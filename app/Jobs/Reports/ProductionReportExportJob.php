<?php

namespace App\Jobs\Reports;

use App\Exports\Reports\Production\ProductionReportExport;
use App\Models\{Company, Service, User};
use App\Notifications\SystemNotification;
use App\Services\Files\ExportStorage;
use App\Services\Reports\Production\ProductionReportAnalytics;
use App\Support\Notifications\UserNotificationData;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\{InteractsWithQueue, SerializesModels};
use Illuminate\Support\Facades\{Log, URL};
use Maatwebsite\Excel\Facades\Excel;
use Throwable;

/**
 * Excel do "Relatório de Produção" (Serviços → Relatórios): o mesmo filtro e os mesmos números da tela, mais a lista completa.
 * O recorte de empresas do solicitante (usuário de contrato) é reaplicado aqui, nunca vem dos parâmetros.
 *
 * Params: os filtros de ProductionReportAnalytics::normalize() (from/to 'Y-m-d', services, company, user, conclusions, doe, ma,
 * confirmed, include_open, include_ri, search, multisearch).
 */
class ProductionReportExportJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public array $params;

    public string|int $userId;

    public $tries = 2;

    public $backoff = [30, 120];

    public int $timeout = 1800; // 30 min para exportacoes grandes

    public function __construct(array $params, string|int $userId)
    {
        $this->params = $params;
        $this->userId = $userId;
        $this->onConnection('database');
        $this->onQueue('exports');
    }

    public function handle(ProductionReportAnalytics $analytics, ExportStorage $exports): void
    {
        $ownerId  = (string) $this->userId;
        $user     = User::find($ownerId);
        $filePath = null;

        if (!$user) {
            Log::warning('ProductionReportExportJob sem usuário', ['user_id' => $ownerId]);

            return;
        }

        try {
            $report  = $analytics->report(array_merge($this->params, ['scope' => $analytics->scopeFor($user)]));
            $records = $analytics->listQuery($report['filters']);

            $fileName = now()->format('YmdHis') . "_{$ownerId}_relatorio_producao.xlsx";
            $filePath = 'exports/' . $fileName;
            $disk     = $exports->disk();
            $exports->ensureDirectory();

            if (!Excel::store(new ProductionReportExport($report, $records, (string) $user->name, self::controlRows($user, $report['filters'])), $filePath, $exports->diskName())) {
                throw new \RuntimeException('Arquivo não foi gerado no disco esperado.');
            }

            $user->notify(new SystemNotification(new UserNotificationData(
                title: 'Exportação concluída!',
                message: 'O Relatório de Produção está pronto para download em Excel.<br><br>Clique para baixar.',
                link: URL::temporarySignedRoute('exports.download', now()->addDays(7), ['user' => $ownerId, 'file' => $fileName]),
                status: 'download',
            )));
        } catch (Throwable $e) {
            Log::error('ProductionReportExportJob falhou', ['user_id' => $this->userId, 'params' => $this->params, 'error' => $e->getMessage()]);

            if (isset($disk) && $filePath && $disk->exists($filePath)) {
                $disk->delete($filePath);
            }

            throw $e;
        }
    }

    public function failed(Throwable $exception): void
    {
        Log::critical('ProductionReportExportJob FAILED', ['user_id' => $this->userId, 'error' => $exception->getMessage()]);

        User::find($this->userId)?->notify(new SystemNotification(new UserNotificationData(
            title: 'Erro na exportação',
            message: 'Não foi possível gerar o Excel do Relatório de Produção. Tente novamente com um período menor ou fale com o suporte.',
            status: 'error',
        )));
    }

    /**
     * Aba "Controle Exportacao": quem pediu, quando, e os filtros aplicados em texto.
     *
     * @param array<string, mixed> $f filtros normalizados
     *
     * @return array<string, string>
     */
    public static function controlRows(User $user, array $f, string $type = 'Relatório de Produção'): array
    {
        $tri   = fn (string $v, string $yes, string $no) => $v === 'sim' ? $yes : ($v === 'nao' ? $no : 'Todos');
        $names = fn (array $uuids) => $uuids ? Service::whereIn('uuid', $uuids)->pluck('service')->implode(', ') : 'Todas';

        return [
            'Usuário'             => (string) $user->name,
            'E-mail'              => (string) $user->email,
            'Data/hora'           => now()->format('d/m/Y H:i:s'),
            'Tipo'                => $type,
            'Período (conclusão)' => $f['from'] || $f['to'] ? ($f['from']?->format('d/m/Y') ?? 'início') . ' a ' . ($f['to']?->format('d/m/Y') ?? 'hoje') : 'Todo o período',
            'Atividades'          => $names($f['services']),
            'Empresa'             => $f['company'] ? (string) Company::withTrashed()->whereKey($f['company'])->value('name') : 'Todas',
            'Pessoa'              => $f['user'] ? (string) User::withTrashed()->whereKey($f['user'])->value('name') : 'Todas',
            'Conclusões'          => $f['conclusions'] ? implode(', ', $f['conclusions']) : 'Todas',
            'Órgão externo'       => $tri($f['doe'], 'Depende', 'Não depende'),
            'Meio ambiente'       => $tri($f['ma'], 'Depende', 'Não depende'),
            'Contabilização'      => $tri($f['confirmed'], 'Contabilizado', 'Não contabilizado'),
            'Incluir em aberto'   => $f['include_open'] ? 'Sim' : 'Não',
            'Incluir RI'          => $f['include_ri'] ? 'Sim' : 'Não',
            'Busca'               => $f['multisearch'] ? count($f['multisearch']) . ' termo(s) (busca múltipla)' : ($f['search'] !== '' ? $f['search'] : '—'),
            'Ordem'               => 'Mais recente para a mais antiga',
        ];
    }
}
