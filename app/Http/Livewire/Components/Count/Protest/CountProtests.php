<?php

namespace App\Http\Livewire\Components\Count\Protest;

use App\Models\ProtestJob;
use Livewire\Component;
use Illuminate\Support\Collection;

class CountProtests extends Component
{
    public $type;
    protected ?Collection $hierarchyIds = null;

    public function mount($type = null)
    {
        $this->type = mb_strtoupper($type ?? '');
    }

    public function getCountProperty()
    {
        $ownerIds = $this->resolveOwnerIds();

        if ($ownerIds->isEmpty()) {
            return 0;
        }

        return ProtestJob::query()
            ->whereIn('owner_id', $ownerIds)
            ->whereNull('closed_at')
            ->count();
    }

    protected function resolveOwnerIds(): Collection
    {
        if ($this->type !== 'M') {
            return collect([auth()->id()]);
        }

        if ($this->hierarchyIds instanceof Collection) {
            return $this->hierarchyIds;
        }

        $user = auth()->user();

        if (!$user) {
            return $this->hierarchyIds = collect();
        }

        $ids = $user->descendantsQuery(false, true)->pluck('users.id');

        return $this->hierarchyIds = $ids->unique()->values();
    }

    public function render()
    {
        return view('livewire.components.count.protest.count-protests', [
            'count' => $this->count,
        ]);
    }
}
