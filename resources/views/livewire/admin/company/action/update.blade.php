<div>
    <x-show-loading />
    <style>
        #companyModal [x-cloak] { display: none !important; }
        #companyModal .co-modal { background: #f4f7f9; border: 0; border-radius: 16px; overflow: hidden; }
        #companyModal .co-head { align-items: center; background: linear-gradient(135deg, #0d3b3f, #155f67 60%, #0f8a77); color: #fff; display: flex; gap: 16px; padding: 18px 24px; }
        #companyModal .co-logo { align-items: center; background: #0f172a; border-radius: 12px; display: inline-flex; flex: none; height: 52px; justify-content: center; padding: 6px; width: 52px; }
        #companyModal .co-logo img { max-height: 100%; max-width: 100%; object-fit: contain; }
        #companyModal .co-title { font-size: 1.25rem; font-weight: 800; line-height: 1.2; margin: 0; }
        #companyModal .co-meta { display: flex; flex-wrap: wrap; gap: 6px; margin-top: 6px; }
        #companyModal .co-pill { background: rgba(255, 255, 255, .16); border-radius: 99px; font-size: .72rem; font-weight: 700; padding: 3px 10px; }
        #companyModal .co-layout { display: grid; grid-template-columns: 230px minmax(0, 1fr); min-height: 0; }
        #companyModal .co-nav { background: #fff; border-right: 1px solid #e3eaef; display: flex; flex-direction: column; gap: 4px; padding: 14px 10px; }
        #companyModal .co-tab { align-items: center; background: none; border: 0; border-radius: 10px; color: #475569; display: flex; font-size: .88rem; font-weight: 600; gap: 10px; padding: 10px 12px; text-align: left; width: 100%; }
        #companyModal .co-tab i { color: #94a3b8; font-size: 1.1rem; }
        #companyModal .co-tab:hover { background: #f1f6f8; }
        #companyModal .co-tab.is-active { background: #e4f1f3; color: #0d3b3f; font-weight: 800; }
        #companyModal .co-tab.is-active i { color: #155f67; }
        #companyModal .co-count { background: #e8edf1; border-radius: 99px; color: #475569; font-size: .68rem; font-weight: 800; margin-left: auto; padding: 1px 8px; }
        #companyModal .co-tab.is-active .co-count { background: #155f67; color: #fff; }
        #companyModal .co-body { max-height: calc(100vh - 290px); min-height: 420px; overflow-y: auto; padding: 22px 26px; }
        #companyModal .co-panel-title { font-size: 1.05rem; font-weight: 800; margin: 0; }
        #companyModal .co-panel-sub { color: #64748b; font-size: .84rem; margin-top: 2px; }
        #companyModal .co-card { background: #fff; border: 1px solid #e3eaef; border-radius: 12px; padding: 16px 18px; }
        #companyModal .co-label { color: #475569; display: block; font-size: .72rem; font-weight: 800; letter-spacing: .04em; margin-bottom: 5px; text-transform: uppercase; }
        #companyModal .co-item { align-items: center; background: #fff; border: 1px solid #e3eaef; border-radius: 10px; display: flex; gap: 12px; justify-content: space-between; padding: 11px 14px; }
        #companyModal .co-code { background: #e4f1f3; border-radius: 6px; color: #0d3b3f; font-weight: 800; font-size: .82rem; padding: 2px 9px; }
        #companyModal .co-empty { background: #fff; border: 1px dashed #cbd5e1; border-radius: 12px; color: #64748b; padding: 28px 16px; text-align: center; }
        #companyModal .co-empty i { display: block; font-size: 1.8rem; opacity: .5; }
        #companyModal .co-hint { background: #eef5f6; border-left: 3px solid #155f67; border-radius: 6px; color: #334155; font-size: .84rem; padding: 10px 12px; }
        #companyModal .co-foot { background: #fff; border-top: 1px solid #e3eaef; display: flex; gap: 10px; justify-content: flex-end; padding: 14px 24px; }
        #companyModal .perm-card { background: #fff; border: 1px solid #e3eaef; border-radius: 12px; padding: 14px 16px; }
        #companyModal .perm-card-head { align-items: center; border-bottom: 1px solid #eef2f6; display: flex; gap: 10px; margin-bottom: 8px; padding-bottom: 10px; padding-left: 2.5em; }
        #companyModal .perm-card-head .form-check-label { font-weight: 800; }
        #companyModal .perm-link { background: none; border: 0; color: #155f67; font-size: .7rem; font-weight: 700; padding: 0 3px; text-decoration: underline; }
        #companyModal .co-logos .co-logo-slot { background: #fff; border: 1px solid #e3eaef; border-radius: 12px; display: flex; gap: 14px; padding: 12px; }
        .slide-down-animation { animation: companySlideDown .35s ease-out; }
        @keyframes companySlideDown { from { transform: translateY(-12px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
        @media (max-width: 991.98px) { #companyModal .co-layout { grid-template-columns: 1fr; } #companyModal .co-nav { border-bottom: 1px solid #e3eaef; border-right: 0; flex-direction: row; overflow-x: auto; } #companyModal .co-tab { white-space: nowrap; width: auto; } #companyModal .co-body { max-height: none; } }
    </style>

    <div wire:ignore.self class="modal fade" id="companyModal" tabindex="-1" aria-labelledby="companyModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content co-modal shadow" x-data="{ tab: 'dados' }">
                <div class="co-head">
                    <span class="co-logo"><img src="{{ $company?->logo_url }}" alt="Logo"></span>
                    <div class="flex-grow-1" style="min-width:0;">
                        <h5 class="co-title text-truncate" id="companyModalLabel">{{ $company?->name ?: 'Atualizar dados da empresa' }}</h5>
                        <div class="co-meta">
                            @if ($company?->parent)
                                <span class="co-pill"><i class="ri-git-branch-line"></i> Unidade de {{ $company->parent->name }}</span>
                            @elseif ($company?->branches?->count())
                                <span class="co-pill"><i class="ri-building-2-line"></i> Concentradora · {{ $company->branches->count() }} unidade(s)</span>
                            @endif
                            <span class="co-pill"><i class="ri-file-text-line"></i> {{ $company?->contracts?->count() ?? 0 }} contrato(s)</span>
                            <span class="co-pill"><i class="ri-map-pin-line"></i> {{ count($addresses ?? []) }} endereço(s)</span>
                        </div>
                    </div>
                    <button type="button" class="btn btn-sm btn-light fw-semibold" wire:click.prevent="downloadUserRegistrationWorkbook" wire:loading.attr="disabled" wire:target="downloadUserRegistrationWorkbook">
                        <i class="ri-file-excel-2-line" wire:loading.remove wire:target="downloadUserRegistrationWorkbook"></i>
                        <span class="spinner-border spinner-border-sm" wire:loading wire:target="downloadUserRegistrationWorkbook"></span>
                        Ficha de usuários
                    </button>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>

                <div class="co-layout">
                    <nav class="co-nav" aria-label="Seções da empresa">
                        @foreach ([
                            ['dados', 'ri-building-line', 'Dados e identidade', null],
                            ['enderecos', 'ri-map-pin-line', 'Endereços', count($addresses ?? [])],
                            ['depositos', 'ri-store-2-line', 'Centros e depósitos', $company?->Centerjobs?->count() ?? 0],
                            ['contratos', 'ri-file-text-line', 'Contratos e atividades', $company?->contracts?->count() ?? 0],
                            ['unidades', 'ri-git-branch-line', 'Unidades', $company?->branches?->count() ?? 0],
                            ['permissoes', 'ri-shield-keyhole-line', 'Permissões', null],
                            ['massa', 'ri-team-line', 'Cadastro em massa', null],
                        ] as [$key, $icon, $label, $count])
                            <button type="button" class="co-tab" :class="{ 'is-active': tab === '{{ $key }}' }" @click="tab = '{{ $key }}'">
                                <i class="{{ $icon }}"></i><span>{{ $label }}</span>
                                @if ($count !== null)<span class="co-count">{{ $count }}</span>@endif
                            </button>
                        @endforeach
                    </nav>

                    <div class="co-body">
                        {{-- DADOS E IDENTIDADE --}}
                        <section x-show="tab === 'dados'">
                            <h3 class="co-panel-title">Dados e identidade</h3>
                            <div class="co-panel-sub mb-3">Contato da empresa, regra de inatividade e logos usadas nos portais.</div>
                            <div class="co-card mb-3">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="co-label" for="name">Nome</label>
                                        <input wire:model.defer="company.name" type="text" class="form-control" id="name">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="co-label" for="email">E-mail</label>
                                        <input wire:model.defer="company.email" type="email" class="form-control" id="email">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="co-label" for="telephone">Telefone</label>
                                        <input wire:model.defer="company.telephone" type="text" class="form-control" id="telephone">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="co-label" for="partner_user_inactivity_days">Auto desativar usuários parceiros após</label>
                                        <div class="input-group">
                                            <input wire:model.defer="company.partner_user_inactivity_days" type="number" min="1" max="3650" class="form-control" id="partner_user_inactivity_days">
                                            <span class="input-group-text">dias sem login</span>
                                        </div>
                                        <div class="form-text">Deixe vazio para não aplicar auto desativação.</div>
                                        @error('company.partner_user_inactivity_days')
                                            <div class="text-danger small mt-1">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                            <div class="co-logos">
                                <div class="co-label">Logos</div>
                                <div class="row g-3">
                                    @for ($i = 0; $i < 4; $i++)
                                        <div class="col-md-6">
                                            <div class="co-logo-slot">
                                                <img src="{{ $this->logoPreviewUrl($i) }}" alt="{{ $this->title_img($i)->title }}" class="img-thumbnail bg-white" style="width: 84px; height: 84px; object-fit: contain;">
                                                <div class="flex-grow-1" style="min-width:0;">
                                                    <label for="photo{{ $i }}" class="form-label small fw-semibold mb-1">{{ $this->title_img($i)->title }}</label>
                                                    <input type="file" id="photo{{ $i }}" class="form-control form-control-sm" wire:model="photo{{ $i }}" accept="image/*">
                                                    @error('photo' . $i)
                                                        <div class="text-danger small mt-1">{{ $message }}</div>
                                                    @enderror
                                                </div>
                                            </div>
                                        </div>
                                    @endfor
                                </div>
                            </div>
                        </section>

                        {{-- ENDEREÇOS --}}
                        <section x-show="tab === 'enderecos'" x-cloak>
                            <div class="d-flex align-items-start justify-content-between gap-3 mb-3">
                                <div><h3 class="co-panel-title">Endereços</h3><div class="co-panel-sub">Locais de atuação da empresa.</div></div>
                                <button type="button" class="btn btn-sm btn-primary" wire:click="addAddress"><i class="ri-add-line"></i> Adicionar endereço</button>
                            </div>
                            @if ($newAddress)
                                <div class="co-card mb-3 slide-down-animation">
                                    <div class="row g-3">
                                        <div class="col-md-5"><label class="co-label" for="addr-street">Rua</label><input type="text" class="form-control form-control-sm" id="addr-street" wire:model.defer="newAddress.street"></div>
                                        <div class="col-md-3"><label class="co-label" for="addr-complement">Complemento</label><input type="text" class="form-control form-control-sm" id="addr-complement" wire:model.defer="newAddress.complement"></div>
                                        <div class="col-md-3"><label class="co-label" for="addr-city">Cidade</label><input type="text" class="form-control form-control-sm" id="addr-city" wire:model.defer="newAddress.city"></div>
                                        <div class="col-md-1"><label class="co-label" for="addr-uf">UF</label><input type="text" class="form-control form-control-sm" id="addr-uf" wire:model.defer="newAddress.uf"></div>
                                    </div>
                                    <div class="d-flex justify-content-end gap-2 mt-3">
                                        <button type="button" class="btn btn-sm btn-outline-secondary" wire:click.prevent="cancelAddress">Cancelar</button>
                                        <button type="button" class="btn btn-sm btn-primary" wire:click.prevent="saveAddress">Salvar endereço</button>
                                    </div>
                                </div>
                            @endif
                            <div class="d-flex flex-column gap-2">
                                @forelse ($addresses as $address)
                                    <div class="co-item" wire:key="company_address_{{ $address->id }}">
                                        <span><i class="ri-map-pin-2-line text-muted me-2"></i><strong>{{ $address->street }}</strong>{{ $address->complement ? ', ' . $address->complement : '' }}, {{ $address->city }} - {{ $address->uf }}</span>
                                        <button type="button" class="btn btn-sm btn-outline-danger" wire:click="removeAddress({{ $address->id }})" aria-label="Remover endereço"><i class="ri-delete-bin-line"></i></button>
                                    </div>
                                @empty
                                    <div class="co-empty"><i class="ri-map-pin-line"></i>Nenhum endereço cadastrado.</div>
                                @endforelse
                            </div>
                        </section>

                        {{-- CENTROS E DEPÓSITOS --}}
                        <section x-show="tab === 'depositos'" x-cloak>
                            <div class="d-flex align-items-start justify-content-between gap-3 mb-3">
                                <div><h3 class="co-panel-title">Centros e depósitos</h3><div class="co-panel-sub">Vínculo da empresa com os centros de trabalho e depósitos do SAP.</div></div>
                                <button type="button" class="btn btn-sm btn-primary" wire:click.prevent="addCenterjob"><i class="ri-add-line"></i> Adicionar vínculo</button>
                            </div>
                            <div class="co-hint mb-3">O <strong>Centro de trabalho</strong> é o código do SAP (ex.: <code>ENGELCAC</code>), o mesmo que aparece na operação 0010 das Ordens. É por ele que o sistema sabe a que empresa pertence um depósito, por exemplo para o bloqueio de depósito do Encerramento.</div>
                            @if ($centerjob)
                                <div class="co-card mb-3 slide-down-animation">
                                    <div class="row g-3">
                                        <div class="col-md-6"><label class="co-label" for="cj-centerjob">Centro de trabalho</label><input type="text" class="form-control form-control-sm" id="cj-centerjob" wire:model.defer="centerjob.centerjob" placeholder="Ex.: ENGELCAC"></div>
                                        <div class="col-md-3"><label class="co-label" for="cj-center">Centro</label><input type="text" class="form-control form-control-sm" id="cj-center" wire:model.defer="centerjob.center" placeholder="Ex.: 5503"></div>
                                        <div class="col-md-3"><label class="co-label" for="cj-deposit">Depósito</label><input type="text" class="form-control form-control-sm" id="cj-deposit" wire:model.defer="centerjob.deposit" placeholder="Ex.: 5011"></div>
                                    </div>
                                    <div class="d-flex justify-content-end gap-2 mt-3">
                                        <button type="button" class="btn btn-sm btn-outline-secondary" wire:click.prevent="cancelCenterjob">Cancelar</button>
                                        <button type="button" class="btn btn-sm btn-primary" wire:click.prevent="saveCenterjob">Salvar vínculo</button>
                                    </div>
                                </div>
                            @endif
                            <div class="d-flex flex-column gap-2">
                                @forelse ($company?->Centerjobs ?? [] as $cj)
                                    <div class="co-item" wire:key="company_centerjob_{{ $cj->id }}">
                                        <span class="d-flex flex-wrap align-items-center gap-2">
                                            <span class="co-code">{{ $cj->centerjob ?: '—' }}</span>
                                            <span class="small text-muted">Centro <strong class="text-dark">{{ $cj->center ?: '—' }}</strong></span>
                                            <span class="small text-muted">Depósito <strong class="text-dark">{{ $cj->deposit ?: '—' }}</strong></span>
                                        </span>
                                        <button type="button" class="btn btn-sm btn-outline-danger" wire:click="removeCenterjob({{ $cj->id }})" aria-label="Remover vínculo"><i class="ri-delete-bin-line"></i></button>
                                    </div>
                                @empty
                                    <div class="co-empty"><i class="ri-store-2-line"></i>Nenhum centro ou depósito vinculado a esta empresa.</div>
                                @endforelse
                            </div>
                        </section>

                        {{-- CONTRATOS --}}
                        <section x-show="tab === 'contratos'" x-cloak>
                            <div class="d-flex align-items-start justify-content-between gap-3 mb-3">
                                <div><h3 class="co-panel-title">Contratos e atividades</h3><div class="co-panel-sub">Base usada no cadastro dos usuários.</div></div>
                                <button type="button" class="btn btn-sm btn-primary" wire:click.prevent="newContract"><i class="ri-add-line"></i> Novo contrato</button>
                            </div>
                            @if ($showContractForm)
                                        <div class="border rounded-2 p-3 mb-3 bg-white">
                                            <div class="row g-3">
                                                <div class="col-md-5">
                                                    <label class="form-label">Numero do contrato</label>
                                                    <input type="text" class="form-control" wire:model.defer="contractNumber">
                                                </div>
                                                <div class="col-md-3">
                                                    <label class="form-label">Validade</label>
                                                    <input type="date" class="form-control" wire:model.defer="contractDateEnd">
                                                </div>
                                                <div class="col-md-4">
                                                    <label class="form-label d-block">Tipo</label>
                                                    <div class="d-flex gap-3">
                                                        <div class="form-check form-switch">
                                                            <input class="form-check-input" type="checkbox" id="companyContractService" wire:model="contractService">
                                                            <label class="form-check-label" for="companyContractService">Serviços</label>
                                                        </div>
                                                        <div class="form-check form-switch">
                                                            <input class="form-check-input" type="checkbox" id="companyContractConstruction" wire:model="contractConstruction">
                                                            <label class="form-check-label" for="companyContractConstruction">Construção</label>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-12">
                                                    <div class="d-flex flex-wrap gap-2 align-items-center justify-content-between mb-2">
                                                        <strong>Atividades liberadas</strong>
                                                        <input type="search" class="form-control form-control-sm" style="max-width: 260px;" placeholder="Buscar atividade" wire:model.debounce.300ms="contractActivitySearch">
                                                    </div>
                                                    <div class="row g-2" style="max-height: 320px; overflow:auto;">
                                                        @forelse ($services_l as $activity)
                                                            <div class="col-md-6 col-xl-4" wire:key="company_contract_activity_{{ $activity->id }}">
                                                                <div class="border rounded-2 p-3 h-100">
                                                                    <div class="d-flex align-items-start gap-2">
                                                                        <input class="form-check-input mt-1" type="checkbox" value="{{ $activity->id }}" wire:model="contractSelectedServices" id="company_activity_{{ $activity->id }}">
                                                                        <div class="flex-grow-1">
                                                                            <label class="fw-bold mb-1" for="company_activity_{{ $activity->id }}">{{ $activity->service }}</label>
                                                                            <div class="d-flex flex-wrap gap-1">
                                                                                @if ($activity->project)
                                                                                    <span class="badge text-bg-primary">Projeto</span>
                                                                                @endif
                                                                                @if ($activity->construction)
                                                                                    <span class="badge text-bg-secondary">Construcao</span>
                                                                                @endif
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                    <div class="form-check form-switch mt-3">
                                                                        <input class="form-check-input" type="checkbox" wire:model.defer="contractServiceDispatch.{{ $activity->id }}" id="company_dispatch_{{ $activity->id }}" @disabled(!in_array((string) $activity->id, $contractSelectedServices, true) && !in_array($activity->id, $contractSelectedServices, true))>
                                                                        <label class="form-check-label" for="company_dispatch_{{ $activity->id }}">Permitir despacho</label>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        @empty
                                                            <div class="col-12 text-center text-muted py-3">Nenhuma atividade encontrada.</div>
                                                        @endforelse
                                                    </div>
                                                </div>
                                                <div class="col-12 d-flex justify-content-end gap-2">
                                                    <button type="button" class="btn btn-outline-secondary" wire:click.prevent="cancelContract">Cancelar</button>
                                                    <button type="button" class="btn btn-primary" wire:click.prevent="saveContract">
                                                        <i class="ri-save-line"></i> Salvar contrato
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    @endif

                                    <div class="table-responsive">
                                        <table class="table table-sm align-middle mb-0">
                                            <thead>
                                                <tr>
                                                    <th>Contrato</th>
                                                    <th>Validade</th>
                                                    <th>Tipo</th>
                                                    <th>Atividades</th>
                                                    <th class="text-end"></th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @forelse ($company?->contracts ?? [] as $contract)
                                                    <tr wire:key="company_contract_row_{{ $contract->id }}">
                                                        <td class="fw-bold">{{ $contract->number }}</td>
                                                        <td>{{ $contract->date_end ? date('d/m/Y', strtotime($contract->date_end)) : '-' }}</td>
                                                        <td>
                                                            @if ($contract->service)
                                                                <span class="badge text-bg-primary">Serviços</span>
                                                            @endif
                                                            @if ($contract->construction)
                                                                <span class="badge text-bg-secondary">Construção</span>
                                                            @endif
                                                        </td>
                                                        <td>
                                                            <div class="d-flex flex-wrap gap-1">
                                                                @forelse ($contract->services->take(4) as $service)
                                                                    <span class="badge text-bg-light border">
                                                                        {{ $service->service }}
                                                                        @if ($service->pivot->dispatch)
                                                                            <span class="text-danger">/ despacho</span>
                                                                        @endif
                                                                    </span>
                                                                @empty
                                                                    <span class="text-muted small">Sem atividades</span>
                                                                @endforelse
                                                                @if ($contract->services->count() > 4)
                                                                    <span class="badge text-bg-secondary">+{{ $contract->services->count() - 4 }}</span>
                                                                @endif
                                                            </div>
                                                        </td>
                                                        <td class="text-end">
                                                            <button type="button" class="btn btn-sm btn-primary" wire:click.prevent="editContract({{ $contract->id }})">
                                                                <i class="ri-pencil-line"></i>
                                                            </button>
                                                            <button type="button" class="btn btn-sm btn-danger" wire:click.prevent="confirmRemoveContract({{ $contract->id }})">
                                                                <i class="ri-delete-bin-line"></i>
                                                            </button>
                                                        </td>
                                                    </tr>
                                                @empty
                                                    <tr>
                                                        <td colspan="5" class="text-center text-muted py-3">Nenhum contrato cadastrado.</td>
                                                    </tr>
                                                @endforelse
                                            </tbody>
                                        </table>
                                    </div>

                        </section>

                        {{-- UNIDADES --}}
                        <section x-show="tab === 'unidades'" x-cloak>
                            <div class="d-flex align-items-start justify-content-between gap-3 mb-3">
                                <div>
                                    <h3 class="co-panel-title">Unidades</h3>
                                    <div class="co-panel-sub">
                                        @if ($company?->parent)
                                            Esta empresa é unidade de {{ $company->parent->name }}.
                                        @else
                                            Agrupe filiais sem duplicar a empresa principal.
                                        @endif
                                    </div>
                                </div>
                                <button type="button" class="btn btn-sm btn-primary" wire:click.prevent="newBranch"><i class="ri-add-line"></i> Nova unidade</button>
                            </div>
                            @if ($showBranchForm)
                                        <div class="border rounded-2 p-3 mb-3 bg-white">
                                            <div class="row g-3 align-items-end">
                                                <div class="col-md-4">
                                                    <label class="form-label">Nome da unidade</label>
                                                    <input type="text" class="form-control" wire:model.defer="branchName" placeholder="Linhares, São Mateus...">
                                                </div>
                                                <div class="col-md-4">
                                                    <label class="form-label">Email</label>
                                                    <input type="email" class="form-control" wire:model.defer="branchEmail">
                                                </div>
                                                <div class="col-md-2">
                                                    <label class="form-label">Telefone</label>
                                                    <input type="text" class="form-control" wire:model.defer="branchTelephone">
                                                </div>
                                                <div class="col-md-2 d-flex gap-2">
                                                    <button type="button" class="btn btn-outline-secondary" wire:click.prevent="cancelBranch">Cancelar</button>
                                                    <button type="button" class="btn btn-primary" wire:click.prevent="saveBranch">Salvar</button>
                                                </div>
                                            </div>
                                        </div>
                                    @endif

                                    <div class="border rounded-2 p-3 mb-3 bg-white">
                                        <div class="d-flex align-items-center justify-content-between mb-3">
                                            <div>
                                                <strong>Concentradora atual</strong>
                                                <div class="text-muted small">{{ $company?->parent?->name ?: $company?->name }}</div>
                                            </div>
                                            <span class="badge text-bg-light border">A empresa aberta não aparece na lista de associação</span>
                                        </div>
                                        <div class="row g-2 align-items-end">
                                            <div class="col-md-4">
                                                <label class="form-label">Buscar outra empresa existente</label>
                                                <input type="search" class="form-control" wire:model.debounce.300ms="branchSearch" placeholder="Ex.: Satel São Paulo">
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">Associar como unidade</label>
                                                <select class="form-select" wire:model="branchAttachId">
                                                    <option value="">Selecione uma empresa existente</option>
                                                    @foreach ($branchCandidates as $candidate)
                                                        <option value="{{ $candidate->id }}">{{ $candidate->name }} · {{ $candidate->email }}</option>
                                                    @endforeach
                                                </select>
                                                @if ($branchSearch && !$branchCandidates->count())
                                                    <div class="text-muted small mt-1">Nenhuma empresa livre encontrada com esse termo.</div>
                                                @endif
                                            </div>
                                            <div class="col-md-2 d-grid">
                                                <button type="button" class="btn btn-outline-primary" wire:click.prevent="attachExistingBranch" @disabled(!$branchAttachId)>
                                                    <i class="ri-links-line"></i> Associar
                                                </button>
                                            </div>
                                        </div>
                                    </div>

                                    @if ($company?->branches?->count())
                                        <div class="row g-2">
                                            @foreach ($company->branches as $branch)
                                                <div class="col-md-6 col-xl-4" wire:key="branch_{{ $branch->id }}">
                                                    <div class="border rounded-2 p-3 h-100 bg-white">
                                                        <div class="d-flex align-items-center gap-2">
                                                            <img src="{{ $branch->logo_url }}" alt="Logo da unidade" style="width: 38px; height: 38px; object-fit: contain; border-radius: 8px; background:#0f172a; padding:4px;">
                                                            <div>
                                                                <div class="fw-bold">{{ $branch->name }}</div>
                                                                <div class="text-muted small">{{ $branch->Address->first()?->city ?: $branch->email }}</div>
                                                            </div>
                                                        </div>
                                                        <div class="d-flex gap-2 mt-3 small text-muted">
                                                            <span>{{ $branch->contracts->count() }} contratos</span>
                                                            <span>{{ $branch->contracts->flatMap(fn ($contract) => $contract->services)->unique('id')->count() }} atividades</span>
                                                        </div>
                                                        <div class="mt-3 text-end">
                                                            <button type="button" class="btn btn-sm btn-outline-secondary" wire:click.prevent="detachBranch('{{ $branch->id }}')">
                                                                <i class="ri-link-unlink"></i> Desassociar
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    @else
                                        <div class="text-muted small">Nenhuma unidade cadastrada.</div>
                                    @endif

                        </section>

                        {{-- PERMISSÕES --}}
                        <section x-show="tab === 'permissoes'" x-cloak>
                            <div class="d-flex align-items-start justify-content-between gap-3 mb-3">
                                <div>
                                    <h3 class="co-panel-title">Permissões liberadas pelo contratante</h3>
                                    <div class="co-panel-sub">Configuração da matriz/concentradora usada como teto para os admins da empresa.</div>
                                </div>
                                @if (!$partnerGrantConfigured)
                                    <span class="badge text-bg-info">Acesso legado completo</span>
                                @endif
                            </div>
                            <div class="row g-3">
                                        @foreach ($partnerPermissionCatalog as $groupKey => $group)
                                            <div class="col-md-6 col-xl-4" wire:key="contractor_permission_group_{{ $groupKey }}">
                                                <div class="perm-card h-100">
                                                    <div class="perm-card-head form-check form-switch mb-0">
                                                        <input class="form-check-input perm-group" type="checkbox"
                                                            id="contractor_permission_{{ $groupKey }}"
                                                            wire:model.defer="partnerGrantPermissions.{{ $this->permissionInputKey($groupKey) }}">
                                                        <label class="form-check-label fw-bold" for="contractor_permission_{{ $groupKey }}">
                                                            {{ $group['label'] }}
                                                        </label>
                                                        <span class="perm-bulk ms-auto" x-data>
                                                            <button type="button" class="perm-link" @click="$el.closest('.perm-card').querySelectorAll('.perm-items input[type=checkbox]').forEach(c => { if (!c.checked) { c.checked = true; c.dispatchEvent(new Event('change', { bubbles: true })); } })">todos</button>
                                                            <button type="button" class="perm-link" @click="$el.closest('.perm-card').querySelectorAll('.perm-items input[type=checkbox]').forEach(c => { if (c.checked) { c.checked = false; c.dispatchEvent(new Event('change', { bubbles: true })); } })">nenhum</button>
                                                        </span>
                                                    </div>

                                                    <div class="perm-items row g-1">
                                                        @foreach ($group['items'] as $permissionKey => $label)
                                                            <div class="col-12" wire:key="contractor_permission_item_{{ $permissionKey }}">
                                                                <div class="form-check">
                                                                    <input class="form-check-input" type="checkbox"
                                                                        id="contractor_permission_{{ str_replace('.', '_', $permissionKey) }}"
                                                                        wire:model.defer="partnerGrantPermissions.{{ $this->permissionInputKey($permissionKey) }}">
                                                                    <label class="form-check-label small" for="contractor_permission_{{ str_replace('.', '_', $permissionKey) }}">
                                                                        {{ $label }}
                                                                    </label>
                                                                </div>
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>

                        </section>

                        {{-- CADASTRO EM MASSA --}}
                        <section x-show="tab === 'massa'" x-cloak>
                            <div class="d-flex align-items-start justify-content-between gap-3 mb-3">
                                <div><h3 class="co-panel-title">Cadastro em massa</h3><div class="co-panel-sub">Baixe a ficha, preencha os usuários e processe a validação antes de gravar.</div></div>
                                <button type="button" class="btn btn-sm btn-outline-success" wire:click.prevent="downloadUserRegistrationWorkbook" wire:loading.attr="disabled" wire:target="downloadUserRegistrationWorkbook">
                                    <i class="ri-file-excel-2-line" wire:loading.remove wire:target="downloadUserRegistrationWorkbook"></i>
                                    <span class="spinner-border spinner-border-sm" wire:loading wire:target="downloadUserRegistrationWorkbook"></span>
                                    Baixar ficha
                                </button>
                            </div>
                            <div class="co-card">
                                <div class="row g-3 align-items-end">
                                        <div class="col-lg-5">
                                            <label class="form-label">Ficha preenchida</label>
                                            <input type="file" class="form-control" wire:model="registrationWorkbook" accept=".xlsx,.xls">
                                            @error('registrationWorkbook')
                                                <div class="text-danger small mt-1">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        <div class="col-lg-3 d-grid">
                                            <button type="button" class="btn btn-primary" wire:click.prevent="processUserRegistrationWorkbook" wire:loading.attr="disabled" wire:target="processUserRegistrationWorkbook,registrationWorkbook">
                                                <span class="spinner-border spinner-border-sm" wire:loading wire:target="processUserRegistrationWorkbook,registrationWorkbook"></span>
                                                <i class="ri-shield-check-line" wire:loading.remove wire:target="processUserRegistrationWorkbook,registrationWorkbook"></i>
                                                Processar ficha
                                            </button>
                                        </div>
                                        @if ($registrationValidation)
                                            @php($summary = $registrationValidation['summary'] ?? [])
                                            <div class="col-lg-4">
                                                <div class="d-flex flex-wrap gap-2">
                                                    <span class="badge text-bg-success">Usuários válidos: {{ $summary['users_valid'] ?? 0 }}</span>
                                                    <span class="badge text-bg-danger">Usuários erro: {{ $summary['users_invalid'] ?? 0 }}</span>
                                                    <span class="badge text-bg-success">Filiais válidas: {{ $summary['units_valid'] ?? 0 }}</span>
                                                    <span class="badge text-bg-danger">Filiais erro: {{ $summary['units_invalid'] ?? 0 }}</span>
                                                </div>
                                            </div>
                                            <div class="col-12 d-flex flex-wrap gap-2">
                                                <button type="button" class="btn btn-outline-danger" wire:click.prevent="exportUserRegistrationErrors" @disabled(!($summary['users_invalid'] ?? 0) && !($summary['units_invalid'] ?? 0))>
                                                    <i class="ri-file-warning-line"></i> Exportar inconsistências
                                                </button>
                                                <button type="button" class="btn btn-success" wire:click.prevent="confirmUserRegistrationWorkbook" @disabled(!($summary['users_valid'] ?? 0) && !($summary['units_valid'] ?? 0))>
                                                    <i class="ri-check-line"></i> Confirmar válidos
                                                </button>
                                            </div>
                                        @endif
                                    </div>

                            </div>
                        </section>
                    </div>
                </div>

                <div class="co-foot">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Fechar</button>
                    <button type="button" class="btn btn-primary" wire:click.prevent="save"><i class="ri-save-line"></i> Salvar alterações</button>
                </div>
            </div>
        </div>
    </div>
</div>
