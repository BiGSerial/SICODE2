<?php

namespace App\Http\Livewire\Closure\Concerns;

use App\Enum\ClosureOperationalStatus;
use App\Http\Livewire\Concerns\WithListFilters;
use App\Services\Closure\ClosureMetaClassifier;
use App\Services\Closure\Queries\ClosureDispatchListQuery;

/**
 * Filtros das listas "por Ordem" do Encerramento (Despacho e Lista do Serviço): Região, Regional, Município, Status usuário,
 * Empresa (informe), Competência, Rubrica, Status operacional e Responsável, todos pelo WithListFilters (na URL, com cascata).
 * A classe precisa de `listQuery(): ClosureDispatchListQuery`.
 */
trait WithClosureOrderFilters
{
    use WithListFilters;

    /** @return array<string, array{label: string, depends?: array<int, string>}> */
    protected function listFilterDefinitions(): array
    {
        return $this->orderFilterDefinitions();
    }

    /** @return array<string, array{label: string, depends?: array<int, string>}> */
    protected function orderFilterDefinitions(): array
    {
        return [
            'region'     => ['label' => 'Região'],
            'regional'   => ['label' => 'Regional', 'depends' => ['region']],
            'city'       => ['label' => 'Município', 'depends' => ['region', 'regional']],
            'statusUser' => ['label' => 'Status usuário'],
            'company'    => ['label' => 'Empresa (informe)'],
            'competence' => ['label' => 'Competência'],
            'rubric'     => ['label' => 'Rubrica'],
            'status'     => ['label' => 'Status operacional'],
            'assignee'   => ['label' => 'Responsável'],
        ];
    }

    /** @return array<string, array<int, array{v: string, l: string, p?: array<string, string>}>> */
    protected function listFilterOptions(): array
    {
        $query = $this->listQuery();
        $geo   = $query->filterOptions();

        return [
            'region'     => array_map(fn ($o) => ['v' => $o['v'], 'l' => $o['l']], $geo['regions']),
            'regional'   => array_map(fn ($o) => ['v' => $o['v'], 'l' => $o['l'], 'p' => ['region' => $o['g']]], $geo['regionals']),
            'city'       => array_map(fn ($o) => ['v' => $o['v'], 'l' => $o['l'], 'p' => ['region' => $o['g'], 'regional' => $o['r']]], $geo['cities']),
            'statusUser' => array_map(fn ($o) => ['v' => $o['v'], 'l' => $o['l']], $geo['statusUsers']),
            'company'    => $this->rememberFilterOptions('company', fn () => $query->companies()->map(fn ($c) => ['v' => (string) $c->id, 'l' => (string) $c->name])->values()->all()),
            'competence' => collect($query->competenceOptions())->map(fn ($period) => ['v' => $period, 'l' => ClosureMetaClassifier::competenceLabel($period)])->values()->all(),
            'rubric'     => $this->rememberFilterOptions('rubric', fn () => array_map(fn ($r) => ['v' => $r, 'l' => $r], ClosureDispatchListQuery::rubricOptions())),
            'status'     => array_merge(
                [['v' => ClosureDispatchListQuery::NO_STATE, 'l' => 'Não despachada']],
                array_map(fn (ClosureOperationalStatus $case) => ['v' => $case->value, 'l' => $case->label()], ClosureOperationalStatus::cases()),
            ),
            'assignee' => $this->rememberFilterOptions('assignee', fn () => $query->assignees()->map(fn ($u) => ['v' => (string) $u->id, 'l' => (string) $u->name])->values()->all()),
        ];
    }

    /** Filtros do trait no formato que `ClosureDispatchListQuery` lê. */
    protected function orderQueryFilters(): array
    {
        return [
            'statuses'    => $this->filterValues('status'),
            'companyIds'  => $this->filterValues('company'),
            'regions'     => $this->filterValues('region'),
            'regionals'   => $this->filterValues('regional'),
            'cityCodes'   => $this->filterValues('city'),
            'statusUsers' => $this->filterValues('statusUser'),
            'assigneeIds' => $this->filterValues('assignee'),
            'competences' => $this->filterValues('competence'),
            'rubrics'     => $this->filterValues('rubric'),
        ];
    }
}
