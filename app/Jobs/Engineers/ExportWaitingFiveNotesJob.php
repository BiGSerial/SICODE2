<?php

namespace App\Jobs\Engineers;

use App\Exports\Engineers\{D5ConsultExport, D5ConsultListSheet};
use App\Models\{Company, FiveNote, Service, User};
use App\Notifications\SystemNotification;
use App\Services\D5\D5StageResolver;
use App\Services\Engineers\D5ConsultAnalytics;
use App\Services\Files\ExportStorage;
use App\Support\Notifications\UserNotificationData;
use App\Traits\WildcardFormmater;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\{InteractsWithQueue, SerializesModels};
use Illuminate\Support\Facades\{DB, Log, URL};
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

    public $tries = 2;

    public $backoff = [30, 120];

    public int $timeout = 1200; // 20 min

    public function __construct(array $params, string $userId)
    {
        $this->onQueue('exports');
        $this->params = $params;
        $this->userId = $userId;
    }

    public function handle(ExportStorage $exports, D5ConsultAnalytics $analytics): void
    {
        $user = User::with(['Companies', 'Company'])->find($this->userId);

        if (!$user) {
            return;
        }

        $disk     = $exports->disk();
        $filePath = null;

        try {
            // Mesma base da lista, sem a etapa: dela saem os números do resumo e das abas (iguais aos da tela).
            $scoped = FiveNote::query();

            $this->applyUserScope($scoped, $user);
            $this->applyBaseConstraints($scoped);

            if ($this->hideFinalized()) {
                $scoped->whereNot(fn ($finalized) => D5StageResolver::applyFilter($finalized, D5StageResolver::FINALIZED));
            }

            $filtered = clone $scoped;
            $this->applyFilters($filtered, false);

            $query = clone $scoped;
            $this->applyFilters($query);

            $query->with([
                'note.WorkForm.Orders',
                'note.Orders',
                'note.Productions.User',
                'note.Productions.Company',
                'company',
                'WorkReport:id,current_status_key,current_status_label,current_status_class',
            ]);

            $fileName = now()->format('YmdHis') . "_{$this->userId}_consulta_geral_d5.xlsx";
            $filePath = 'exports/' . $fileName;

            $exports->ensureDirectory();

            $export = new D5ConsultExport(
                $analytics->snapshot($filtered),
                new D5ConsultListSheet(clone $query, $this->shouldUseHistoricExport(), $this->exportOptions()),
                (string) $user->name,
                $this->filterLabels(),
                $this->auditRows($user),
                now(),
                !$this->hideFinalized(),
            );

            if (!Excel::store($export, $filePath, $exports->diskName())) {
                throw new \RuntimeException('Arquivo nao foi gerado.');
            }

            $user->notify(new SystemNotification(new UserNotificationData(
                title: 'Exportação concluída!',
                message: 'A consulta geral de D5 está pronta para download em Excel.',
                link: URL::temporarySignedRoute('exports.download', now()->addDays(7), ['user' => (string) $this->userId, 'file' => $fileName]),
                status: 'download',
            )));
        } catch (Throwable $e) {
            Log::error('ExportWaitingFiveNotesJob falhou', [
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
            $user->notify(new SystemNotification(new UserNotificationData(
                title: 'Erro na exportação',
                message: 'Não foi possível gerar o Excel da consulta geral de D5. Tente novamente mais tarde.',
                status: 'error',
            )));
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
        $query->where(function ($base) {
            $base->where('visible_partner', true)
                ->orWhere(function ($inner) {
                    $inner->where(function ($noteD5) {
                        $noteD5->whereNull('note_d5')
                            ->orWhere('note_d5', '');
                    })->whereExists(function ($exists) {
                        $exists->select(DB::raw(1))
                            ->from('timeline_events as te')
                            ->whereColumn('te.five_note_id', 'five_notes.id')
                            ->where('te.event_type', 'd5_created_from_supervision');
                    });
                });
        });
    }

    protected function applyFilters(Builder $query, bool $withStage = true): void
    {
        $statusFilter = $this->params['statusFilter'] ?? '';

        if ($withStage && $statusFilter !== '') {
            D5StageResolver::applyFilter($query, $statusFilter);
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
        $multiD5   = array_filter($this->params['multiD5'] ?? [], fn ($value) => $value !== null && $value !== '');

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

        $this->applyPassiveModeFilter($query, $filtersState);

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

        $this->applyDesiredBetweenFilter($query, $filtersState);

        $query->orderByRaw('CASE WHEN completed_at IS NULL THEN 1 ELSE 0 END');
        $query->orderBy('completed_at', 'asc');
        $query->orderBy('dispatch_at', 'asc');
    }

    protected function applyDesiredBetweenFilter(Builder $query, array $filtersState): void
    {
        if (empty($filtersState['desired_between']) || !is_array($filtersState['desired_between'])) {
            return;
        }

        $dateRange = $filtersState['desired_between'];

        if (!isset($dateRange['start'], $dateRange['end'])) {
            return;
        }

        $periodColumn = $this->filterScalar($filtersState, 'period_column', 'dispatch');

        if ($periodColumn === 'completed') {
            $query->whereBetween('completed_at', [$dateRange['start'], $dateRange['end']]);

            return;
        }

        if ($periodColumn === 'both') {
            $query->where(function ($scope) use ($dateRange) {
                $scope->whereBetween('dispatch_at', [$dateRange['start'], $dateRange['end']])
                    ->orWhereBetween('completed_at', [$dateRange['start'], $dateRange['end']]);
            });

            return;
        }

        $query->whereBetween('dispatch_at', [$dateRange['start'], $dateRange['end']]);
    }

    protected function applyPassiveModeFilter(Builder $query, array $filtersState): void
    {
        $passiveMode = $this->filterScalar($filtersState, 'passive_mode', 'both');

        if ($passiveMode === 'passive') {
            $query->where('isPassive', true);

            return;
        }

        if ($passiveMode === 'meta') {
            $query->where(function ($scope) {
                $scope->whereNull('isPassive')
                    ->orWhere('isPassive', false);
            });
        }
    }

    protected function filterScalar(array $filtersState, string $key, string $default = ''): string
    {
        $value = $filtersState[$key] ?? $default;

        if (is_array($value)) {
            $value = reset($value);
        }

        if ($value === null || $value === '') {
            return $default;
        }

        return (string) $value;
    }

    /** Exportação da tela "Aguardando resolução": sem as D5 finalizadas. */
    protected function hideFinalized(): bool
    {
        return (bool) ($this->params['hideFinalized'] ?? false);
    }

    protected function shouldUseHistoricExport(): bool
    {
        return ($this->params['statusFilter'] ?? '') === 'finalizado';
    }

    protected function exportOptions(): array
    {
        return [
            'd5_tracking'              => true,
            'statusFilter'             => (string) ($this->params['statusFilter'] ?? ''),
            'fiscalization_service_id' => Service::whereIn('service', ['Fiscalizacao', 'Fiscalização'])->value('uuid'),
            'payment_service_id'       => Service::where('service', 'Pagamento')->value('uuid'),
        ];
    }

    /** Filtros em texto, para o resumo do Excel. @return array<string, string> */
    protected function filterLabels(): array
    {
        $state = $this->params['filtersState'] ?? [];
        $none  = 'Todos';

        $names = fn (string $model, array $ids, string $column = 'name') => $ids ? $model::query()->whereIn('id', $ids)->pluck($column)->implode(', ') : $none;

        $stage = (string) ($this->params['statusFilter'] ?? '');
        $multi = array_merge($this->params['multiNote'] ?? [], $this->params['multiD5'] ?? []);
        $range = $state['desired_between'] ?? null;

        return [
            'D5 finalizadas'    => $this->hideFinalized() ? 'Fora desta consulta' : 'Incluídas',
            'Etapa da lista'    => $stage !== '' ? (D5ConsultAnalytics::STAGE_LABELS[$stage] ?? $stage) : 'Todas (os números do resumo sempre incluem todas)',
            'Busca'             => !empty($this->params['search']) ? (string) $this->params['search'] : '—',
            'Busca múltipla'    => $multi ? count(array_unique($multi)) . ' termo(s)' : '—',
            'Empreiteiras'      => $names(Company::class, (array) ($state['company'] ?? [])),
            'Tipo'              => match ((string) $this->filterScalar($state, 'type')) {
                '2'     => 'OV',
                '1'     => 'NOTA',
                default => $none,
            },
            'Meta / Passivo'    => match ($this->filterScalar($state, 'passive_mode', 'both')) {
                'passive' => 'Passivo',
                'meta'    => 'Meta',
                default   => 'Meta + Passivo',
            },
            'Municípios'        => !empty($state['city']) ? count((array) $state['city']) . ' selecionado(s)' : $none,
            'Rubricas'          => !empty($state['rubrica']) ? implode(', ', (array) $state['rubrica']) : $none,
            'Período por'       => match ($this->filterScalar($state, 'period_column', 'dispatch')) {
                'completed' => 'Conclusão',
                'both'      => 'Despacho ou conclusão',
                default     => 'Despacho',
            },
            'Período'           => is_array($range) && isset($range['start'], $range['end']) ? $range['start'] . ' a ' . $range['end'] : 'Sem período',
            'Dias parada'       => 'Dias desde a entrada da D5 na etapa atual; atrasada = mais de ' . D5ConsultAnalytics::LATE_DAYS . ' dias',
        ];
    }

    /** Aba "Controle Exportacao": quem pediu, quando e os filtros em texto. @return array<string, string> */
    protected function auditRows(User $user): array
    {
        return [
            'Usuário'   => (string) $user->name,
            'E-mail'    => (string) $user->email,
            'Data/hora' => now()->format('d/m/Y H:i:s'),
            'Tipo'      => 'Consulta geral D5',
        ] + $this->filterLabels();
    }
}
