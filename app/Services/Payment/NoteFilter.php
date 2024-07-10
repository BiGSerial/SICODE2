<?php

namespace App\Services\Payment;

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

        $query->whereHas('WorkForm', function ($q) {
            $q->when(isset($this->filters['company']), function ($sq) {
                return $sq->where(function ($query) {
                    $query->whereIn('company_id', $this->filters['company'])
                        ->orWhereNull('company_id');
                });
            });
        })
            ->whereHas('Orders', function ($q) {
                $q->where('statusSist', 'LIKE', 'LIB%')
                    ->whereHas('Operations', function ($sq) {
                        $sq->where('operacao', '0030')
                            ->where('status', 'like', 'CONF%');
                    })
                    ->whereHas('Operations', function ($sq) {
                        $sq->where('operacao', '0040')
                            ->where(function ($q) {
                                $q->where('status', 'like', 'CONF%')
                                    ->orWhere('status', 'like', 'CNPA%');
                            });
                    })
                    ->whereHas('Operations', function ($sq) {
                        $sq->where('operacao', '0050')
                            ->where('status', 'like', 'LIB%');
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
        });;

        $query->with('Productions.User');


        return $query;
    }
}
