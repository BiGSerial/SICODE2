<?php

namespace App\Http\Livewire\Partner\Count;

use App\Models\FiveNote;
use Livewire\Component;

class D5OpenCount extends Component
{
    public bool $returned = false;

    public function render()
    {
        return view('livewire.partner.count.d5-open-count', [
            'count' => $this->count,
        ]);
    }

    public function getCountProperty(): int
    {
        $query = FiveNote::query()
            ->where('visible_partner', true)
            ->where('is_completed', false)
            ->where('returned', $this->returned);

        if (!auth()->user()->superadm) {
            $query->where(function ($q) {
                if (auth()->user()->Companies->isNotEmpty()) {
                    $q->whereIn('company_id', auth()->user()->Companies->pluck('id')->all())
                        ->orWhere('company_id', auth()->user()->Company->id);
                } else {
                    $q->where('company_id', auth()->user()->Company->id);
                }
            });
        }

        return $query->count();
    }
}
