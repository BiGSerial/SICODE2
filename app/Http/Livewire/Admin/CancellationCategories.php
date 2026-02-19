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
    public ?int $display_order = null;

    public function save(): void
    {
        $this->authorize('manage', CancellationCategory::class);

        $this->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'active' => 'boolean',
            'require_evidence' => 'boolean',
            'min_evidence_files' => 'integer|min:0',
            'display_order' => 'nullable|integer|min:1',
        ]);

        if ($this->editingId) {
            $category = CancellationCategory::findOrFail($this->editingId);

            $category->update([
                'name' => $this->name,
                'description' => $this->description,
                'active' => $this->active,
                'require_evidence' => $this->require_evidence,
                'min_evidence_files' => $this->min_evidence_files,
                'display_order' => $this->display_order,
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
                'display_order' => $this->display_order,
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
        $this->display_order = $category->display_order;
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
        $this->display_order = null;
    }

    public function render()
    {
        $this->authorize('manage', CancellationCategory::class);

        $categories = CancellationCategory::orderBy('display_order')
            ->orderBy('name')
            ->get();

        return view('livewire.admin.cancellation-categories', [
            'categories' => $categories,
            'slugPreview' => Str::slug($this->name),
        ]);
    }
}
