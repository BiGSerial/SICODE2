<?php

namespace App\Jobs\Dispatchs;

use App\Exports\Dispatchs\Dashboard\ActivityDashboardExport;
use App\Models\{Service, User};
use App\Notifications\SystemNotification;
use App\Services\Dispatch\Dashboard\ActivityDashboardAnalytics;
use App\Services\Files\ExportStorage;
use App\Support\Notifications\UserNotificationData;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\{InteractsWithQueue, SerializesModels};
use Illuminate\Support\Facades\{Log, URL};
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use Throwable;

/**
 * Excel do Dashboard de atividade (docs/dashboards/DASHBOARD_ATIVIDADE.md): os mesmos filtros e números da tela, mais a pilha crítica completa.
 * Filtros: os de `ActivityDashboardAnalytics::normalize()` (type, tracking, from, to, limit).
 */
class ActivityDashboardExportJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public $tries = 2;

    public $backoff = [30, 120];

    public int $timeout = 900;

    public function __construct(public string $serviceUuid, public array $filters, public string|int $userId)
    {
        $this->onConnection('database');
        $this->onQueue('exports');
    }

    public function handle(ActivityDashboardAnalytics $analytics, ExportStorage $exports): void
    {
        $ownerId  = (string) $this->userId;
        $user     = User::find($ownerId);
        $service  = Service::with('Status')->where('uuid', $this->serviceUuid)->first();
        $filePath = null;

        if (!$user || !$service) {
            Log::warning('ActivityDashboardExportJob sem usuário ou atividade', ['user_id' => $ownerId, 'service' => $this->serviceUuid]);

            return;
        }

        try {
            $report   = ActivityDashboardAnalytics::for($service)->report($service, $this->filters);
            $fileName = now()->format('YmdHis') . "_{$ownerId}_dashboard_" . Str::slug($service->service, '_') . '.xlsx';
            $filePath = 'exports/' . $fileName;
            $disk     = $exports->disk();
            $exports->ensureDirectory();

            if (!Excel::store(new ActivityDashboardExport($report, (string) $user->name, $this->controlRows($user, $service, $report['filters'])), $filePath, $exports->diskName())) {
                throw new \RuntimeException('Arquivo não foi gerado no disco esperado.');
            }

            $user->notify(new SystemNotification(new UserNotificationData(
                title: 'Exportação concluída!',
                message: 'O Dashboard de ' . $service->service . ' está pronto para download em Excel.<br><br>Clique para baixar.',
                link: URL::temporarySignedRoute('exports.download', now()->addDays(7), ['user' => $ownerId, 'file' => $fileName]),
                status: 'download',
            )));
        } catch (Throwable $e) {
            Log::error('ActivityDashboardExportJob falhou', ['user_id' => $this->userId, 'service' => $this->serviceUuid, 'error' => $e->getMessage()]);

            if (isset($disk) && $filePath && $disk->exists($filePath)) {
                $disk->delete($filePath);
            }

            throw $e;
        }
    }

    public function failed(Throwable $exception): void
    {
        Log::critical('ActivityDashboardExportJob FAILED', ['user_id' => $this->userId, 'error' => $exception->getMessage()]);

        User::find($this->userId)?->notify(new SystemNotification(new UserNotificationData(
            title: 'Erro na exportação',
            message: 'Não foi possível gerar o Excel do Dashboard. Tente novamente com um período menor ou fale com o suporte.',
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
    private function controlRows(User $user, Service $service, array $f): array
    {
        return [
            'Usuário'                => (string) $user->name,
            'E-mail'                 => (string) $user->email,
            'Data/hora'              => now()->format('d/m/Y H:i:s'),
            'Tipo'                   => 'Dashboard de ' . $service->service,
            'Tipo de nota'           => ['2' => 'Somente OVs', '1' => 'Somente Notas', '' => 'Notas e OVs'][$f['type']],
            'Rubrica Acompanhamento' => $f['tracking'] ? 'Incluída' : 'Fora (padrão)',
            'Período da aderência'   => $f['from']->format('d/m/Y') . ' a ' . $f['to']->format('d/m/Y'),
            'Limite da 1ª faixa'     => $f['limit'] . ' dias',
            'Pilha'                  => 'Situação de agora (as notas que a lista de despacho da atividade enxerga)',
        ];
    }
}
