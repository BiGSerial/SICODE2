<?php

namespace App\Services\Reports;

use App\Models\FiveNote;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class FiveNoteReportService
{
    public function paginate(array $filters, int $perPage = 30): LengthAwarePaginator
    {
        $paginator = $this->baseQuery($filters)->paginate($perPage);

        $paginator->setCollection(
            $paginator->getCollection()->map(fn (FiveNote $fiveNote) => $this->mapFiveNote($fiveNote))
        );

        return $paginator;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function exportRows(array $filters): array
    {
        return $this->baseQuery($filters)
            ->get()
            ->map(fn (FiveNote $fiveNote) => $this->mapFiveNote($fiveNote))
            ->values()
            ->all();
    }

    public function summarize(array $filters): array
    {
        $query = $this->baseQuery($filters);

        $total = (clone $query)->count();
        $passive = (clone $query)->where('isPassive', true)->count();
        $completed = (clone $query)->whereNotNull('completed_at')->count();

        return [
            'total' => (int) $total,
            'passive' => (int) $passive,
            'completed' => (int) $completed,
        ];
    }

    private function baseQuery(array $filters)
    {
        $dispatchFrom = $this->asStartOfDay($filters['dispatch_from'] ?? null);
        $dispatchTo = $this->asEndOfDay($filters['dispatch_to'] ?? null);
        $companyId = isset($filters['company_id']) ? (string) $filters['company_id'] : '';
        $passiveMode = $this->filterScalar($filters, 'passive_mode', 'both');
        $search = trim((string) ($filters['search'] ?? ''));
        $openOnly = $this->filterScalar($filters, 'open_only', '0') === '1';
        $directTerms = $this->normalizeTerms($filters['direct_terms'] ?? []);
        if ($search !== '') {
            $directTerms[] = $search;
        }
        $directTerms = array_values(array_unique(array_filter($directTerms)));
        $hasDirectSearch = count($directTerms) > 0;

        $query = FiveNote::query()
            ->with([
                'note:id,note',
                'company:id,name',
                'productions' => function ($query) {
                    $query->with([
                        'User:id,name',
                        'Service:id,uuid,service',
                    ])->select([
                        'productions.id',
                        'productions.user_id',
                        'productions.service_id',
                        'productions.dispatch_at',
                        'productions.created_at',
                    ]);
                },
            ])
            ->orderByDesc('dispatch_at')
            ->orderByDesc('id');

        if ($hasDirectSearch) {
            $query->where(function ($scope) use ($directTerms) {
                $scope->whereIn('note_d5', $directTerms)
                    ->orWhereHas('note', fn ($noteQuery) => $noteQuery->whereIn('note', $directTerms));
            });

            return $query;
        }

        if (!$openOnly) {
            $query->when($dispatchFrom, fn ($q) => $q->where('dispatch_at', '>=', $dispatchFrom))
                ->when($dispatchTo, fn ($q) => $q->where('dispatch_at', '<=', $dispatchTo));
        } else {
            $query->where('is_archived', false);
        }

        $query->when($companyId !== '', fn ($q) => $q->where('company_id', $companyId))
            ->when($passiveMode === 'passive', fn ($q) => $q->where('isPassive', true))
            ->when($passiveMode === 'meta', function ($q) {
                $q->where(function ($scope) {
                    $scope->whereNull('isPassive')->orWhere('isPassive', false);
                });
            });

        return $query;
    }

    /**
     * @return array<string, mixed>
     */
    private function mapFiveNote(FiveNote $fiveNote): array
    {
        $fiscalizationProduction = $this->pickFiscalizationProduction($fiveNote->productions);
        $paymentProduction = $this->pickPaymentProduction($fiveNote->productions);

        return [
            'nota_d5' => (string) ($fiveNote->note_d5 ?? '---'),
            'nota_ov' => (string) ($fiveNote->note?->note ?? '---'),
            'empresa_parceira' => (string) ($fiveNote->company?->name ?? '---'),
            'dispatch_at' => $this->formatDate($fiveNote->dispatch_at),
            'completed_at' => $this->formatDate($fiveNote->completed_at),
            'supervisioned_at' => $this->formatDate($fiveNote->supervisioned_at),
            'payed_at' => $this->formatDate($fiveNote->payed_at),
            'fiscalizado_por' => (string) ($fiscalizationProduction?->User?->name ?? '---'),
            'pago_por' => (string) ($paymentProduction?->User?->name ?? '---'),
            'passivo' => $fiveNote->isPassive ? 'SIM' : 'NAO',
            'local_instalacao' => (string) ($fiveNote->loc_install ?? '---'),
            'conjunto' => (string) ($fiveNote->conjunto ?? '---'),
            'pep' => (string) ($fiveNote->pep ?? '---'),
            'e_pep' => (string) ($fiveNote->e_pep ?? '---'),
            'codificacao' => (string) ($fiveNote->codify ?? '---'),
            'sintomas' => (string) ($fiveNote->sintoms ?? '---'),
            'motivo' => (string) ($fiveNote->reason ?? '---'),
            'descricao' => (string) ($fiveNote->description ?? '---'),
            'responsavel_registro' => (string) ($fiveNote->name ?? '---'),
            'criado_em' => $this->formatDate($fiveNote->created_at),
            'atualizado_em' => $this->formatDate($fiveNote->updated_at),
        ];
    }

    private function pickFiscalizationProduction(Collection $productions)
    {
        return $productions
            ->filter(function ($production) {
                $service = $this->normalizeServiceName((string) ($production->Service?->service ?? ''));
                return Str::contains($service, 'fiscalizacao');
            })
            ->sortByDesc(fn ($production) => $production->dispatch_at ?? $production->created_at ?? $production->id)
            ->first();
    }

    private function pickPaymentProduction(Collection $productions)
    {
        return $productions
            ->filter(function ($production) {
                $service = $this->normalizeServiceName((string) ($production->Service?->service ?? ''));
                return Str::contains($service, 'pagamento');
            })
            ->sortBy(fn ($production) => $production->dispatch_at ?? $production->created_at ?? $production->id)
            ->first();
    }

    private function normalizeServiceName(string $name): string
    {
        return Str::of($name)
            ->ascii()
            ->lower()
            ->replace('-', ' ')
            ->replace('_', ' ')
            ->squish()
            ->toString();
    }

    private function asStartOfDay(?string $date): ?Carbon
    {
        if (!$date) {
            return null;
        }

        return Carbon::parse($date)->startOfDay();
    }

    private function asEndOfDay(?string $date): ?Carbon
    {
        if (!$date) {
            return null;
        }

        return Carbon::parse($date)->endOfDay();
    }

    private function formatDate($value): string
    {
        if (!$value) {
            return '---';
        }

        return Carbon::parse($value)->format('d/m/Y H:i');
    }

    private function filterScalar(array $filters, string $key, string $default = ''): string
    {
        $value = $filters[$key] ?? $default;

        if (is_array($value)) {
            $value = reset($value);
        }

        if ($value === null || $value === '') {
            return $default;
        }

        return (string) $value;
    }

    private function normalizeTerms($terms): array
    {
        if (is_string($terms)) {
            $terms = [$terms];
        }

        if (!is_array($terms)) {
            return [];
        }

        $normalized = [];

        foreach ($terms as $term) {
            $value = trim((string) $term);
            if ($value !== '') {
                $normalized[] = $value;
            }
        }

        return array_values(array_unique($normalized));
    }
}
