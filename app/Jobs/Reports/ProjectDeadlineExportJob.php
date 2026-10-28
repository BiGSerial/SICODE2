<?php

namespace App\Jobs\Reports;

use App\Exports\Reports\ProjectDeadline\ProjectDeadlineExport;
use App\Models\{Company, User};
use App\Notifications\SystemNotification;
use App\Services\Files\ExportStorage;
use App\Services\Indicators\ProjectDeadlineAnalytics;
use App\Support\Notifications\UserNotificationData;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\{InteractsWithQueue, SerializesModels};
use Illuminate\Support\Facades\{Log, URL};
use Maatwebsite\Excel\Facades\Excel;
use Throwable;

/**
 * Excel do "Prazo de Projetos" (gerência): as mesmas séries da tela (levantamento, desenho, por empresa) mais a lista completa.
 * Só gera para quem tem a permissão `management`; o arquivo vai para o solicitante.
 *
 * Params: company (UUID, opcional), dt_init/dt_end ('Y-m-d'), exclude (rubrics, prefixes, next_stats: o expurgo escolhido na tela).
 */
class ProjectDeadlineExportJob implements ShouldQueue
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

    public function handle(ProjectDeadlineAnalytics $analytics, ExportStorage $exports): void
    {
        $ownerId  = (string) $this->userId;
        $user     = User::find($ownerId);
        $filePath = null;

        if (!$user || !$user->can('management')) {
            Log::warning('ProjectDeadlineExportJob negado: usuário sem permissão de gerência', ['user_id' => $ownerId]);

            return;
        }

        try {
            $company = $this->params['company'] ?? null;

            $report = $analytics->report([
                'company' => $company,
                'from'    => $this->params['dt_init'] ?? null,
                'to'      => $this->params['dt_end'] ?? null,
                'exclude' => $this->params['exclude'] ?? [],
            ]);

            $companyLabel = $company ? Company::withTrashed()->whereKey($company)->value('name') : null;

            // Arquivo direto em exports/ (a rota assinada exports.download só serve esse formato); o dono vai no nome.
            $fileName = now()->format('YmdHis') . "_{$ownerId}_prazo_projetos.xlsx";
            $filePath = 'exports/' . $fileName;
            $disk     = $exports->disk();
            $exports->ensureDirectory();

            if (!Excel::store(new ProjectDeadlineExport($report, (string) $user->name, $companyLabel), $filePath, $exports->diskName())) {
                throw new \RuntimeException('Arquivo não foi gerado no disco esperado.');
            }

            $user->notify(new SystemNotification(new UserNotificationData(
                title: 'Exportação concluída!',
                message: 'O relatório de prazo de projetos está pronto para download em Excel.',
                link: URL::temporarySignedRoute('exports.download', now()->addDays(7), ['user' => $ownerId, 'file' => $fileName]),
                status: 'download',
            )));
        } catch (Throwable $e) {
            Log::error('ProjectDeadlineExportJob falhou', ['user_id' => $this->userId, 'params' => $this->params, 'error' => $e->getMessage()]);

            if (isset($disk) && $filePath && $disk->exists($filePath)) {
                $disk->delete($filePath);
            }

            throw $e;
        }
    }

    public function failed(Throwable $exception): void
    {
        Log::critical('ProjectDeadlineExportJob FAILED', ['user_id' => $this->userId, 'error' => $exception->getMessage()]);

        User::find($this->userId)?->notify(new SystemNotification(new UserNotificationData(
            title: 'Exportação falhou',
            message: 'A geração do Excel de prazo de projetos falhou após novas tentativas. Tente novamente mais tarde.',
            status: 'error',
        )));
    }
}
