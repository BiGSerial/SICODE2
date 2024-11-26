<?php

namespace App\Http\Livewire\Btzero;

use App\Models\Viability;
use Livewire\Component;

class Main extends Component
{
    public function getCountCompletedProperty()
    {

        $query = Viability::Query()
                    ->where('completed', true)
                    ->whereYear('completed_at', date('Y'))
                    ->whereMonth('completed_at', date('m'));
        ;

        if (!auth()->user()->superadm) {

            if (Auth()->user()->Companies->isNotEmpty()) {
                $query->where(function ($q) {
                    $q->whereIn('company_id', Auth()->user()->Companies->pluck('id')->toArray())
                    ->orWhere('company_id', Auth()->user()->Company->id);
                });
            } else {
                $query->where('company_id', Auth()->user()->Company->id);
            }
        }


        return $query->count();


    }


    public function getEvolutionCompletedProperty()
    {
        $actual = $this->countCompleted;

        $query = Viability::Query()
                    ->where('completed', true)
                    ->whereYear('completed_at', date('Y'))
                    ->whereMonth('completed_at', date('m') - 1);
        ;

        if (!auth()->user()->superadm) {


            if (Auth()->user()->Companies->isNotEmpty()) {
                $query->whereIn('company_id', Auth()->user()->Companies->pluck('id')->toArray());
            } else {
                $query->where('company_id', Auth()->user()->Company->id);
            }
        }




        if ($query->count() != 0) {
            return round((($actual - $query->count()) / $query->count()) * 100, 2);
        } else {
            return $actual;
        }
    }

    public function getCountViabilityProperty()
    {

        $query = Viability::Query()
                    ->where('completed', false)
                    ->where('approved', false)
                    ->where('rejected', false)
                    ->whereYear('sended_at', date('Y'))
                    ->whereMonth('sended_at', date('m'));
        ;

        if (!auth()->user()->superadm) {


            if (Auth()->user()->Companies->isNotEmpty()) {
                $query->whereIn('company_id', Auth()->user()->Companies->pluck('id')->toArray());
            } else {
                $query->where('company_id', Auth()->user()->Company->id);
            }
        }


        return $query->count();


    }

    public function getCountTacitProperty()
    {
        $query = Viability::Query()
                    ->where('completed', true)
                    ->where('tacit', true)
                    ->whereYear('completed_at', date('Y'))
                    ->whereMonth('completed_at', date('m'));
        ;

        if (!auth()->user()->superadm) {


            if (Auth()->user()->Companies->isNotEmpty()) {
                $query->whereIn('company_id', Auth()->user()->Companies->pluck('id')->toArray());
            } else {
                $query->where('company_id', Auth()->user()->Company->id);
            }
        }


        return $query->count();


    }

    public function getEvolutionTacitProperty()
    {
        $actual = $this->countTacit;

        $query = Viability::Query()
                    ->where('completed', true)
                    ->where('tacit', true)
                    ->whereYear('completed_at', date('Y'))
                    ->whereMonth('completed_at', date('m') - 1);
        ;

        if (!auth()->user()->superadm) {


            if (Auth()->user()->Companies->isNotEmpty()) {
                $query->whereIn('company_id', Auth()->user()->Companies->pluck('id')->toArray());
            } else {
                $query->where('company_id', Auth()->user()->Company->id);
            }
        }


        if ($query->count() != 0) {
            return round((($actual - $query->count()) / $query->count()) * 100, 2);
        } else {
            return $actual;
        }
    }




    public function getCountResponsersProperty()
    {

        $query = Viability::Query()
                    ->where('completed', false)
                    ->whereYear('sended_at', date('Y'))
                    ->whereMonth('sended_at', date('m'))
                    ->where('rejected', true)
                    ->whereIn('status', [4, 10, 12]);
        ;

        if (!auth()->user()->superadm) {

            if (Auth()->user()->Companies->isNotEmpty()) {
                $query->where(function ($q) {
                    $q->whereIn('company_id', Auth()->user()->Companies->pluck('id')->toArray())
                    ->orWhere('company_id', Auth()->user()->Company->id);
                });
            } else {
                $query->where('company_id', Auth()->user()->Company->id);
            }
        }


        return $query->count();

    }




    public function render()
    {
        return view('livewire.btzero.main', [
            'completedNow' => $this->getCountCompletedProperty(),
            'completedBefore' => $this->getEvolutionCompletedProperty(),
            'vaibilityOpen' => $this->getCountViabilityProperty(),
            'tacitNow' => $this->getCountTacitProperty(),
            'tacitBefore' => $this->getEvolutionTacitProperty(),
            'responsers' => $this->getCountResponsersProperty(),

        ]);
    }
}
