<?php

namespace App\Jobs\Reports;

use App\Exports\Reports\Production\ProductionLegacyLayoutExport;
use App\Models\User;
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
 * Excel da lista de Produção no layout anterior (59 colunas), com o mesmo filtro do "Relatório de Produção" da tela.
 * O recorte de empresas do solicitante (usuário de contrato) é reaplicado aqui, nunca vem dos parâmetros.
 *
 * Params: os mesmos de ProductionReportExportJob (filtros de ProductionReportAnalytics::normalize()).
 */
class ProductionLegacyLayoutExportJob implements ShouldQueue
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
            Log::warning('ProductionLegacyLayoutExportJob sem usuário', ['user_id' => $ownerId]);

            return;
        }

        try {
            $filters = $analytics->normalize(array_merge($this->params, ['scope' => $analytics->scopeFor($user)]));
            $records = $analytics->query($filters)
                ->select('productions.*')
                ->with([
                    'Dispatcher:id,name',
                    'Dispatcher.Company:id,name',
                    'Att:id,name',
                    'Att.Company:id,name',
                    'User:id,name',
                    'Company:id,name',
                    'Service:uuid,service',
                    'Note:id,note,material,group2,group5,lexp,postes,nexp,doe,rubrica,type_note',
                    'Note.RamalForm:id,note_id,created_at',
                    'Note.WorkForm' => fn ($q) => $q->with(['Orders:id', 'Adsform:id,created_at']),
                    'Analise',
                    'Reclaim:id,category',
                ])
                ->orderBy('productions.completed_at');

            $rowEstimate = (clone $records)->toBase()->count();

            $fileName = now()->format('YmdHis') . "_{$ownerId}_producao_layout_anterior.xlsx";
            $filePath = 'exports/' . $fileName;
            $disk     = $exports->disk();
            $exports->ensureDirectory();

            $control = ProductionReportExportJob::controlRows($user, $filters, 'Relatório de Produção (layout anterior, 59 colunas)');

            if (!Excel::store(new ProductionLegacyLayoutExport($records, $rowEstimate, (string) $user->name, $control), $filePath, $exports->diskName())) {
                throw new \RuntimeException('Arquivo não foi gerado no disco esperado.');
            }

            $user->notify(new SystemNotification(new UserNotificationData(
                title: 'Exportação concluída!',
                message: 'A lista de Produção no layout anterior (59 colunas) está pronta para download em Excel.<br><br>Clique para baixar.',
                link: URL::temporarySignedRoute('exports.download', now()->addDays(7), ['user' => $ownerId, 'file' => $fileName]),
                status: 'download',
            )));
        } catch (Throwable $e) {
            Log::error('ProductionLegacyLayoutExportJob falhou', ['user_id' => $this->userId, 'params' => $this->params, 'error' => $e->getMessage()]);

            if (isset($disk) && $filePath && $disk->exists($filePath)) {
                $disk->delete($filePath);
            }

            throw $e;
        }
    }

    public function failed(Throwable $exception): void
    {
        Log::critical('ProductionLegacyLayoutExportJob FAILED', ['user_id' => $this->userId, 'error' => $exception->getMessage()]);

        User::find($this->userId)?->notify(new SystemNotification(new UserNotificationData(
            title: 'Erro na exportação',
            message: 'Não foi possível gerar o Excel no layout anterior. Tente novamente com um período menor ou fale com o suporte.',
            status: 'error',
        )));
    }
}
