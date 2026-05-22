<?php

namespace App\Services\Publication;

use App\Models\Note;

/** @package  */
class NoteFilter
{
    private $filters;
    private $btzeroform;

    public function filter($filterGroup, $btzeroform = true)
    {

        $this->btzeroform = $btzeroform;

        if (!(session_status() == PHP_SESSION_ACTIVE)) {
            if (!session()->isStarted()) { session()->start(); }
        }

        if (isset($_SESSION['filter'][$filterGroup])) {
            $this->filters = $_SESSION['filter'][$filterGroup];
        }

        $query = Note::query();

        $query->where(function ($q) {

            $q->where(function ($wq) {
                $wq->whereHas('WorkForm', function ($sq) {
                    $sq->where('rejected', false);
                })->orWhere(function ($sq) {
                    if ($this->btzeroform) {
                        $sq->doesntHave('WorkForm')
                       ->whereHas('RamalForm');
                    }
                });
            });
        });

        $query->whereHas('Orders', function ($q) {
            $q->where(function ($sq) {
                $sq->where('statusSist', 'LIKE', 'LIB%')
                    ->orWhere('statusSist', 'LIKE', 'ABER%');
            })
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






        return $query;
    }
}
