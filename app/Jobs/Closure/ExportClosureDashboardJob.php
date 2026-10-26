<?php

namespace App\Jobs\Closure;

use App\Exports\Closure\Dashboard\ClosureDashboardExport;
use App\Models\User;
use App\Notifications\SystemNotification;
use App\Services\Closure\Analytics\ClosureDashboardReport;
use App\Support\Notifications\UserNotificationData;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\{InteractsWithQueue, SerializesModels};
use Illuminate\Support\Facades\{Log, Storage};
use Maatwebsite\Excel\Facades\Excel;
use Throwable;

/**
 * Exporta, em segundo plano, o Painel analítico do Encerramento (uma aba por bloco da tela e as atividades detalhadas por status), com os
 * filtros que o usuário tinha no painel. Só despachante (closure.dispatch). Avisa por SystemNotification com o link do arquivo.
 */
class ExportClosureDashboardJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public $tries = 2;

    public $backoff = [30, 120];

    public int $timeout = 900;

    /** @param array<string, mixed> $filters filtros do painel (type, company_id, from, to) */
    public function __construct(public array $filters, public int $staleDays, public string $userId)
    {
        $this->onQueue('exports');
    }

    public function handle(): void
    {
        $user = User::find($this->userId);

        try {
            abort_unless($user && $user->can('closure.dispatch'), 403);

            $report   = app(ClosureDashboardReport::class)->build($this->filters, $this->staleDays);
            $filePath = 'exports/encerramento_painel_' . now()->format('YmdHis') . '_' . substr(md5($this->userId . microtime()), 0, 6) . '.xlsx';

            Storage::disk('local')->makeDirectory('exports');
            Excel::store(new ClosureDashboardExport($report, $user->name), $filePath, 'local');

            if (!Storage::disk('local')->exists($filePath)) {
                throw new \RuntimeException('Arquivo não foi gerado.');
            }

            $user->notify(new SystemNotification(new UserNotificationData(
                title: 'Exportação - Painel analítico do Encerramento',
                message: 'Seu Excel do painel está pronto (' . count($report['orders']) . ' Ordens ativas em ' . (count(array_filter($report['orders_by_status'])) + 9) . ' abas).',
                link: Storage::url($filePath),
                status: 'download',
            )));
        } catch (Throwable $exception) {
            Log::error('ExportClosureDashboardJob falhou', ['error_message' => $exception->getMessage(), 'filters' => $this->filters]);

            throw $exception;
        }
    }

    public function failed(Throwable $exception): void
    {
        User::find($this->userId)?->notify(new SystemNotification(new UserNotificationData(
            title: 'Erro ao exportar o painel',
            message: 'Ocorreu um erro ao gerar o arquivo. ' . $exception->getMessage(),
            status: 'error',
        )));
    }
}
