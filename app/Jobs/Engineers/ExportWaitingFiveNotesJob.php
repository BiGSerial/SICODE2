<?php

namespace App\Jobs\Engineers;

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

class ExportWaitingFiveNotesJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;
    use WildcardFormmater;

    public array $params;
    public string $userId;

    public function __construct(array $params, string $userId)
    {
        $this->params = $params;
        $this->userId = $userId;
    }

    public function handle(): void
    {
        $user = User::with(['Companies', 'Company'])->find($this->userId);

        if (!$user) {
            return;
        }

        $disk = Storage::disk('local');

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

            $filePath = 'exports/' . now()->format('YmdHis') . '-five-notes-general.xlsx';

            $disk->makeDirectory('exports');
            Excel::store(new FiveNotesExport(clone $query, $this->shouldUseHistoricExport()), $filePath, 'local');

            if (!$disk->exists($filePath)) {
                throw new \RuntimeException('Arquivo nao foi gerado.');
            }

            $user->notify(new SystemNotification(
                'Exportacao concluida!',
                'Sua consulta geral de D5 esta pronta para download.',
                Storage::url($filePath),
                4,
                []
            ));
        } catch (Throwable $e) {
            Log::error('ExportWaitingFiveNotesJob falhou', [
                'user_id' => $this->userId,
                'params'  => $this->params,
                'error'   => $e->getMessage(),
            ]);

            $user->notify(new SystemNotification(
                'Erro na exportacao',
                'Nao foi possivel gerar o relatorio solicitado.',
                null,
                5,
                []
            ));

            throw $e;
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
    }

    protected function applyFilters(Builder $query): void
    {
        $statusFilter = $this->params['statusFilter'] ?? '';

        switch ($statusFilter) {
            case 'aguardando_fornecedor':
                $query->where('is_completed', false)
                    ->where('is_archived', false);
                break;
            case 'aguardando_fiscalizacao':
                $query->where('is_completed', true)
                    ->where('is_supervisioned', false)
                    ->where('is_archived', false);
                break;
            case 'aguardando_pagamento':
                $query->where('is_supervisioned', true)
                    ->where('is_archived', false);
                break;
            case 'finalizado':
                $query->where('is_archived', true);
                break;
        }

        if (!empty($this->params['search'])) {
            $search = $this->formatWithWildcard($this->params['search']);

            $query->where(function ($q) use ($search) {
                $q->whereHas('note', function ($noteQuery) use ($search) {
                    $noteQuery->where('note', $search->type, $search->search);
                })
                    ->orWhere('note_d5', $search->type, $search->search)
                    ->orWhere('reason', $search->type, $search->search)
                    ->orWhere('codify', $search->type, $search->search)
                    ->orWhereHas('company', function ($companyQuery) use ($search) {
                        $companyQuery->where('name', $search->type, $search->search);
                    });
            });
        }

        $multiNote = array_filter($this->params['multiNote'] ?? [], fn ($value) => $value !== null && $value !== '');
        $multiD5 = array_filter($this->params['multiD5'] ?? [], fn ($value) => $value !== null && $value !== '');

        if ($multiNote || $multiD5) {
            $query->where(function ($q) use ($multiNote, $multiD5) {
                if ($multiNote) {
                    $q->whereHas('note', function ($noteQuery) use ($multiNote) {
                        $noteQuery->whereIn('note', $multiNote);
                    });
                }

                if ($multiD5) {
                    if ($multiNote) {
                        $q->orWhereIn('note_d5', $multiD5);
                    } else {
                        $q->whereIn('note_d5', $multiD5);
                    }
                }
            });
        }

        $filtersState = $this->params['filtersState'] ?? [];

        if (!empty($filtersState['company']) && is_array($filtersState['company'])) {
            $query->whereIn('company_id', $filtersState['company']);
        }

        if (!empty($filtersState['type'])) {
            $query->whereRelation('note', 'type_note', $filtersState['type']);
        }

        if (!empty($filtersState['city']) && is_array($filtersState['city'])) {
            $query->whereRelation('note', function ($noteQuery) use ($filtersState) {
                $noteQuery->whereIn('nexp', $filtersState['city']);
            });
        }

        if (!empty($filtersState['rubrica']) && is_array($filtersState['rubrica'])) {
            $query->whereRelation('note', function ($noteQuery) use ($filtersState) {
                $noteQuery->whereIn('rubrica', $filtersState['rubrica']);
            });
        }

        if (!empty($filtersState['desired_between']) && is_array($filtersState['desired_between'])) {
            $dateRange = $filtersState['desired_between'];
            if (isset($dateRange['start'], $dateRange['end'])) {
                $query->whereBetween('dispatch_at', [$dateRange['start'], $dateRange['end']]);
            }
        }

        $query->orderBy('dispatch_at', 'asc');
    }

    protected function shouldUseHistoricExport(): bool
    {
        return ($this->params['statusFilter'] ?? '') === 'finalizado';
    }
}
