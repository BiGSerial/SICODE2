<div class="mt-3" x-data="{
    formatDocument(value) {
        const digits = String(value || '').replace(/\D+/g, '').slice(0, 14);

        if (digits.length <= 11) {
            return digits
                .replace(/^(\d{3})(\d)/, '$1.$2')
                .replace(/^(\d{3})\.(\d{3})(\d)/, '$1.$2.$3')
                .replace(/^(\d{3})\.(\d{3})\.(\d{3})(\d{1,2})/, '$1.$2.$3-$4');
        }

        return digits
            .replace(/^(\d{2})(\d)/, '$1.$2')
            .replace(/^(\d{2})\.(\d{3})(\d)/, '$1.$2.$3')
            .replace(/^(\d{2})\.(\d{3})\.(\d{3})(\d)/, '$1.$2.$3/$4')
            .replace(/^(\d{2})\.(\d{3})\.(\d{3})\/(\d{4})(\d{1,2})/, '$1.$2.$3/$4-$5');
    }
}">
    <div class="d-flex align-items-center justify-content-between mb-2">
        <div class="fw-semibold" style="font-size:13px;color:#334155;">
            <i class="bi bi-people me-1"></i>Partes adversas
        </div>
        <div class="d-flex align-items-center gap-2">
            @if($canManageAdverseParties && !$showAdversePartyForm)
                <button type="button" class="btn btn-outline-primary btn-sm" wire:click="showAdversePartyForm">
                    <i class="bi bi-plus-lg me-1"></i>Adicionar
                </button>
            @endif
            <span class="badge bg-light text-dark border">
                {{ $legalCase->adverseParties->count() }} vinculada{{ $legalCase->adverseParties->count() !== 1 ? 's' : '' }}
            </span>
        </div>
    </div>

    @if($legalCase->adverseParties->isNotEmpty())
        <div class="table-responsive mb-3">
            <table class="table table-sm align-middle mb-0">
                <thead>
                    <tr>
                        <th>Nome</th>
                        <th>Documento</th>
                        @if($canManageAdverseParties)
                            <th class="text-end">Ações</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @foreach($legalCase->adverseParties as $party)
                        <tr wire:key="adverse-party-{{ $party->id }}">
                            <td>{{ $party->name }}</td>
                            <td class="mono">
                                {{ $canViewSensitiveAdverseParties ? $party->document_formatted : $party->document_masked }}
                                <span class="badge bg-light text-dark border ms-1">{{ strtoupper($party->document_type) }}</span>
                            </td>
                            @if($canManageAdverseParties)
                                <td class="text-end">
                                    <button type="button" class="btn btn-link btn-sm p-0 me-2" wire:click="editAdverseParty({{ $party->id }})">Editar</button>
                                    <button type="button" class="btn btn-link btn-sm p-0 text-danger" wire:click="removeAdverseParty({{ $party->id }})">Remover</button>
                                </td>
                            @endif
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <div class="text-muted small mb-3">Nenhuma parte adversa vinculada a este processo.</div>
    @endif

    @if($canManageAdverseParties && $showAdversePartyForm)
        <div class="p-3 rounded border bg-light">
            <div class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label class="form-label small fw-semibold mb-1">CPF/CNPJ</label>
                    <input type="text" class="form-control form-control-sm @error('adversePartyDocument') is-invalid @enderror"
                           wire:model.defer="adversePartyDocument"
                           x-on:input="$event.target.value = formatDocument($event.target.value)"
                           wire:blur="lookupAdverseParty"
                           placeholder="CPF ou CNPJ">
                    @error('adversePartyDocument') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-5">
                    <label class="form-label small fw-semibold mb-1">Nome</label>
                    <input type="text" class="form-control form-control-sm @error('adversePartyName') is-invalid @enderror"
                           wire:model.defer="adversePartyName"
                           placeholder="Nome da parte adversa">
                    @error('adversePartyName') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button type="button" class="btn btn-primary btn-sm flex-fill" wire:click="saveAdverseParty">
                        {{ $adversePartyEditingId ? 'Salvar edição' : 'Adicionar' }}
                    </button>
                    <button type="button" class="btn btn-outline-secondary btn-sm" wire:click="resetAdversePartyForm">Cancelar</button>
                </div>
            </div>
            @if($adversePartyLookupMessage)
                <div class="small mt-2 {{ str_contains($adversePartyLookupMessage, 'inválido') ? 'text-danger' : 'text-primary' }}">
                    {{ $adversePartyLookupMessage }}
                </div>
            @endif
        </div>
    @endif
</div>
