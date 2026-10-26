<?php

namespace App\Http\Livewire\Closure\Concerns;

use App\Services\Closure\ClosureBulkSearch;

/**
 * Busca em massa (colar uma lista de Notas/Ordens) para as listas do Encerramento. A tela usa <x-closure.bulk-search> (botão + modal)
 * e <x-closure.bulk-search-result> (resumo). Cada lista aplica `$bulkTerms` no seu filtro e informa em `bulkSearchedValues()` as
 * Ordens/Notas que o filtro devolveu (com os demais filtros atuais), para calcular o que não foi encontrado.
 */
trait WithBulkSearch
{
    /** @var array<int, string> termos da busca em massa ativa */
    public array $bulkTerms = [];

    public string $bulkText = '';

    public bool $bulkOpen = false;

    /** @return array<int, mixed> Ordens e Notas (texto) das linhas que a lista devolve com os filtros atuais */
    abstract protected function bulkSearchedValues(): array;

    public function openBulkSearch(): void
    {
        $this->bulkText = implode("\n", $this->bulkTerms);
        $this->bulkOpen = true;
    }

    public function closeBulkSearch(): void
    {
        $this->bulkOpen = false;
    }

    public function applyBulkSearch(): void
    {
        $this->bulkTerms = ClosureBulkSearch::parse($this->bulkText);
        $this->bulkOpen  = false;

        if (method_exists($this, 'resetPage')) {
            $this->resetPage();
        }
    }

    public function clearBulkSearch(): void
    {
        $this->bulkTerms = [];
        $this->bulkText  = '';
        $this->bulkOpen  = false;

        if (method_exists($this, 'resetPage')) {
            $this->resetPage();
        }
    }

    /** Resumo da busca ativa (null sem busca): total, encontradas e a lista do que não casou. */
    public function bulkReport(): ?array
    {
        if ($this->bulkTerms === []) {
            return null;
        }

        return ClosureBulkSearch::summary($this->bulkTerms, $this->bulkSearchedValues());
    }
}
