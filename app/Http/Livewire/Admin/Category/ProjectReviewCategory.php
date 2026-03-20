<?php

namespace App\Http\Livewire\Admin\Category;

use App\Models\ProjectReviewCategory as ProjectReviewCategoryModel;
use App\Models\ProjectReviewItem;
use App\Models\ProjectReviewSubcategory;
use App\Models\Service;
use App\Models\SystemSetting;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class ProjectReviewCategory extends Component
{
    public $category_id;
    public $subcategory_id;

    public $category_name;
    public $category_active = true;

    public $subcategory_name;
    public $subcategory_active = true;

    public $item_name;
    public $item_active = true;

    public string $bulk_target = '';
    public string $bulk_payload = '';
    public ?string $selectedProjectReviewSurveyServiceId = null;

    public function mount(): void
    {
        $this->selectedProjectReviewSurveyServiceId = SystemSetting::getValue('project_review_survey_service_id');
    }

    public function getCategoriesProperty()
    {
        return ProjectReviewCategoryModel::query()
            ->orderBy('name')
            ->get();
    }

    public function getSubcategoriesProperty()
    {
        if (!$this->category_id) {
            return collect();
        }

        return ProjectReviewSubcategory::query()
            ->where('category_id', $this->category_id)
            ->orderBy('name')
            ->get();
    }

    public function getItemsProperty()
    {
        if (!$this->subcategory_id) {
            return collect();
        }

        return ProjectReviewItem::query()
            ->where('subcategory_id', $this->subcategory_id)
            ->orderBy('name')
            ->get();
    }

    public function getServiceOptionsProperty()
    {
        return Service::query()
            ->orderBy('service')
            ->get(['uuid', 'service']);
    }

    public function updatedSelectedProjectReviewSurveyServiceId($value): void
    {
        $value = $value ?: null;

        if ($value !== null && !Service::query()->where('uuid', $value)->exists()) {
            $this->selectedProjectReviewSurveyServiceId = null;
            SystemSetting::setValue('project_review_survey_service_id', null);
            return;
        }

        SystemSetting::setValue('project_review_survey_service_id', $value);
    }

    public function saveCategory(): void
    {
        $this->validate([
            'category_name' => 'required|string|max:191',
        ]);

        ProjectReviewCategoryModel::updateOrCreate(
            [
                'name' => mb_strtoupper(trim((string) $this->category_name)),
            ],
            [
                'sort_order' => 0,
                'active' => (bool) $this->category_active,
            ]
        );

        $this->reset(['category_name']);

        $this->dispatchBrowserEvent('swal', [
            'position' => 'center',
            'icon' => 'success',
            'title' => 'Categoria salva com sucesso',
            'timer' => 2000,
        ]);
    }

    public function saveSubcategory(): void
    {
        $this->validate([
            'category_id' => 'required|exists:project_review_categories,id',
            'subcategory_name' => 'required|string|max:191',
        ]);

        ProjectReviewSubcategory::updateOrCreate(
            [
                'category_id' => (int) $this->category_id,
                'name' => mb_strtoupper(trim((string) $this->subcategory_name)),
            ],
            [
                'sort_order' => 0,
                'active' => (bool) $this->subcategory_active,
            ]
        );

        $this->reset(['subcategory_name']);

        $this->dispatchBrowserEvent('swal', [
            'position' => 'center',
            'icon' => 'success',
            'title' => 'Subcategoria salva com sucesso',
            'timer' => 2000,
        ]);
    }

    public function saveItem(): void
    {
        $this->validate([
            'subcategory_id' => 'required|exists:project_review_subcategories,id',
            'item_name' => 'required|string|max:191',
        ]);

        $subcategoryId = (int) $this->subcategory_id;
        $incomingName = $this->normalizeStoredName((string) $this->item_name);
        $incomingKey = $this->normalizeCompareKey($incomingName);

        $existing = ProjectReviewItem::query()
            ->where('subcategory_id', $subcategoryId)
            ->get()
            ->first(function (ProjectReviewItem $item) use ($incomingKey) {
                return $this->normalizeCompareKey((string) $item->name) === $incomingKey;
            });

        if ($existing) {
            $existing->update([
                'sort_order' => 0,
                'active' => (bool) $this->item_active,
            ]);
        } else {
            ProjectReviewItem::create([
                'subcategory_id' => $subcategoryId,
                'name' => $incomingName,
                'sort_order' => 0,
                'active' => (bool) $this->item_active,
            ]);
        }

        $this->reset(['item_name']);

        $this->dispatchBrowserEvent('swal', [
            'position' => 'center',
            'icon' => 'success',
            'title' => 'Item salvo com sucesso',
            'timer' => 2000,
        ]);
    }

    public function openBulkModal(string $target): void
    {
        if (!in_array($target, ['category', 'subcategory', 'item'], true)) {
            return;
        }

        if ($target === 'subcategory' && !$this->category_id) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon' => 'warning',
                'title' => 'Selecione uma categoria primeiro',
                'timer' => 2200,
            ]);
            return;
        }

        if ($target === 'item' && !$this->subcategory_id) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon' => 'warning',
                'title' => 'Selecione uma subcategoria primeiro',
                'timer' => 2200,
            ]);
            return;
        }

        $this->bulk_target = $target;
        $this->bulk_payload = '';

        $this->dispatchBrowserEvent('showModal', ['id' => 'projectReviewBulkModal']);
    }

    public function saveBulk(): void
    {
        $parsed = $this->parseBulkValues($this->bulk_payload);

        if (empty($parsed)) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon' => 'warning',
                'title' => 'Cole pelo menos um valor válido',
                'timer' => 2200,
            ]);
            return;
        }

        if ($this->bulk_target === 'category') {
            $this->insertBulkCategories($parsed);
            return;
        }

        if ($this->bulk_target === 'subcategory') {
            $this->insertBulkSubcategories($parsed);
            return;
        }

        if ($this->bulk_target === 'item') {
            $this->insertBulkItems($parsed);
            return;
        }
    }

    private function insertBulkCategories(array $parsed): void
    {
        $existingNames = ProjectReviewCategoryModel::query()->pluck('name')->all();
        $existingKeys = collect($existingNames)->mapWithKeys(function ($name) {
            return [$this->normalizeCompareKey((string) $name) => true];
        })->all();

        $inserted = 0;
        $skipped = 0;

        DB::transaction(function () use ($parsed, &$existingKeys, &$inserted, &$skipped) {
            foreach ($parsed as $row) {
                if (isset($existingKeys[$row['key']])) {
                    $skipped++;
                    continue;
                }

                ProjectReviewCategoryModel::create([
                    'name' => $row['name'],
                    'sort_order' => 0,
                    'active' => true,
                ]);

                $existingKeys[$row['key']] = true;
                $inserted++;
            }
        });

        $this->finishBulkInsert($inserted, $skipped);
    }

    private function insertBulkSubcategories(array $parsed): void
    {
        $categoryId = (int) $this->category_id;
        $existingNames = ProjectReviewSubcategory::query()
            ->where('category_id', $categoryId)
            ->pluck('name')
            ->all();

        $existingKeys = collect($existingNames)->mapWithKeys(function ($name) {
            return [$this->normalizeCompareKey((string) $name) => true];
        })->all();

        $inserted = 0;
        $skipped = 0;

        DB::transaction(function () use ($parsed, $categoryId, &$existingKeys, &$inserted, &$skipped) {
            foreach ($parsed as $row) {
                if (isset($existingKeys[$row['key']])) {
                    $skipped++;
                    continue;
                }

                ProjectReviewSubcategory::create([
                    'category_id' => $categoryId,
                    'name' => $row['name'],
                    'sort_order' => 0,
                    'active' => true,
                ]);

                $existingKeys[$row['key']] = true;
                $inserted++;
            }
        });

        $this->finishBulkInsert($inserted, $skipped);
    }

    private function insertBulkItems(array $parsed): void
    {
        $subcategoryId = (int) $this->subcategory_id;
        $existingNames = ProjectReviewItem::query()
            ->where('subcategory_id', $subcategoryId)
            ->pluck('name')
            ->all();

        $existingKeys = collect($existingNames)->mapWithKeys(function ($name) {
            return [$this->normalizeCompareKey((string) $name) => true];
        })->all();

        $inserted = 0;
        $skipped = 0;

        DB::transaction(function () use ($parsed, $subcategoryId, &$existingKeys, &$inserted, &$skipped) {
            foreach ($parsed as $row) {
                if (isset($existingKeys[$row['key']])) {
                    $skipped++;
                    continue;
                }

                ProjectReviewItem::create([
                    'subcategory_id' => $subcategoryId,
                    'name' => $row['name'],
                    'sort_order' => 0,
                    'active' => true,
                ]);

                $existingKeys[$row['key']] = true;
                $inserted++;
            }
        });

        $this->finishBulkInsert($inserted, $skipped);
    }

    private function finishBulkInsert(int $inserted, int $skipped): void
    {
        $this->dispatchBrowserEvent('hideModal');
        $this->bulk_payload = '';

        $this->dispatchBrowserEvent('swal', [
            'position' => 'center',
            'icon' => 'success',
            'title' => 'Inserção em massa concluída',
            'html' => "Inseridos: <strong>{$inserted}</strong><br>Ignorados (duplicados): <strong>{$skipped}</strong>",
            'timer' => 2800,
        ]);
    }

    private function parseBulkValues(string $payload): array
    {
        $tokens = preg_split('/[\r\n,;]+/u', $payload) ?: [];
        $out = [];
        $seen = [];

        foreach ($tokens as $token) {
            $name = $this->normalizeStoredName((string) $token);
            if ($name === '') {
                continue;
            }

            $key = $this->normalizeCompareKey($name);
            if ($key === '' || isset($seen[$key])) {
                continue;
            }

            $seen[$key] = true;
            $out[] = [
                'name' => $name,
                'key' => $key,
            ];
        }

        return $out;
    }

    private function normalizeStoredName(string $value): string
    {
        $value = preg_replace('/\s+/u', ' ', trim($value));
        return mb_strtoupper((string) $value);
    }

    private function normalizeCompareKey(string $value): string
    {
        $value = $this->normalizeStoredName($value);
        $ascii = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
        if ($ascii === false) {
            $ascii = $value;
        }

        $ascii = mb_strtoupper($ascii);
        $ascii = preg_replace('/[^A-Z0-9 ]+/', ' ', $ascii);
        $ascii = preg_replace('/\s+/', ' ', trim((string) $ascii));

        return (string) $ascii;
    }

    public function toggleCategory(int $id): void
    {
        $cat = ProjectReviewCategoryModel::findOrFail($id);
        $cat->active = !$cat->active;
        $cat->save();
    }

    public function toggleSubcategory(int $id): void
    {
        $sub = ProjectReviewSubcategory::findOrFail($id);
        $sub->active = !$sub->active;
        $sub->save();
    }

    public function toggleItem(int $id): void
    {
        $item = ProjectReviewItem::findOrFail($id);
        $item->active = !$item->active;
        $item->save();
    }

    public function removeCategory(int $id): void
    {
        $category = ProjectReviewCategoryModel::findOrFail($id);

        $hasUsage = DB::table('project_review_findings as f')
            ->join('project_review_subcategories as s', 's.id', '=', 'f.subcategory_id')
            ->where('s.category_id', $id)
            ->exists();

        if ($hasUsage) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon' => 'warning',
                'title' => 'Categoria em uso',
                'html' => 'Desative a categoria ao invés de remover.',
            ]);
            return;
        }

        $category->delete();
    }

    public function removeSubcategory(int $id): void
    {
        $sub = ProjectReviewSubcategory::findOrFail($id);

        if (DB::table('project_review_findings')->where('subcategory_id', $id)->exists()) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon' => 'warning',
                'title' => 'Subcategoria em uso',
                'html' => 'Desative a subcategoria ao invés de remover.',
            ]);
            return;
        }

        $sub->delete();
    }

    public function removeItem(int $id): void
    {
        $item = ProjectReviewItem::findOrFail($id);

        if (DB::table('project_review_findings')->where('item_id', $id)->exists()) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon' => 'warning',
                'title' => 'Item em uso',
                'html' => 'Desative o item ao invés de remover.',
            ]);
            return;
        }

        $item->delete();
    }

    public function render()
    {
        return view('livewire.admin.category.project-review-category', [
            'categories' => $this->categories,
            'subcategories' => $this->subcategories,
            'items' => $this->items,
            'serviceOptions' => $this->serviceOptions,
        ]);
    }

    public function selectCategory(int $id): void
    {
        $this->category_id = $id;
        $this->subcategory_id = null;
    }

    public function selectSubcategory(int $id): void
    {
        $this->subcategory_id = $id;
    }
}
