<?php

namespace App\Http\Livewire\Admin;

use App\Models\CancellationCategory;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Str;
use Livewire\Component;

class CancellationCategories extends Component
{
    use AuthorizesRequests;

    public ?int $editingId = null;
    public string $name = '';
    public string $slug = '';
    public ?string $description = null;
    public bool $active = true;
    public bool $require_evidence = true;
    public int $min_evidence_files = 1;

    public function save(): void
    {
        $this->authorize('manage', CancellationCategory::class);

        $this->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'active' => 'boolean',
            'require_evidence' => 'boolean',
            'min_evidence_files' => 'integer|min:0',
        ]);

        if ($this->editingId) {
            $category = CancellationCategory::findOrFail($this->editingId);

            $category->update([
                'name' => $this->name,
                'description' => $this->description,
                'active' => $this->active,
                'require_evidence' => $this->require_evidence,
                'min_evidence_files' => $this->min_evidence_files,
            ]);
        } else {
            $slug = Str::slug($this->name);
            if ($slug === '') {
                $this->addError('name', 'Não foi possível gerar slug a partir do nome.');
                return;
            }

            if (CancellationCategory::query()->where('slug', $slug)->exists()) {
                $this->addError('name', 'Já existe uma categoria com este slug. Altere o nome.');
                return;
            }

            CancellationCategory::create([
                'name' => $this->name,
                // o model também força slug automático e imutável
                'slug' => $slug,
                'description' => $this->description,
                'active' => $this->active,
                'require_evidence' => $this->require_evidence,
                'min_evidence_files' => $this->min_evidence_files,
                'display_order' => ((int) CancellationCategory::max('display_order')) + 1,
            ]);
        }

        $this->resetForm();
    }

    public function edit(int $id): void
    {
        $this->authorize('manage', CancellationCategory::class);

        $category = CancellationCategory::findOrFail($id);

        $this->editingId = $category->id;
        $this->name = $category->name;
        $this->slug = $category->slug;
        $this->description = $category->description;
        $this->active = (bool) $category->active;
        $this->require_evidence = (bool) $category->require_evidence;
        $this->min_evidence_files = (int) $category->min_evidence_files;
    }

    public function toggleActive(int $id): void
    {
        $this->authorize('manage', CancellationCategory::class);

        $category = CancellationCategory::findOrFail($id);
        $category->update(['active' => !$category->active]);
    }

    public function resetForm(): void
    {
        $this->editingId = null;
        $this->name = '';
        $this->slug = '';
        $this->description = null;
        $this->active = true;
        $this->require_evidence = true;
        $this->min_evidence_files = 1;
    }

    public function moveUp(int $id): void
    {
        $this->authorize('manage', CancellationCategory::class);

        $ordered = CancellationCategory::query()
            ->orderByRaw('COALESCE(display_order, 999999), name')
            ->get();

        $index = $ordered->search(fn ($item) => (int) $item->id === $id);
        if ($index === false || $index === 0) {
            return;
        }

        $current = $ordered[$index];
        $previous = $ordered[$index - 1];
        $this->swapDisplayOrder($current, $previous);
    }

    public function moveDown(int $id): void
    {
        $this->authorize('manage', CancellationCategory::class);

        $ordered = CancellationCategory::query()
            ->orderByRaw('COALESCE(display_order, 999999), name')
            ->get()
            ->values();

        $index = $ordered->search(fn ($item) => (int) $item->id === $id);
        if ($index === false || $index === ($ordered->count() - 1)) {
            return;
        }

        $current = $ordered[$index];
        $next = $ordered[$index + 1];
        $this->swapDisplayOrder($current, $next);
    }

    public function reorder(array $orderedIds): void
    {
        $this->authorize('manage', CancellationCategory::class);

        $position = 1;
        foreach ($orderedIds as $id) {
            CancellationCategory::whereKey((int) $id)->update(['display_order' => $position]);
            $position++;
        }
    }

    private function swapDisplayOrder(CancellationCategory $a, CancellationCategory $b): void
    {
        $orderA = $a->display_order ?? 999999;
        $orderB = $b->display_order ?? 999999;

        $a->update(['display_order' => $orderB]);
        $b->update(['display_order' => $orderA]);
    }

    public function render()
    {
        $this->authorize('manage', CancellationCategory::class);

        $categories = CancellationCategory::orderByRaw('COALESCE(display_order, 999999)')
            ->orderBy('name')
            ->get();

        return view('livewire.admin.cancellation-categories', [
            'categories' => $categories,
            'slugPreview' => Str::slug($this->name),
        ]);
    }
}
