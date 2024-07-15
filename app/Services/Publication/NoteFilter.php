<?php

namespace App\Services\Publication;

use App\Models\Note;

/** @package  */
class NoteFilter
{
    private $filters;

    public function filter($search, $filterGroup)
    {
        if (!(session_status() == PHP_SESSION_ACTIVE)) {
            session_start();
        }

        if (isset($_SESSION['filter'][$filterGroup])) {
            $this->filters = $_SESSION['filter'][$filterGroup];
        }

        $query = Note::query();

        $query->whereHas('WorkForm')
            ->whereHas('Orders', function ($q) {
                $q->where('statusSist', 'LIKE', 'LIB%')
                    ->whereHas('Operations', function ($sq) {
                        $sq->where('operacao', '0010')
                            ->where('status', 'like', 'CONF%');
                    })
                    ->whereHas('Operations', function ($sq) {
                        $sq->where('operacao', '0020')
                            ->where('status', 'not like', 'CONF%');
                    });
            });

        $query->when($search, function ($q, $s) {
            return $q->where(function ($query) use ($s) {
                $query->where('note', 'like', '%' . $s . '%')
                    ->orWhere('material', 'like', '%' . $s . '%')
                    ->orWhere('numPedido', 'like', '%' . $s . '%')
                    ->orWhere('group2', 'like', '%' . $s . '%');
            });
        })->when(isset($this->filters['rubrica']), function ($q) {
            return $q->where(function ($query) {
                $query->whereIn('rubrica', $this->filters['rubrica'])
                    ->orWhereNull('rubrica');
            });
        })->when(isset($this->filters['city']), function ($q) {
            return $q->where(function ($query) {
                $query->whereIn('lexp', $this->filters['city'])
                    ->orWhereNull('lexp');
            });
        })->when(isset($this->filters['company']), function ($q) {
            return $q->where(function ($query) {
                $query->whereRelation('WorkForm', function ($q) {
                    $q->whereIn('company_id', $this->filters['company']);
                });
            });
        });

        $query->with('Productions.User');


        return $query;
    }
}
