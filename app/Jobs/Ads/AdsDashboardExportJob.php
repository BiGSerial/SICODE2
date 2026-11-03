<?php

namespace App\Jobs\Ads;

use App\Exports\Ads\AdsDashboardExport;
use App\Models\{Company, User};
use App\Notifications\SystemNotification;
use App\Services\Ads\AdsDashboardAnalytics;
use App\Services\Files\ExportStorage;
use App\Support\Notifications\UserNotificationData;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\{InteractsWithQueue, SerializesModels};
use Illuminate\Support\Facades\{Log, URL};
use Maatwebsite\Excel\Facades\Excel;
use Throwable;

/**
 * Excel do painel de ADS solicitadas, nos filtros da tela. Só gera para engenheiro (ou superadm). Partes sem conexão (SQL Server) saem
 * com o aviso na própria aba, o arquivo não falha. O arquivo vai para o solicitante.
 *
 * Params: os filtros do AdsRequestedReport::filters().
 */
class AdsDashboardExportJob implements ShouldQueue
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

    public function handle(AdsDashboardAnalytics $analytics, ExportStorage $exports): void
    {
        $ownerId  = (string) $this->userId;
        $user     = User::find($ownerId);
        $filePath = null;

        if (!$user || !$user->can('engineer')) {
            Log::warning('AdsDashboardExportJob negado: usuário sem perfil de engenheiro', ['user_id' => $ownerId]);

            return;
        }

        try {
            $report = $analytics->report($this->params);

            $fileName = now()->format('YmdHis') . "_{$ownerId}_ads_solicitadas.xlsx";
            $filePath = 'exports/' . $fileName;
            $disk     = $exports->disk();
            $exports->ensureDirectory();

            if (!Excel::store(new AdsDashboardExport($report, $this->audit($user)), $filePath, $exports->diskName())) {
                throw new \RuntimeException('Arquivo não foi gerado no disco esperado.');
            }

            $user->notify(new SystemNotification(new UserNotificationData(
                title: 'Exportação concluída!',
                message: 'O relatório do painel de ADS está pronto para download em Excel.',
                link: URL::temporarySignedRoute('exports.download', now()->addDays(7), ['user' => $ownerId, 'file' => $fileName]),
                status: 'download',
            )));
        } catch (Throwable $e) {
            Log::error('AdsDashboardExportJob falhou', ['user_id' => $this->userId, 'params' => $this->params, 'error' => $e->getMessage()]);

            if (isset($disk) && $filePath && $disk->exists($filePath)) {
                $disk->delete($filePath);
            }

            throw $e;
        }
    }

    public function failed(Throwable $exception): void
    {
        Log::critical('AdsDashboardExportJob FAILED', ['user_id' => $this->userId, 'error' => $exception->getMessage()]);

        User::find($this->userId)?->notify(new SystemNotification(new UserNotificationData(
            title: 'Exportação falhou',
            message: 'A geração do Excel do painel de ADS falhou após novas tentativas. Tente novamente mais tarde.',
            status: 'error',
        )));
    }

    /** @return array<int, array{0: string, 1: string}> */
    private function audit(User $user): array
    {
        $p       = $this->params;
        $company = ($p['companyIds'][0] ?? null) ? Company::withTrashed()->whereKey($p['companyIds'][0])->value('name') : null;
        $by      = ($p['completed_in'] ?? null) ? 'conclusão' : 'solicitação';
        $from    = $p['date_in'] ?? $p['completed_in'] ?? null;
        $to      = $p['date_out'] ?? $p['completed_out'] ?? null;

        return [
            ['Usuário', (string) $user->name],
            ['E-mail', (string) $user->email],
            ['Gerado em', now()->format('d/m/Y H:i:s')],
            ['Tipo', 'Painel de ADS solicitadas (Engenharia)'],
            ['Período', ($from ?? '—') . ' a ' . ($to ?? '—') . ' (pela data de ' . $by . ')'],
            ['Empreiteira', $company ?? 'Todas'],
            ['Situação', ['all' => 'Todas', 'active' => 'Em atividade', 'delivered' => 'Entregues'][$p['statusFilter'] ?? 'all'] ?? 'Todas'],
            ['Status exato', ($p['status_exact'] ?? '') !== '' ? (string) $p['status_exact'] : 'Todos'],
            ['Origem', ['reused' => 'Reaproveitada', 'new' => 'Nova solicitação'][$p['origin'] ?? ''] ?? 'Todas'],
            ['Busca', ($p['search'] ?? '') !== '' ? (string) $p['search'] : '—'],
            ['Fila atual', 'Direto do SQL Server; fica em branco com o aviso se a conexão estiver fora do ar'],
            ['Limite', 'Fila: 5.000 linhas · Histórico: 20.000 linhas'],
        ];
    }
}
