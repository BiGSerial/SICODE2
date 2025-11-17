<?php

namespace App\Http\Livewire\Components\Count\Protest;

use App\Models\ProtestJob;
use Livewire\Component;
use Illuminate\Support\Collection;

class HasProtests extends Component
{
    protected ?Collection $hierarchyIds = null;

    public function getHasProtestsProperty(): bool
    {
        $ownerIds = $this->resolveOwnerIds();

        if ($ownerIds->isEmpty()) {
            return false;
        }

        return ProtestJob::query()
            ->whereIn('owner_id', $ownerIds)
            ->whereNull('closed_at')
            ->exists();
    }

    protected function resolveOwnerIds(): Collection
    {
        if ($this->hierarchyIds instanceof Collection) {
            return $this->hierarchyIds;
        }

        $user = auth()->user();

        if (!$user) {
            return $this->hierarchyIds = collect();
        }

        $ids = $user->descendantsQuery(true, true)
            ->pluck('users.id');

        return $this->hierarchyIds = $ids->unique()->values();
    }

    public function render()
    {
        return view('livewire.components.count.protest.has-protests', [
            'hasProtests' => $this->hasProtests,
        ]);
    }
}
