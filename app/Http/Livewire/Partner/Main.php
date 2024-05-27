<?php

namespace App\Http\Livewire\Partner;

use App\Models\Note;
use Livewire\Component;

class Main extends Component
{
    public function getCountCompletedProperty()
    {
        return Note::whereRelation('Viabilities', function ($q) {
            return $q->where('completed', true)
                    ->whereYear('completed_at', date('Y'))
                    ->whereMonth('completed_at', date('m'));


            if (!Auth()->User()->superadm) {

                $companyId = auth()->user()->Employee->Contract->Company->id ?? null;

                if ($companyId) {
                    $q->where('company_id', $companyId);
                } else {
                    $q->where('company_id', null);
                }

            }


        })->count();
    }


    public function getEvolutionCompletedProperty()
    {
        $actual = $this->countCompleted;
        $past = Note::whereRelation('Viabilities', function ($q) {
            return $q->where('completed', true)
                    ->whereYear('completed_at', date('Y'))
                    ->whereMonth('completed_at', date('m') - 1);

            if (!Auth()->User()->superadm) {

                $companyId = auth()->user()->Employee->Contract->Company->id ?? null;

                if ($companyId) {
                    $q->where('company_id', $companyId);
                } else {
                    $q->where('company_id', null);
                }

            }

        })->count();

        if ($past != 0) {
            return round((($actual - $past) / $past) * 100, 2);
        } else {
            return $actual;
        }
    }

    public function getCountViabilityProperty()
    {
        return Note::whereRelation('Viabilities', function ($q) {
            return $q->where('completed', false)
                    ->whereYear('sended_at', date('Y'))
                    ->whereMonth('sended_at', date('m'));

            if (!Auth()->User()->superadm) {

                $companyId = auth()->user()->Employee->Contract->Company->id ?? null;

                if ($companyId) {
                    $q->where('company_id', $companyId);
                } else {
                    $q->where('company_id', null);
                }

            }


        })->count();
    }

    public function getCountTacitProperty()
    {
        return Note::whereRelation('Viabilities', function ($q) {
            return $q->where('completed', true)
                    ->where('tacit', true)
                    ->whereYear('completed_at', date('Y'))
                    ->whereMonth('completed_at', date('m'));

            if (!Auth()->User()->superadm) {

                $companyId = auth()->user()->Employee->Contract->Company->id ?? null;

                if ($companyId) {
                    $q->where('company_id', $companyId);
                } else {
                    $q->where('company_id', null);
                }

            }


        })->count();
    }

    public function getEvolutionTacitProperty()
    {
        $actual = $this->countTacit;
        $past = Note::whereRelation('Viabilities', function ($q) {
            return $q->where('completed', true)
                    ->where('tacit', true)
                    ->whereYear('completed_at', date('Y'))
                    ->whereMonth('completed_at', date('m') - 1);

            if (!Auth()->User()->superadm) {

                $companyId = auth()->user()->Employee->Contract->Company->id ?? null;

                if ($companyId) {
                    $q->where('company_id', $companyId);
                } else {
                    $q->where('company_id', null);
                }

            }

        })->count();

        if ($past != 0) {
            return round((($actual - $past) / $past) * 100, 2);
        } else {
            return $actual;
        }
    }




    public function getCountResponsersProperty()
    {
        return Note::whereRelation('Viabilities', function ($q) {
            return $q->whereYear('sended_at', date('Y'))
                    ->whereMonth('sended_at', date('m'))
                    ->where('rejected', true)
                    ->whereIn('status', [4, 10, 12]);

            if (!Auth()->User()->superadm) {

                $companyId = auth()->user()->Employee->Contract->Company->id ?? null;

                if ($companyId) {
                    $q->where('company_id', $companyId);
                } else {
                    $q->where('company_id', null);
                }

            }

        })
        ->count();
    }




    public function render()
    {
        return view('livewire.partner.main', [
            'completedNow' => $this->getCountCompletedProperty(),
            'completedBefore' => $this->getEvolutionCompletedProperty(),
            'vaibilityOpen' => $this->getCountViabilityProperty(),
            'tacitNow' => $this->getCountTacitProperty(),
            'tacitBefore' => $this->getEvolutionTacitProperty(),
            'responsers' => $this->getCountResponsersProperty(),

        ]);
    }
}
