<?php

namespace App\Jobs\Reports;

use App\Exports\Reports\CancellationListExport;
use App\Models\User;
use App\Notifications\SystemNotification;
use App\Support\Notifications\UserNotificationData;
use App\Support\Reports\CancellationListQuery;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\{InteractsWithQueue, SerializesModels};
use Illuminate\Support\Facades\{Log, Storage};
use Throwable;

class ExportCancellationListJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /** @var array<string, mixed> */
    public array $filters;

    public string $userId;

    public $tries = 2;

    public $backoff = [30, 120];

    public int $timeout = 1200;

    /**
     * @param  array<string, mixed>  $filters
     * @param  array<int, int>|null  $visibleRequesterIds
     */
    public function __construct(array $filters, ?array $visibleRequesterIds, string $userId)
    {
        $this->onQueue('exports');
        $this->filters                        = $filters;
        $this->filters['visibleRequesterIds'] = $visibleRequesterIds;
        $this->userId                         = $userId;
    }

    public function handle(): void
    {
        $user     = User::find($this->userId);
        $filePath = null;

        try {
            $query = CancellationListQuery::build($this->filters, $this->filters['visibleRequesterIds']);
            $query->selectRaw('
                cr.id,
                n.note as note_number,
                cc.name as category_name,
                cr.scope,
                cr.status,
                requester.name as requester_name,
                assignee.name as assignee_name,
                engineer.name as engineer_name,
                COALESCE(cr.submitted_at, cr.created_at) as opened_at,
                cr.closed_at,
                TIMESTAMPDIFF(SECOND, cr.assigned_at, cr.closed_at) as exec_seconds,
                TIMESTAMPDIFF(SECOND, cr.engineer_approval_requested_at, cr.engineer_approval_decided_at) as eng_seconds,
                TIMESTAMPDIFF(SECOND, cr.submitted_at, cr.closed_at) as close_seconds,
                TIMESTAMPDIFF(SECOND, cr.engineer_approval_decided_at, cr.closed_at) as final_seconds,
                CASE cr.scope
                    WHEN "NOTE_FULL" THEN "Nota inteira"
                    WHEN "ORDERS_PARTIAL" THEN "Ordens específicas"
                    WHEN "WORK_FORM_ONLY" THEN "Somente WorkForm"
                    ELSE COALESCE(cr.scope, "-")
                END as scope_label,
                CASE cr.status
                    WHEN "DRAFT" THEN "Rascunho"
                    WHEN "SUBMITTED" THEN "Enviado"
                    WHEN "ASSIGNED" THEN "Em execução"
                    WHEN "PAUSED" THEN "Pausado"
                    WHEN "DONE" THEN "Concluído"
                    WHEN "REJECTED" THEN "Rejeitado"
                    WHEN "ABORTED" THEN "Cancelado"
                    ELSE COALESCE(cr.status, "-")
                END as status_label
            ')->orderByDesc('opened_at');

            $filePath = 'exports/cancelamentos_' . now()->format('YmdHis') . '.xlsx';
            (new CancellationListExport($query))->store($filePath, 'local');

            if (!$user || !Storage::disk('local')->exists($filePath)) {
                throw new \RuntimeException('Arquivo não foi gerado no disco esperado.');
            }

            $user->notify(new SystemNotification(new UserNotificationData(
                title: 'Exportação concluída!',
                message: 'A lista de cancelamentos está pronta para download.',
                link: Storage::url($filePath),
                status: 'download',
            )));
        } catch (Throwable $exception) {
            Log::error('ExportCancellationListJob falhou', [
                'user_id' => $this->userId,
                'filters' => $this->filters,
                'attempt' => $this->attempts(),
                'error'   => $exception->getMessage(),
            ]);

            if ($filePath && Storage::disk('local')->exists($filePath)) {
                Storage::disk('local')->delete($filePath);
            }

            throw $exception;
        }
    }

    public function failed(Throwable $exception): void
    {
        Log::critical('ExportCancellationListJob failed', [
            'user_id' => $this->userId,
            'error'   => $exception->getMessage(),
        ]);

        if ($user = User::find($this->userId)) {
            $user->notify(new SystemNotification(new UserNotificationData(
                title: 'Exportação falhou',
                message: 'Não foi possível gerar a lista de cancelamentos. Tente novamente.',
                status: 'error',
            )));
        }
    }
}
