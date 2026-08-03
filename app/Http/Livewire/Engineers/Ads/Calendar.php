<?php

namespace App\Http\Livewire\Engineers\Ads;

use App\Models\Holiday;
use App\Services\Holidays\HolidayImportService;
use Illuminate\Support\Collection;
use Livewire\Component;
use RuntimeException;

class Calendar extends Component
{
    public string $state = 'ES';
    public int $year = 2026;
    public array $previewRows = [];
    public ?string $lastMessage = null;
    public ?string $errorMessage = null;

    public function mount(): void
    {
        $this->year = (int) now()->year;
    }

    public function consult(HolidayImportService $service): void
    {
        $this->resetMessages();
        $this->previewRows = [];

        try {
            $this->validateInput();
            $this->previewRows = $service->preview($this->state, $this->year);
            $this->lastMessage = count($this->previewRows) . ' feriado(s) retornado(s) pela API. Confirme para substituir o calendário local.';
        } catch (\Throwable $exception) {
            $this->errorMessage = $this->friendlyError($exception);
        }
    }

    public function confirmImport(HolidayImportService $service): void
    {
        $this->resetMessages();

        try {
            $this->validateInput();

            if (empty($this->previewRows)) {
                throw new RuntimeException('Clique em Consultar Feriados primeiro. A importação só confirma depois que a prévia da API aparecer na tabela.');
            }

            $count = $service->replaceCalendar($this->state, $this->year, $this->previewRows);
            $this->previewRows = [];
            $this->lastMessage = "Calendário {$this->state}/{$this->year} importado com {$count} feriado(s).";
        } catch (\Throwable $exception) {
            $this->errorMessage = $this->friendlyError($exception);
        }
    }

    public function getImportedRowsProperty(): Collection
    {
        return Holiday::query()
            ->where('state', strtoupper($this->state))
            ->where('year', $this->year)
            ->orderBy('date')
            ->get();
    }

    public function getLastImportedAtProperty(): ?string
    {
        $last = Holiday::query()
            ->where('state', strtoupper($this->state))
            ->where('year', $this->year)
            ->max('imported_at');

        return $last ? (string) $last : null;
    }

    private function validateInput(): void
    {
        $this->state = strtoupper(trim($this->state));

        if (!preg_match('/^[A-Z]{2}$/', $this->state)) {
            throw new RuntimeException('Informe uma UF válida com 2 letras.');
        }

        if ($this->year < 2000 || $this->year > 2100) {
            throw new RuntimeException('Informe um ano entre 2000 e 2100.');
        }
    }

    private function resetMessages(): void
    {
        $this->lastMessage = null;
        $this->errorMessage = null;
    }

    private function friendlyError(\Throwable $exception): string
    {
        return match ((int) $exception->getCode()) {
            401 => 'Chave ausente ou inválida na Feriados API.',
            404 => 'Endpoint ou recurso não encontrado na Feriados API.',
            422 => 'Parâmetros inválidos para consulta de feriados.',
            429 => 'Limite de requisições da Feriados API excedido.',
            default => $exception->getMessage(),
        };
    }

    public function render()
    {
        return view('livewire.engineers.ads.calendar', [
            'importedRows' => $this->importedRows,
            'lastImportedAt' => $this->lastImportedAt,
        ]);
    }
}
