<?php

namespace App\Http\Livewire\Responsible;

use App\Models\Note;
use Livewire\Component;
use Livewire\WithPagination;

class ViabAnalise extends Component
{
    use WithPagination;
    protected $paginationTheme = 'bootstrap';

    public $allCenters = false;
    public $typeNote = '';
    public $search;

    private $filter_group = 'analises';
    private $filter;

    protected $queryString = [
        'typeNote' => ['except' => '', 'as' => 'tipo'],
        'search' => ['except' => '', 'as' => 'busca'],
    ];


    public function getListsProperty()
    {
        if (!(session_status() == PHP_SESSION_ACTIVE)) {
            session_start();
        }

        if (isset($_SESSION['filter'][$this->filter_group])) {
            $this->filter = $_SESSION['filter'][$this->filter_group];
        }



        $query = Note::query();

        $query->where(function ($query) {
            $query->where(function ($qq) {
                $qq->when(!$this->allCenters, function ($q) {
                    $q->whereIn('nstats', [46, 47, 48, 49, 50]);
                })
                ->whereNotIn('rubrica', ['Incoporação'])
                ->where('type_note', 2);
            })
            ->orWhere(function ($qq) {
                $qq->where('type_note', 1)
                ->when(!$this->allCenters, function ($q) {
                    $q->where('centerjob', 'like', 'VIAB%');
                })
                ->orWhere(function ($qq) {
                    $qq->where('centerjob', '')
                    ->where('type_note', 1);
                });
            });
        })
        ->whereHas('Orders', function ($q) {
            if (!$this->allCenters) {
                $q->where('statusSist', 'not like', 'ENTE%')
                  ->where('statusSist', 'not like', 'ENCE%')
                  ->where(function ($q) {
                      $q->whereRelation('Operations', function ($sq) {
                          $sq->where('operacao', '0010')
                             ->where('status', 'like', 'ABER%');
                      });
                  });
            }
        })
        ->whereDoesntHave('Approval')
        ->with([
            'orders' => function ($q) {
                $q->where('statusSist', 'not like', 'ENT%')
                  ->where('statusSist', 'not like', 'ENC%')
                  ->orderBy('ordem');
            },
            'orders.operations' => function ($q) {
                $q->where('operacao', '0010');
            },
        ]);

        if ($this->typeNote) {
            $query->where('type_note', $this->typeNote);
        }

        if (isset($this->filter['city'])) {
            $query->whereIn('lexp', $this->filter['city']);
        }

        return $query
                ->orderBy('type_note', 'DESC')
                ->orderBy('dt_status', 'ASC');
    }


    public function render()
    {
        return view('livewire.responsible.viab-analise', [
            'lists' => $this->lists->paginate(50),
        ]);
    }
}
