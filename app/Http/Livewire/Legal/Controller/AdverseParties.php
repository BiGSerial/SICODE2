<?php

namespace App\Http\Livewire\Legal\Controller;

use App\Models\Legal\LegalCase;
use App\Models\Legal\LegalCaseAdverseParty;
use App\Support\Legal\LegalPartyDocument;
use Livewire\Component;

class AdverseParties extends Component
{
    public int $legalCaseId;

    public string $adversePartyDocument = '';
    public string $adversePartyName = '';
    public ?int $adversePartyEditingId = null;
    public ?string $adversePartyLookupMessage = null;
    public bool $showAdversePartyForm = false;

    public function mount(int $legalCaseId): void
    {
        $this->legalCaseId = $legalCaseId;
    }

    public function lookupAdverseParty(): void
    {
        $this->adversePartyLookupMessage = null;

        $digits = LegalPartyDocument::digits($this->adversePartyDocument);
        if (!in_array(strlen($digits), [11, 14], true)) {
            return;
        }

        if (!LegalPartyDocument::validate($digits)) {
            $this->adversePartyLookupMessage = strlen($digits) === 11
                ? 'CPF inválido. Confira os dígitos informados.'
                : 'CNPJ inválido. Confira os dígitos informados.';
            return;
        }

        $existing = LegalCaseAdverseParty::query()
            ->where('document_hash', LegalPartyDocument::hash($digits))
            ->when($this->adversePartyEditingId, fn ($query) => $query->where('id', '!=', $this->adversePartyEditingId))
            ->orderByDesc('updated_at')
            ->first();

        if ($existing) {
            $this->adversePartyName = $existing->name;
            $this->adversePartyLookupMessage = 'Documento já conhecido. Nome preenchido automaticamente.';
        }
    }

    public function saveAdverseParty(): void
    {
        abort_unless(auth()->user()->can('legal.adverse_parties.manage'), 403);

        $this->validate([
            'adversePartyDocument' => 'required|string|min:11|max:25',
            'adversePartyName' => 'nullable|string|max:255',
        ]);

        $digits = LegalPartyDocument::digits($this->adversePartyDocument);
        $type = LegalPartyDocument::type($digits);

        if ($type === null || !LegalPartyDocument::validate($digits)) {
            $this->addError('adversePartyDocument', 'Informe um CPF ou CNPJ válido.');
            return;
        }

        $hash = LegalPartyDocument::hash($digits);
        $existingForDocument = LegalCaseAdverseParty::query()
            ->where('document_hash', $hash)
            ->when($this->adversePartyEditingId, fn ($query) => $query->where('id', '!=', $this->adversePartyEditingId))
            ->orderByDesc('updated_at')
            ->first();

        $name = trim($this->adversePartyName) !== ''
            ? trim($this->adversePartyName)
            : $existingForDocument?->name;

        if (!$name) {
            $this->addError('adversePartyName', 'Informe o nome da parte adversa.');
            return;
        }

        $duplicateInCase = LegalCaseAdverseParty::query()
            ->where('legal_case_id', $this->legalCaseId)
            ->where('document_hash', $hash)
            ->when($this->adversePartyEditingId, fn ($query) => $query->where('id', '!=', $this->adversePartyEditingId))
            ->exists();

        if ($duplicateInCase) {
            $this->dispatchBrowserEvent('swal', ['icon' => 'warning', 'title' => 'Esta parte adversa já está vinculada ao processo.']);
            return;
        }

        $payload = [
            'name' => $name,
            'document_type' => $type,
            'document_encrypted' => $digits,
            'document_hash' => $hash,
            'document_last4' => LegalPartyDocument::last4($digits),
            'updated_by' => auth()->id(),
        ];

        if ($this->adversePartyEditingId) {
            LegalCaseAdverseParty::query()
                ->where('legal_case_id', $this->legalCaseId)
                ->findOrFail($this->adversePartyEditingId)
                ->update($payload);
        } else {
            LegalCase::query()->findOrFail($this->legalCaseId)->adverseParties()->create($payload + [
                'created_by' => auth()->id(),
            ]);
        }

        $this->resetAdversePartyForm();
        $this->dispatchBrowserEvent('swal', ['icon' => 'success', 'title' => 'Parte adversa salva.']);
    }

    public function showAdversePartyForm(): void
    {
        abort_unless(auth()->user()->can('legal.adverse_parties.manage'), 403);

        $this->resetAdversePartyForm();
        $this->showAdversePartyForm = true;
    }

    public function editAdverseParty(int $partyId): void
    {
        abort_unless(auth()->user()->can('legal.adverse_parties.manage'), 403);

        $party = LegalCaseAdverseParty::query()
            ->where('legal_case_id', $this->legalCaseId)
            ->findOrFail($partyId);

        $this->adversePartyEditingId = $party->id;
        $this->adversePartyName = $party->name;
        $this->adversePartyDocument = $party->document_formatted;
        $this->adversePartyLookupMessage = null;
        $this->showAdversePartyForm = true;
    }

    public function removeAdverseParty(int $partyId): void
    {
        abort_unless(auth()->user()->can('legal.adverse_parties.manage'), 403);

        LegalCaseAdverseParty::query()
            ->where('legal_case_id', $this->legalCaseId)
            ->findOrFail($partyId)
            ->delete();

        if ($this->adversePartyEditingId === $partyId) {
            $this->resetAdversePartyForm();
        }

        $this->dispatchBrowserEvent('swal', ['icon' => 'success', 'title' => 'Parte adversa removida do processo.']);
    }

    public function resetAdversePartyForm(): void
    {
        $this->adversePartyDocument = '';
        $this->adversePartyName = '';
        $this->adversePartyEditingId = null;
        $this->adversePartyLookupMessage = null;
        $this->showAdversePartyForm = false;
        $this->resetErrorBag(['adversePartyDocument', 'adversePartyName']);
    }

    public function render()
    {
        $legalCase = LegalCase::query()
            ->with('adverseParties')
            ->findOrFail($this->legalCaseId);

        return view('livewire.legal.controller.adverse-parties', [
            'legalCase' => $legalCase,
            'canManageAdverseParties' => auth()->user()->can('legal.adverse_parties.manage'),
            'canViewSensitiveAdverseParties' => auth()->user()->can('legal.adverse_parties.view_sensitive'),
        ]);
    }
}
