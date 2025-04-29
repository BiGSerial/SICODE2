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

        $query->where(function ($q1) {

            $q1->Where(function ($q2) {
                $q2->whereHas('WorkForm', function ($q) {
                    $q->when(isset($this->filters['company']), function ($sq) {
                        return $sq->where('rejected', false)
                            ->where(function ($query) {
                                $query->whereIn('company_id', $this->filters['company'])
                                    ->orWhereNull('company_id');
                            });
                    });
                })->whereHas('Orders', function ($q) {
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
                            ->where(function ($q) {
                                $q->where('status', 'like', 'LIB%')
                                    ->orWhere('status', 'like', 'CNPA%')
                                    ->orWhere('status', 'like', 'JBFI LIB%');
                            });
                        });
                });
            })->orWhere(function ($sq) {
                $sq->whereHas('Partials', function ($q2) {
                    $q2->where('supervision', true)
                        ->where('payment', false);
                    $q2->when(isset($this->filters['company']), function ($sq) {
                        return $sq->where(function ($query) {
                            $query->whereIn('company_id', $this->filters['company'])
                                ->orWhereNull('company_id');
                        });
                    });
                })
                ->whereDoesntHave('WorkForm');
            });



        });

        if ($search) {

            $query->where(function ($q) use ($search) {
                $q->where('note', 'like', '%' . $search . '%')
                    ->orWhereRelation('Orders', 'ordem', 'like', '%' . $search . '%');
            });
        }

        $query->when(isset($this->filters['rubrica']), function ($q) {
            return $q->where(function ($query) {
                $query->whereIn('rubrica', $this->filters['rubrica'])
                    ->orWhereNull('rubrica');
            });
        })->when(isset($this->filters['city']), function ($q) {
            return $q->where(function ($query) {
                $query->whereIn('lexp', $this->filters['city'])
                    ->orWhereNull('lexp');
            });
        });

        $query->with('Productions.User');


        return $query;
    }
}
