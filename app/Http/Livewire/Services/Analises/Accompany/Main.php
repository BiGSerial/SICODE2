<?php

namespace App\Http\Livewire\Services\Analises\Accompany;

use App\Http\Livewire\Services\Concerns\IsActivityAccompanyList;
use Livewire\{Component, WithPagination};

/**
 * Acompanhamento de Análises (e Desenho) (Serviços): a fila de trabalho do usuário. A tela, os filtros e as ações são os da base
 * `IsActivityAccompanyList`; o que é característico da atividade (formulário, limite de pausa, encerramento em massa, prazo) está em
 * `ActivityAccompanyProfile`. Padrão: docs/PADRAO_LISTAS_OPERACIONAIS.md.
 */
class Main extends Component
{
    use WithPagination;
    use IsActivityAccompanyList;

    protected $paginationTheme = 'bootstrap';

    public function render()
    {
        return view('livewire.services.accompany.note-list', $this->accompanyViewData());
    }
}
