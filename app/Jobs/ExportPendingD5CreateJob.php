<?php

namespace App\Jobs;

use App\Exports\Services\Payment\PendingD5CreateExport;
use App\Models\FiveNote;
use App\Models\User;
use App\Notifications\SystemNotification;
use App\Traits\WildcardFormmater;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Throwable;

class ExportPendingD5CreateJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;
    use WildcardFormmater;

    public array $params;
    public $userId;
    public $tries = 2;
    public $backoff = [30, 120];

    public function __construct(array $params, $userId)
    {
        $this->onQueue('exports');
        $this->params = $params;
        $this->userId = $userId;
    }

    public function handle(): void
    {
        $user = User::find($this->userId);

        if (!$user) {
            return;
        }

        $disk = Storage::disk('local');
        $filePath = null;

        try {
            $query = FiveNote::query();

            $this->applyBaseConstraints($query);
            $this->applyFilters($query);
            $query->orderBy('dispatch_at')->orderBy('id');

            $query->with([
                'note.WorkForm.Orders',
                'note.Orders',
                'company',
            ]);

            $filePath = 'exports/' . now()->format('YmdHis') . '-five-notes-pending-create.xlsx';

            $disk->makeDirectory('exports');
            Excel::store(new PendingD5CreateExport(clone $query), $filePath, 'local');

            if (!$disk->exists($filePath)) {
                throw new \RuntimeException('Arquivo nao foi gerado.');
            }

            $user->notify(new SystemNotification(
                'Exportacao concluida!',
                'Sua lista de D5 pendentes para criacao esta pronta para download.',
                Storage::url($filePath),
                4,
                []
            ));
        } catch (Throwable $e) {
            Log::error('ExportPendingD5CreateJob falhou', [
                'user_id' => $this->userId,
                'params'  => $this->params,
                'attempt' => $this->attempts(),
                'error'   => $e->getMessage(),
            ]);

            if ($filePath && $disk->exists($filePath)) {
                $disk->delete($filePath);
            }

            throw $e;
        }
    }

    public function failed(Throwable $exception): void
    {
        if ($user = User::find($this->userId)) {
            $user->notify(new SystemNotification(
                'Erro na exportacao',
                'Nao foi possivel gerar o relatorio solicitado.',
                null,
                5,
                []
            ));
        }
    }

    protected function applyBaseConstraints(Builder $query): void
    {
        $query->where(function ($q) {
            $q->whereNull('note_d5')
                ->orWhere('note_d5', '');
        })
            ->where(function ($q) {
                $q->whereNull('is_payed')
                    ->orWhere('is_payed', false);
            })
            ->whereNull('payed_at')
            ->where(function ($q) {
                $q->whereNull('is_archived')
                    ->orWhere('is_archived', false);
            })
            ->where(function ($q) {
                $q->whereNull('isPassive')
                    ->orWhere('isPassive', false);
            })
            ->where(function ($q) {
                $q->whereNull('returned')
                    ->orWhere('returned', false);
            });
    }

    protected function applyFilters(Builder $query): void
    {
        if (!empty($this->params['search'])) {
            $search = $this->formatWithWildcard($this->params['search']);

            $query->where(function ($q) use ($search) {
                $q->whereHas('note', function ($noteQuery) use ($search) {
                    $noteQuery->where('note', $search->type, $search->search);
                })
                    ->orWhereHas('note.Orders', function ($orderQuery) use ($search) {
                        $orderQuery->where('ordem', $search->type, $search->search);
                    })
                    ->orWhere('loc_install', $search->type, $search->search)
                    ->orWhere('pep', $search->type, $search->search)
                    ->orWhere('codify', $search->type, $search->search)
                    ->orWhere('reason', $search->type, $search->search);
            });
        }

        $multiple = array_filter(
            $this->params['multipleSearch'] ?? [],
            fn ($value) => $value !== null && $value !== ''
        );

        if (!empty($multiple)) {
            $query->where(function ($outer) use ($multiple) {
                $outer->whereHas('note', function ($noteQuery) use ($multiple) {
                    $noteQuery->whereIn('note', $multiple);
                })
                    ->orWhereHas('note.Orders', function ($orderQuery) use ($multiple) {
                        $orderQuery->whereIn('ordem', $multiple);
                    })
                    ->orWhereIn('loc_install', $multiple)
                    ->orWhereIn('pep', $multiple)
                    ->orWhereIn('codify', $multiple)
                    ->orWhereIn('reason', $multiple);
            });
        }

        $filters = $this->params['filters'] ?? [];

        if (!empty($filters['company'])) {
            $query->whereIn('company_id', (array) $filters['company']);
        }

        if (!empty($filters['type'])) {
            $query->whereRelation('note', 'type_note', $filters['type']);
        }

        if (!empty($filters['city'])) {
            $query->whereRelation('note', function ($q) use ($filters) {
                $q->whereIn('nexp', (array) $filters['city']);
            });
        }

        if (!empty($filters['desired_between']['start']) && !empty($filters['desired_between']['end'])) {
            $query->whereBetween('dispatch_at', [
                $filters['desired_between']['start'],
                $filters['desired_between']['end'],
            ]);
        }
    }
}
