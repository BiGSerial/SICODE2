<?php

namespace App\Jobs\Engineers;

use App\Exports\Engineers\ViabilityDashboardExport;
use App\Models\{Company, User};
use App\Notifications\SystemNotification;
use App\Services\Engineers\ViabilityDashboardAnalytics;
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
 * Excel do painel de Viabilidade (Engenharia): as mesmas séries da tela (fila, por empresa, motivos de rejeição, fluxo diário).
 * Só gera para engenheiro (ou superadm); respeita a visão de quem pediu. O arquivo vai para o solicitante.
 *
 * Params: company (UUID, opcional), dt_init/dt_end ('Y-m-d').
 */
class ViabilityDashboardExportJob implements ShouldQueue
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

    public function handle(ViabilityDashboardAnalytics $analytics, ExportStorage $exports): void
    {
        $ownerId  = (string) $this->userId;
        $user     = User::find($ownerId);
        $filePath = null;

        if (!$user || !$user->can('engineer')) {
            Log::warning('ViabilityDashboardExportJob negado: usuário sem perfil de engenheiro', ['user_id' => $ownerId]);

            return;
        }

        try {
            $company = $this->params['company'] ?? null;

            $report = $analytics->report($user, [
                'company' => $company,
                'from'    => $this->params['dt_init'] ?? null,
                'to'      => $this->params['dt_end'] ?? null,
            ]);

            $companyLabel = $company ? Company::withTrashed()->whereKey($company)->value('name') : null;

            // Arquivo direto em exports/ (a rota assinada exports.download só serve esse formato); o dono vai no nome.
            $fileName = now()->format('YmdHis') . "_{$ownerId}_painel_viabilidade.xlsx";
            $filePath = 'exports/' . $fileName;
            $disk     = $exports->disk();
            $exports->ensureDirectory();

            if (!Excel::store(new ViabilityDashboardExport($report, (string) $user->name, $companyLabel), $filePath, $exports->diskName())) {
                throw new \RuntimeException('Arquivo não foi gerado no disco esperado.');
            }

            $user->notify(new SystemNotification(new UserNotificationData(
                title: 'Exportação concluída!',
                message: 'O painel de viabilidade está pronto para download em Excel.',
                link: URL::temporarySignedRoute('exports.download', now()->addDays(7), ['user' => $ownerId, 'file' => $fileName]),
                status: 'download',
            )));
        } catch (Throwable $e) {
            Log::error('ViabilityDashboardExportJob falhou', ['user_id' => $this->userId, 'params' => $this->params, 'error' => $e->getMessage()]);

            if (isset($disk) && $filePath && $disk->exists($filePath)) {
                $disk->delete($filePath);
            }

            throw $e;
        }
    }

    public function failed(Throwable $exception): void
    {
        Log::critical('ViabilityDashboardExportJob FAILED', ['user_id' => $this->userId, 'error' => $exception->getMessage()]);

        User::find($this->userId)?->notify(new SystemNotification(new UserNotificationData(
            title: 'Exportação falhou',
            message: 'A geração do Excel do painel de viabilidade falhou após novas tentativas. Tente novamente mais tarde.',
            status: 'error',
        )));
    }
}
