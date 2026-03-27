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
        $completedFrom = $this->asStartOfDay($filters['completed_from'] ?? null);
        $completedTo = $this->asEndOfDay($filters['completed_to'] ?? null);
        $companyId = isset($filters['company_id']) ? (string) $filters['company_id'] : '';
        $search = trim((string) ($filters['search'] ?? ''));

        return FiveNote::query()
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
            ->when($dispatchFrom, fn ($q) => $q->where('dispatch_at', '>=', $dispatchFrom))
            ->when($dispatchTo, fn ($q) => $q->where('dispatch_at', '<=', $dispatchTo))
            ->when($completedFrom, fn ($q) => $q->where('completed_at', '>=', $completedFrom))
            ->when($completedTo, fn ($q) => $q->where('completed_at', '<=', $completedTo))
            ->when($companyId !== '', fn ($q) => $q->where('company_id', $companyId))
            ->when($search !== '', function ($q) use ($search) {
                $term = '%' . $search . '%';
                $q->where(function ($sub) use ($term) {
                    $sub->where('note_d5', 'like', $term)
                        ->orWhere('loc_install', 'like', $term)
                        ->orWhere('conjunto', 'like', $term)
                        ->orWhere('pep', 'like', $term)
                        ->orWhere('sintoms', 'like', $term)
                        ->orWhere('reason', 'like', $term)
                        ->orWhere('description', 'like', $term)
                        ->orWhereHas('note', fn ($n) => $n->where('note', 'like', $term))
                        ->orWhereHas('company', fn ($c) => $c->where('name', 'like', $term));
                });
            })
            ->orderByDesc('dispatch_at')
            ->orderByDesc('id');
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
}
