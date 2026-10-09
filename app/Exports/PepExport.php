<?php

namespace App\Exports;

use App\Models\Pep;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\{FromQuery, ShouldAutoSize, WithHeadings, WithMapping, WithProperties};

class PepExport implements FromQuery, WithMapping, WithHeadings, WithProperties, ShouldAutoSize
{
    public function __construct(
        private readonly ?string $search = null,
        private readonly ?string $region = null,
        private readonly ?string $year = null,
        private readonly string $availability = '',
    ) {
    }

    public static function filteredQuery(?string $search, ?string $region, ?string $year, string $availability): Builder
    {
        return Pep::query()
            ->when($search, fn (Builder $query) => $query->where('objeto', 'like', '%' . $search . '%'))
            ->when($region, fn (Builder $query) => $query->where('regiao', $region))
            ->when($year, fn (Builder $query) => $query->where('ano', $year))
            ->when($availability === 'positive', fn (Builder $query) => $query->where('disponivel', '>', 0))
            ->when($availability === 'zero', fn (Builder $query) => $query->where('disponivel', '=', 0))
            ->when($availability === 'negative', fn (Builder $query) => $query->where('disponivel', '<', 0))
            ->orderByDesc('disponivel')
            ->orderBy('objeto');
    }

    public function query(): Builder
    {
        return self::filteredQuery($this->search, $this->region, $this->year, $this->availability);
    }

    public function map($pep): array
    {
        return [
            $pep->objeto,
            $pep->ano,
            $pep->orcamento,
            $pep->real,
            $pep->compromisso,
            $pep->plan_res_ord,
            $pep->disposto,
            $pep->disponivel,
            $pep->regiao,
        ];
    }

    public function headings(): array
    {
        return ['PEP', 'Ano', 'Orçamento', 'Real', 'Compromisso', 'Plano Res. Ordem', 'Disposto', 'Disponível', 'Região'];
    }

    public function properties(): array
    {
        return [
            'creator' => auth()->user()?->name ?? 'SICODE',
            'title'   => 'Saldos PEP',
        ];
    }
}
