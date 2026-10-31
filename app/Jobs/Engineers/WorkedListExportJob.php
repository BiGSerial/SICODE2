<?php

namespace App\Jobs\Engineers;

use App\Exports\Engineers\WorkedListExport;
use App\Models\{User, WorkReport};
use App\Notifications\SystemNotification;
use App\Services\Engineers\WorkedListQuery;
use App\Services\Files\ExportStorage;
use App\Support\Notifications\UserNotificationData;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\{InteractsWithQueue, SerializesModels};
use Illuminate\Support\Facades\{Log, URL};
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use Throwable;

/**
 * Excel da "Lista de informes": todos os informes dos filtros da tela (não só a página). Só gera para engenheiro (ou superadm)
 * e respeita as empresas visíveis a quem pediu. O arquivo vai para o solicitante.
 *
 * Params: os filtros do componente (from, to, search, multi, company, rubrica, city, ads, changes, situacao).
 */
class WorkedListExportJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public array $params;

    public string|int $userId;

    public $tries = 2;

    public $backoff = [30, 120];

    public int $timeout = 900;

    public function __construct(array $params, string|int $userId)
    {
        $this->params = $params;
        $this->userId = $userId;
        $this->onConnection('database');
        $this->onQueue('exports');
    }

    public function handle(WorkedListQuery $query, ExportStorage $exports): void
    {
        $ownerId  = (string) $this->userId;
        $user     = User::find($ownerId);
        $filePath = null;

        if (!$user || !$user->can('engineer')) {
            Log::warning('WorkedListExportJob negado: usuário sem perfil de engenheiro', ['user_id' => $ownerId]);

            return;
        }

        try {
            $f    = $query->normalize($this->params);
            $rows = [];

            $query->withRelations($query->query($user, $f))->chunk(500, function ($chunk) use (&$rows, $query) {
                foreach ($chunk as $report) {
                    $rows[] = $this->row($report, $query);
                }
            });

            $fileName = now()->format('YmdHis') . "_{$ownerId}_lista_de_informes.xlsx";
            $filePath = 'exports/' . $fileName;
            $disk     = $exports->disk();
            $exports->ensureDirectory();

            if (!Excel::store(new WorkedListExport($rows, $this->audit($user, $f, count($rows))), $filePath, $exports->diskName())) {
                throw new \RuntimeException('Arquivo não foi gerado no disco esperado.');
            }

            $user->notify(new SystemNotification(new UserNotificationData(
                title: 'Exportação concluída!',
                message: 'A lista de informes está pronta para download em Excel.',
                link: URL::temporarySignedRoute('exports.download', now()->addDays(7), ['user' => $ownerId, 'file' => $fileName]),
                status: 'download',
            )));
        } catch (Throwable $e) {
            Log::error('WorkedListExportJob falhou', ['user_id' => $this->userId, 'params' => $this->params, 'error' => $e->getMessage()]);

            if (isset($disk) && $filePath && $disk->exists($filePath)) {
                $disk->delete($filePath);
            }

            throw $e;
        }
    }

    public function failed(Throwable $exception): void
    {
        Log::critical('WorkedListExportJob FAILED', ['user_id' => $this->userId, 'error' => $exception->getMessage()]);

        User::find($this->userId)?->notify(new SystemNotification(new UserNotificationData(
            title: 'Exportação falhou',
            message: 'A geração do Excel da lista de informes falhou após novas tentativas. Tente novamente mais tarde.',
            status: 'error',
        )));
    }

    /** @return array<int, mixed> */
    private function row(WorkReport $report, WorkedListQuery $query): array
    {
        $ads   = $query->adsOf($report);
        $excel = fn ($d) => $d ? Date::dateTimeToExcel($d) : null;

        return [
            $report->Note?->note,
            $report->Orders->pluck('ordem')->implode(', '),
            $report->Note?->rubrica,
            $report->Note?->lexp,
            $report->Company?->name,
            $report->current_status_label ?: ($report->rejected ? 'Rejeitado' : 'Informado'),
            collect($report->finalScopeBadges())->pluck('label')->implode(' + '),
            $report->changes ? 'SIM' : 'NÃO',
            $report->equipment_count,
            $report->team,
            $report->responsible,
            $excel($report->date),
            $excel($report->informed_at),
            $excel($ads['due']),
            $ads['label'],
            $excel($ads['at']),
            $report->returnwork_count,
            trim(strip_tags((string) $report->observation)),
        ];
    }

    /** @return array<int, array{0: string, 1: string}> */
    private function audit(User $user, array $f, int $total): array
    {
        return [
            ['Usuário', (string) $user->name],
            ['E-mail', (string) $user->email],
            ['Gerado em', now()->format('d/m/Y H:i:s')],
            ['Tipo', 'Lista de informes (Engenharia)'],
            ['Total de informes', (string) $total],
            ['Situação', WorkedListQuery::SITUATIONS[$f['situacao']] ?? $f['situacao']],
            ['Período (informado em)', ($f['from']?->format('d/m/Y') ?? 'início') . ' a ' . ($f['to']?->format('d/m/Y') ?? 'hoje')],
            ['Busca', $f['search'] !== '' ? $f['search'] : ($f['multi'] ? count($f['multi']) . ' notas/ordens' : '—')],
            ['Empreiteira (id)', $f['company'] ?: 'Todas'],
            ['Rubrica', $f['rubrica'] ?: 'Todas'],
            ['Município', $f['city'] ?: 'Todos'],
            ['ADS', WorkedListQuery::ADS[$f['ads']] ?? 'Todas'],
            ['Alteração no projeto', ['sim' => 'Sim', 'nao' => 'Não'][$f['changes']] ?? 'Todas'],
        ];
    }
}
