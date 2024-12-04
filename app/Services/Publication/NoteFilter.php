<?php

namespace App\Services\Publication;

use App\Models\Note;

/** @package  */
class NoteFilter
{
    private $filters;
    private $btzeroform;

    public function filter($search, $filterGroup, $btzeroform = true)
    {

        $this->btzeroform = $btzeroform;

        if (!(session_status() == PHP_SESSION_ACTIVE)) {
            session_start();
        }

        if (isset($_SESSION['filter'][$filterGroup])) {
            $this->filters = $_SESSION['filter'][$filterGroup];
        }

        $query = Note::query();

        $query->where(function ($q) {
            $q->whereHas('WorkForm', function ($sq) {
                $sq->where('rejected', false);
            });

            if ($this->btzeroform) {
                $q->orWhereHas('RamalForm');
            }
        });

        $query->whereHas('Orders', function ($q) {
            $q->where('statusSist', 'LIKE', 'LIB%')
                ->whereHas('Operations', function ($sq) {
                    $sq->where('operacao', '0010')
                        ->where('status', 'like', 'CONF%');
                })
                ->whereHas('Operations', function ($sq) {
                    $sq->where('operacao', '0020')
                        ->where(function ($q) {
                            $q->where('status', 'like', 'LIB%')
                                ->orWhere('status', 'like', 'CNPA%')
                                ->orWhere('status', 'like', 'JBFI LIB%');
                        });
                });
        });

        if ($search) {
            $query->where(function ($query) use ($search) {
                $query->where('note', 'like', '%' . $search . '%')
                    ->orWhere('material', 'like', '%' . $search . '%')
                    ->orWhere('numPedido', 'like', '%' . $search . '%')
                    ->orWhere('group2', 'like', '%' . $search . '%');
            });
        }

        if (isset($this->filters['rubrica'])) {
            $query->where(function ($query) {
                $query->whereIn('rubrica', $this->filters['rubrica'])
                    ->orWhereNull('rubrica');
            });
        }

        if (isset($this->filters['city'])) {
            $query->where(function ($query) {
                $query->whereIn('lexp', $this->filters['city'])
                    ->orWhereNull('lexp');
            });
        }

        if (isset($this->filters['company'])) {
            $query->whereRelation('WorkForm', function ($q) {
                $q->whereIn('company_id', $this->filters['company']);
            });
        }



        $query->with('Productions.User', 'WorkForm', 'RamalForm');



        return $query;
    }
}
