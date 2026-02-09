<?php

namespace App\Repositories;

use App\Models\Note;
use Illuminate\Database\Eloquent\Builder;

class SupervisionRepository
{
    /**
     * Retorna a consulta base para obter notas.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function getBaseQuery(): Builder
    {
        return Note::query()
            ->excludeCanceledFullDone()
            ->leftjoin('work_reports', 'work_reports.note_id', '=', 'notes.id')
            ->where(function ($q) {
                $q->orWhere(function ($q) {
                    $q->whereHas('FiveNote', function ($q2) {
                        $q2->where('is_supervisioned', false)
                            ->where('is_completed', true);
                    });
                })
                ->orwhere(function ($sq) {
                    $sq->whereHas('Partials', function ($q2) {
                        $q2->where('supervision', false)
                            ->where('allow', true);
                    })
                    ->whereDoesntHave('WorkForm');
                })->orWhere(function ($sq) {
                    $sq->where(function ($q) {
                        $q->whereHas('WorkForm', function ($sq) {
                            $sq->where('rejected', false);
                        })
                        ->where(function ($q) {
                            $q->whereHas('Orders', function ($q) {
                                $q->where('statusSist', 'LIKE', 'LIB%')
                                ->where(function ($sq) {
                                    $sq->where(function ($q1) {
                                        $q1->whereHas('Operations', function ($sq) {
                                            $sq->where('operacao', '0030')
                                            ->where(function ($sq) {
                                                $sq->where('status', 'like', 'CNPA%')
                                                ->orWhere('status', 'like', 'LIB%')
                                                ->orwhere('status', 'like', 'JBFI LIB%');
                                            });
                                        })->whereHas('Operations', function ($sq) {
                                            $sq->where('operacao', '0040')
                                            ->where(function ($sq) {
                                                $sq->where('status', 'like', 'LIB%')
                                                ->orwhere('status', 'like', 'JBFI LIB%');
                                            });
                                        });
                                    })
                                    ->orWhere(function ($q2) {
                                        $q2->whereHas('Operations', function ($sq) {
                                            $sq->where('operacao', '0010')
                                            ->where('status', 'like', 'CONF%');
                                        })->whereHas('Operations', function ($sq) {
                                            $sq->where('operacao', '0030')
                                            ->where('status', 'like', 'CONF%');
                                        })->whereHas('Operations', function ($sq) {
                                            $sq->where('operacao', '0040')
                                            ->where('status', 'like', 'LIB%');
                                        });
                                    });

                                });
                            });
                        });
                    });
                });
            })
            ->select('notes.*', 'work_reports.created_at as work_dt_created')
            ->orderBy('work_dt_created', 'ASC');
    }
}
