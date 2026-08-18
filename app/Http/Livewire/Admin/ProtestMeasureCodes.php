<?php

namespace App\Http\Livewire\Admin;

use App\Models\ProtestMeasureCodeClassification;
use Livewire\Component;

class ProtestMeasureCodes extends Component
{
    public string $search = '';
    public string $bulkCodes = '';
    public string $bulkClassification = ProtestMeasureCodeClassification::CLASSIFICATION_CONSTRUCTION;
    public bool $bulkActive = true;
    public string $bulkRemoveCodes = '';

    public function toggleActive(int $id): void
    {
        $classification = ProtestMeasureCodeClassification::findOrFail($id);
        $classification->update(['active' => !$classification->active]);
    }

    public function toggleClassification(int $id): void
    {
        $classification = ProtestMeasureCodeClassification::findOrFail($id);
        $next = $classification->classification === ProtestMeasureCodeClassification::CLASSIFICATION_CONSTRUCTION
            ? ProtestMeasureCodeClassification::CLASSIFICATION_CIP
            : ProtestMeasureCodeClassification::CLASSIFICATION_CONSTRUCTION;

        $classification->update([
            'classification' => $next,
            'label' => $this->labelFor($next),
        ]);
    }

    public function delete(int $id): void
    {
        ProtestMeasureCodeClassification::findOrFail($id)->delete();
    }

    public function bulkSave(): void
    {
        $this->validate([
            'bulkCodes' => 'required|string',
            'bulkClassification' => 'required|in:cip,construction',
            'bulkActive' => 'boolean',
        ]);

        $codes = $this->parseCodes($this->bulkCodes);
        if (empty($codes)) {
            $this->addError('bulkCodes', 'Informe ao menos um código válido.');
            return;
        }

        foreach ($codes as $code) {
            ProtestMeasureCodeClassification::updateOrCreate(
                ['code' => $code],
                [
                    'classification' => $this->bulkClassification,
                    'label' => $this->labelFor($this->bulkClassification),
                    'active' => $this->bulkActive,
                ]
            );
        }

        $this->bulkCodes = '';
        $this->resetValidation('bulkCodes');
    }

    public function bulkDelete(): void
    {
        $this->validate([
            'bulkRemoveCodes' => 'required|string',
        ]);

        $codes = $this->parseCodes($this->bulkRemoveCodes);
        if (empty($codes)) {
            $this->addError('bulkRemoveCodes', 'Informe ao menos um código válido.');
            return;
        }

        ProtestMeasureCodeClassification::query()
            ->whereIn('code', $codes)
            ->delete();

        $this->bulkRemoveCodes = '';
        $this->resetValidation('bulkRemoveCodes');
    }

    public function clearBulkForm(): void
    {
        $this->bulkCodes = '';
        $this->bulkClassification = ProtestMeasureCodeClassification::CLASSIFICATION_CONSTRUCTION;
        $this->bulkActive = true;
        $this->bulkRemoveCodes = '';
        $this->resetValidation();
    }

    private function labelFor(string $classification): string
    {
        return $classification === ProtestMeasureCodeClassification::CLASSIFICATION_CONSTRUCTION
            ? 'Construção'
            : 'CIP';
    }

    private function parseCodes(string $value): array
    {
        return collect(preg_split('/[\s,;]+/', $value) ?: [])
            ->map(fn ($code) => ProtestMeasureCodeClassification::normalizeCode($code))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    public function render()
    {
        $query = ProtestMeasureCodeClassification::query()
            ->when($this->search !== '', function ($query) {
                $term = '%' . trim($this->search) . '%';
                $query->where(function ($sub) use ($term) {
                    $sub->where('code', 'like', $term)
                        ->orWhere('label', 'like', $term)
                        ->orWhere('classification', 'like', $term);
                });
            })
            ->orderBy('classification', 'desc')
            ->orderBy('code');

        return view('livewire.admin.protest-measure-codes', [
            'codes' => $query->get(),
        ]);
    }
}
