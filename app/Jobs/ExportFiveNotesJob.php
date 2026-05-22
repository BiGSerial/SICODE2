<?php

namespace App\Jobs;

use App\Exports\Partner\FiveNotesExport;
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

class ExportFiveNotesJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;
    use WildcardFormmater;

    public array $params;
    public $userId;
    public string $context;
    public $tries   = 2;
    public $backoff = [30, 120];
    public int $timeout = 1200; // 20 min

    public function __construct(array $params, $userId, string $context = 'waiting')
    {
        $this->onQueue('exports');
        $this->params  = $params;
        $this->userId  = $userId;
        $this->context = $context;
    }

    public function handle(): void
    {
        $user = User::with(['Companies', 'Company'])->find($this->userId);

        if (!$user) {
            return;
        }

        $disk = Storage::disk('local');
        $filePath = null;

        try {
            $query = FiveNote::query();

            $this->applyUserScope($query, $user);
            $this->applyBaseConstraints($query);
            $this->applyFilters($query);

            $query->with([
                'note.WorkForm.Orders',
                'note.Orders',
                'company',
            ]);

            $filePath = 'exports/' . now()->format('YmdHis') . '-five-notes-' . $this->context . '.xlsx';

            $disk->makeDirectory('exports');
            Excel::store(new FiveNotesExport(clone $query, $this->context === 'historic'), $filePath, 'local');

            if (!$disk->exists($filePath)) {
                throw new \RuntimeException('Arquivo não foi gerado.');
            }

            $user->notify(new SystemNotification(
                'Exportação concluída!',
                $this->context === 'historic'
                    ? 'Seu relatório do histórico de D5 está pronto para download.'
                    : 'Sua lista de D5 pendentes está pronta para download.',
                Storage::url($filePath),
                4,
                []
            ));
        } catch (Throwable $e) {
            Log::error('ExportFiveNotesJob falhou', [
                'user_id' => $this->userId,
                'context' => $this->context,
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
                'Erro na exportação',
                'Não foi possível gerar o relatório solicitado.',
                null,
                5,
                []
            ));
        }
    }

    protected function applyUserScope(Builder $query, User $user): void
    {
        if ($user->superadm) {
            return;
        }

        $companyIds       = $user->Companies?->pluck('id')->filter()->all() ?? [];
        $defaultCompanyId = $user->Company?->id;

        if ($companyIds) {
            $query->where(function ($q) use ($companyIds, $defaultCompanyId) {
                $q->whereIn('company_id', $companyIds);

                if ($defaultCompanyId) {
                    $q->orWhere('company_id', $defaultCompanyId);
                }
            });

            return;
        }

        if ($defaultCompanyId) {
            $query->where('company_id', $defaultCompanyId);

            return;
        }

        $query->whereRaw('1 = 0');
    }

    protected function applyBaseConstraints(Builder $query): void
    {
        $query->where('visible_partner', true);

        if ($this->context === 'historic') {
            $query->where('is_completed', true)
                ->orderByDesc('completed_at');
        } else {
            $query->where('is_completed', false)
                ->orderBy('dispatch_at');
        }
    }

    protected function applyFilters(Builder $query): void
    {
        $passiveFilter = $this->params['passiveFilter'] ?? 'current';

        if ($passiveFilter === 'current') {
            $query->where('isPassive', false);
        } elseif ($passiveFilter === 'passive') {
            $query->where('isPassive', true);
        }

        if (!empty($this->params['search'])) {
            $search = $this->formatWithWildcard($this->params['search']);

            $query->where(function ($q) use ($search) {
                $q->where('note_d5', $search->type, $search->search)
                    ->orWhere('pep', $search->type, $search->search)
                    ->orWhere('loc_install', $search->type, $search->search)
                    ->orWhereRelation('Note', function ($noteQuery) use ($search) {
                        $noteQuery->where('note', $search->type, $search->search);
                    })
                    ->orWhereRelation('Note.Orders', function ($orderQuery) use ($search) {
                        $orderQuery->where('ordem', $search->type, $search->search);
                    });
            });
        }

        $multiple = array_filter(
            $this->params['multipleSearch'] ?? [],
            fn ($value) => $value !== null && $value !== ''
        );

        if (!empty($multiple)) {
            $query->where(function ($outer) use ($multiple) {
                foreach ($multiple as $value) {
                    $search = $this->formatWithWildcard($value);

                    $outer->orWhere(function ($or) use ($search) {
                        $or->where('note_d5', $search->type, $search->search)
                            ->orWhere('pep', $search->type, $search->search)
                            ->orWhere('loc_install', $search->type, $search->search)
                            ->orWhereRelation('Note', function ($noteQuery) use ($search) {
                                $noteQuery->where('note', $search->type, $search->search);
                            })
                            ->orWhereRelation('Note.Orders', function ($orderQuery) use ($search) {
                                $orderQuery->where('ordem', $search->type, $search->search);
                            });
                    });
                }
            });
        }

        if (!empty($this->params['startDate'])) {
            $query->whereDate('dispatch_at', '>=', $this->params['startDate']);
        }

        if (!empty($this->params['endDate'])) {
            $query->whereDate('dispatch_at', '<=', $this->params['endDate']);
        }

        if (!empty($this->params['month'])) {
            $query->whereMonth('dispatch_at', $this->params['month']);
        }
    }
}
