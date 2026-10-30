<?php

namespace App\Jobs\Engineers;

use App\Exports\Engineers\InformDashboardExport;
use App\Models\{Company, User};
use App\Notifications\SystemNotification;
use App\Services\Engineers\InformDashboardAnalytics;
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
 * Excel do "Painel de informes" (Engenharia → Informes): os mesmos filtros e números da tela, mais os informes do período.
 * O recorte de empresas do solicitante (usuário de contrato) é reaplicado aqui, nunca vem dos parâmetros.
 *
 * Params: os filtros de InformDashboardAnalytics::normalize() (from/to 'Y-m-d', company, search, returned, retry, status, ads).
 */
class InformDashboardExportJob implements ShouldQueue
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

    public function handle(InformDashboardAnalytics $analytics, ExportStorage $exports): void
    {
        $ownerId  = (string) $this->userId;
        $user     = User::find($ownerId);
        $filePath = null;

        if (!$user) {
            Log::warning('InformDashboardExportJob sem usuário', ['user_id' => $ownerId]);

            return;
        }

        try {
            $report = $analytics->report(array_merge($this->params, ['scope' => $analytics->scopeFor($user)]));

            $fileName = now()->format('YmdHis') . "_{$ownerId}_painel_informes.xlsx";
            $filePath = 'exports/' . $fileName;
            $disk     = $exports->disk();
            $exports->ensureDirectory();

            $report['records'] = $analytics->listRows($report, $report['filters'])->all();

            if (!Excel::store(new InformDashboardExport($report, (string) $user->name, $this->controlRows($user, $report['filters'])), $filePath, $exports->diskName())) {
                throw new \RuntimeException('Arquivo não foi gerado no disco esperado.');
            }

            $user->notify(new SystemNotification(new UserNotificationData(
                title: 'Exportação concluída!',
                message: 'O Painel de informes está pronto para download em Excel.<br><br>Clique para baixar.',
                link: URL::temporarySignedRoute('exports.download', now()->addDays(7), ['user' => $ownerId, 'file' => $fileName]),
                status: 'download',
            )));
        } catch (Throwable $e) {
            Log::error('InformDashboardExportJob falhou', ['user_id' => $this->userId, 'params' => $this->params, 'error' => $e->getMessage()]);

            if (isset($disk) && $filePath && $disk->exists($filePath)) {
                $disk->delete($filePath);
            }

            throw $e;
        }
    }

    public function failed(Throwable $exception): void
    {
        Log::critical('InformDashboardExportJob FAILED', ['user_id' => $this->userId, 'error' => $exception->getMessage()]);

        User::find($this->userId)?->notify(new SystemNotification(new UserNotificationData(
            title: 'Erro na exportação',
            message: 'Não foi possível gerar o Excel do Painel de informes. Tente novamente com um período menor ou fale com o suporte.',
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
    private function controlRows(User $user, array $f): array
    {
        $returned = ['sim' => 'Já devolvidos', 'nao' => 'Nunca devolvidos', 'aguardando' => 'Aguardando novo informe'];
        $statuses = app(InformDashboardAnalytics::class)->statuses();

        return [
            'Usuário'                   => (string) $user->name,
            'E-mail'                    => (string) $user->email,
            'Data/hora'                 => now()->format('d/m/Y H:i:s'),
            'Tipo'                      => 'Painel de informes',
            'Período (data do informe)' => $f['from']->format('d/m/Y') . ' a ' . $f['to']->format('d/m/Y'),
            'Empreiteira'               => $f['company'] ? (string) Company::withTrashed()->whereKey($f['company'])->value('name') : 'Todas',
            'Busca'                     => $f['search'] !== '' ? $f['search'] : '—',
            'Devolução'                 => $returned[$f['returned']] ?? 'Todos',
            'Reinforme'                 => ['sim' => 'Só reinformes', 'nao' => 'Sem reinforme'][$f['retry']] ?? 'Todos',
            'Situação atual'            => $f['status'] ? ($statuses[$f['status']] ?? $f['status']) : 'Todas',
            'Situação da ADS (lista)'   => $f['ads'] !== '' ? InformDashboardAnalytics::BUCKETS[$f['ads']] : 'Todas',
            'Ordem'                     => 'Do informe mais recente para o mais antigo',
        ];
    }
}
