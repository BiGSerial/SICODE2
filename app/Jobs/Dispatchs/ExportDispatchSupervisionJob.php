<?php

namespace App\Jobs\Dispatchs;

use App\Custom\Notestatus;
use App\Exports\Dispatchs\DispatchSupervisionControlExport;
use App\Models\{Production, Service, User};
use App\Notifications\SystemNotification;
use App\Services\Files\ExportStorage;
use App\Services\Supervision\{SupervisionControlQuery, SupervisionControlRowPresenter};
use App\Support\Notifications\UserNotificationData;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\{InteractsWithQueue, SerializesModels};
use Illuminate\Support\Facades\{Log, URL};
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use Throwable;

/**
 * Excel do Controle de Fiscalização (e da parceira): todas as páginas dos critérios da tela, com a mesma consulta
 * (`SupervisionControlQuery`) e a mesma regra de visibilidade de quem pediu. O arquivo vai para o solicitante.
 *
 * Params: os critérios de `SupervisionControlQuery::normalize` (service, search, multiSearch, note_type, users, status, cityCodes).
 */
class ExportDispatchSupervisionJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public array $params;

    public string|int $userId;

    public $tries = 2;

    public $backoff = [60, 180];

    public int $timeout = 900;

    public function __construct(array $params, string|int $userId)
    {
        $this->params = $params;
        $this->userId = $userId;
        $this->onQueue('exports');
    }

    public function handle(SupervisionControlQuery $query, SupervisionControlRowPresenter $presenter, ExportStorage $exports): void
    {
        $ownerId  = (string) $this->userId;
        $user     = User::find($ownerId);
        $service  = Service::where('uuid', $this->params['service'] ?? $this->params['service_id'] ?? null)->first();
        $filePath = null;

        if (!$user || !$service) {
            throw new \RuntimeException('Usuário ou atividade não encontrados para a exportação.');
        }

        try {
            $criteria = $query->normalize($this->params + ['service' => $service->uuid]);
            $rows     = [];
            $byStatus = [];

            $query->list($criteria, $user)->with('note.orders:id,note_id,ordem')->chunk(500, function ($chunk) use (&$rows, &$byStatus, $presenter) {
                foreach ($chunk as $production) {
                    $line   = $presenter->present($production);
                    $rows[] = $this->row($production, $line);

                    $byStatus[$line['statusLabel']] = ($byStatus[$line['statusLabel']] ?? 0) + 1;
                }
            });

            $fileName = now()->format('YmdHis') . "_{$ownerId}_controle_" . $service->folder . '.xlsx';
            $filePath = 'exports/' . $fileName;
            $disk     = $exports->disk();
            $exports->ensureDirectory();

            $export = new DispatchSupervisionControlExport($rows, $this->summary($byStatus, count($rows)), $this->audit($user, $service, $criteria, count($rows)), $service->service);

            if (!Excel::store($export, $filePath, $exports->diskName())) {
                throw new \RuntimeException('Arquivo não foi gerado no disco esperado.');
            }

            $user->notify(new SystemNotification(new UserNotificationData(
                title: 'Exportação concluída!',
                message: 'O controle de ' . mb_strtolower($service->service) . ' está pronto para download em Excel.',
                link: URL::temporarySignedRoute('exports.download', now()->addDays(7), ['user' => $ownerId, 'file' => $fileName]),
                status: 'download',
            )));
        } catch (Throwable $e) {
            Log::error('ExportDispatchSupervisionJob falhou', ['user_id' => $this->userId, 'params' => $this->params, 'attempt' => $this->attempts(), 'error' => $e->getMessage()]);

            if (isset($disk) && $filePath && $disk->exists($filePath)) {
                $disk->delete($filePath);
            }

            throw $e;
        }
    }

    public function failed(Throwable $exception): void
    {
        User::find((string) $this->userId)?->notify(new SystemNotification(new UserNotificationData(
            title: 'Exportação falhou',
            message: 'A geração do Excel do controle falhou após novas tentativas. Tente novamente mais tarde.',
            status: 'error',
        )));
    }

    /** @return array<int, mixed> mesma ordem de `DispatchSupervisionControlExport::HEADINGS` */
    private function row(Production $production, array $line): array
    {
        $excel = fn ($date) => $date ? Date::dateTimeToExcel(Carbon::parse($date)) : null;
        $note  = $line['note'];

        return [
            ctype_digit((string) $note) ? (int) $note : $note,
            $production->note?->orders->pluck('ordem')->filter()->unique()->implode(', '),
            $this->type($line),
            $line['rubrica'],
            $line['city'],
            $line['group2'],
            $line['postes'],
            $production->dispatcher?->name,
            $production->dispatcher?->Company?->name,
            $production->user?->name,
            $production->company?->name ?? $production->user?->Company?->name,
            $line['wpa']['dd'],
            $line['wpa']['sub'],
            $excel($line['adsAt']),
            $line['adsAmount'],
            $line['adsAt'] ? ($line['adsTacit'] ? ($line['adsDelivered'] ? 'Tácita entregue' : 'Tácita') : 'Entregue') : null,
            $excel($line['informedAt']),
            $line['informedDays'],
            $excel($line['dispatchAt']),
            $line['dispatchDays'],
            $excel($line['attAt']),
            $line['attDays'],
            $line['statusLabel'],
            $line['priority'] ? 'Sim' : 'Não',
            $line['block'] ? 'Sim' : 'Não',
        ];
    }

    /** Tipo como a tela mostra: Parcial, Final (ou Conexão), com a marca de RI ou D5. */
    private function type(array $line): string
    {
        $type = $line['isPartial'] ? 'Parcial' : ($line['isConnection'] ? 'Final (conexão)' : 'Final');

        return $type . ($line['ri'] ? ' · RI' : ($line['d5'] ? ' · D5' : ''));
    }

    /** @param array<string, int> $byStatus @return array<int, array<int, mixed>> */
    private function summary(array $byStatus, int $total): array
    {
        arsort($byStatus);

        return collect($byStatus)->map(fn ($count, $label) => [$label, $count, $total ? round($count / $total * 100, 2) : 0])->values()->all();
    }

    /** @return array<int, array{0: string, 1: string}> */
    private function audit(User $user, Service $service, array $c, int $total): array
    {
        $filter = fn (array $values, \Closure $label) => $values ? implode(', ', array_map($label, $values)) : 'Todos';

        return [
            ['Usuário', (string) $user->name],
            ['E-mail', (string) $user->email],
            ['Gerado em', now()->format('d/m/Y H:i:s')],
            ['Tipo', 'Controle de ' . mb_strtolower($service->service) . ($user->usesContractDispatch() ? ' (parceira)' : '')],
            ['Total de notas', (string) $total],
            ['Busca', $c['search'] !== '' ? $c['search'] : ($c['multiSearch'] ? count($c['multiSearch']) . ' notas/ordens' : '—')],
            ['Tipo de nota', ['1' => 'Nota', '2' => 'OV'][$c['note_type']] ?? 'Ambos'],
            ['Usuário (responsável)', $filter($c['users'], fn ($id) => (string) (User::withTrashed()->whereKey($id)->value('name') ?? $id))],
            ['Status', $filter($c['status'], fn ($s) => Notestatus::status((int) $s)->status)],
            ['Localidade (Região/Regional/Município)', $c['cityCodes'] === null ? 'Todas' : count($c['cityCodes']) . ' município(s)'],
        ];
    }
}
