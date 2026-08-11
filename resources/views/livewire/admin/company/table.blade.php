<div>
    <x-show-loading />

    <style>
        .company-hero {
            background: linear-gradient(130deg, #0f172a 0%, #0f766e 78%);
            border-radius: 8px;
            color: #fff;
            padding: 22px;
        }

        .company-stat {
            background: rgba(255, 255, 255, .1);
            border: 1px solid rgba(255, 255, 255, .16);
            border-radius: 8px;
            padding: 14px;
        }

        .company-shell {
            max-width: 1480px;
            margin: 0 auto;
        }

        .company-logo {
            width: 54px;
            height: 54px;
            object-fit: contain;
            background: #0f172a;
            border-radius: 8px;
            padding: 5px;
        }

        .company-table th {
            font-size: .75rem;
            text-transform: uppercase;
            color: #64748b;
            background: #f8fafc;
        }
    </style>

    <div class="container-fluid company-shell">
        <div class="company-hero mb-3">
            <div class="row g-3 align-items-center">
                <div class="col-lg-6">
                    <div class="small text-white-50 mb-1">Administração</div>
                    <h4 class="mb-2">Empresas</h4>
                    <div class="text-white-50">Centralize cadastro, logos, endereços, depósitos, contratos e atividades liberadas.</div>
                </div>
                <div class="col-lg-6">
                    <div class="row g-2">
                        <div class="col-4">
                            <div class="company-stat">
                                <div class="small text-white-50">Total</div>
                                <div class="fs-4 fw-bold">{{ $totalCompanies }}</div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="company-stat">
                                <div class="small text-white-50">Ativas</div>
                                <div class="fs-4 fw-bold">{{ $activeCompanies }}</div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="company-stat">
                                <div class="small text-white-50">Inativas</div>
                                <div class="fs-4 fw-bold">{{ $inactiveCompanies }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm mb-3">
            <div class="card-body">
                <div class="row g-3 align-items-end">
                    <div class="col-lg-6">
                        <label for="search" class="form-label">Buscar</label>
                        <div class="input-group">
                            <span class="input-group-text bg-white"><i class="ri-search-line"></i></span>
                            <input wire:model.debounce.400ms="search" type="search" class="form-control" id="search" placeholder="Empresa, email, município, contrato ou atividade">
                        </div>
                    </div>
                    <div class="col-lg-6 text-lg-end">
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#create_modal">
                            <i class="ri-community-fill"></i> Nova empresa
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="table-responsive">
                <table class="table align-middle mb-0 company-table">
                    <thead>
                        <tr>
                            <th>Empresa</th>
                            <th>Localidade</th>
                            <th>Contato</th>
                            <th>Contratos</th>
                            <th>Atividades</th>
                            <th>Status</th>
                            <th class="text-end"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($companies_l as $company)
                            @php
                                $address = $company->Address->first();
                                $branchActivities = $company->branches->flatMap(fn ($branch) => $branch->contracts->flatMap(fn ($contract) => $contract->services));
                                $activities = $company->contracts
                                    ->flatMap(fn ($contract) => $contract->services)
                                    ->merge($branchActivities)
                                    ->unique('id')
                                    ->values();
                            @endphp
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center gap-3">
                                        <img src="{{ $company->logo_url }}" alt="Logo da Empresa" class="company-logo">
                                        <div>
                                            <div class="fw-bold @if ($company->trashed()) text-decoration-line-through text-danger @endif">{{ $company->name }}</div>
                                            <div class="text-muted small">
                                                {{ $address?->street ?: 'Sem endereço principal' }}
                                                @if ($company->branches->count())
                                                    · {{ $company->branches->count() }} unidades
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div>{{ $address?->city ?: '-' }}</div>
                                    <div class="text-muted small">{{ $address?->uf }}</div>
                                </td>
                                <td>
                                    <div>{{ $company->email ?: '-' }}</div>
                                    <div class="text-muted small">{{ $company->telephone ?: '-' }}</div>
                                </td>
                                <td>
                                    <div class="fw-bold">{{ $company->contracts_count + $company->branches->sum(fn ($branch) => $branch->contracts->count()) }}</div>
                                    <div class="text-muted small">{{ $company->to_users_count }} usuários diretos</div>
                                </td>
                                <td>
                                    <div class="d-flex flex-wrap gap-1">
                                        @forelse ($activities->take(3) as $activity)
                                            <span class="badge text-bg-light border">{{ $activity->service }}</span>
                                        @empty
                                            <span class="text-muted small">Sem atividade</span>
                                        @endforelse
                                        @if ($activities->count() > 3)
                                            <span class="badge text-bg-secondary">+{{ $activities->count() - 3 }}</span>
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    @if ($company->trashed())
                                        <span class="badge text-bg-danger">Inativa</span>
                                    @else
                                        <span class="badge text-bg-success">Ativa</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <button type="button" class="btn btn-sm btn-light border" wire:click.prevent="$emitTo('admin.company.action.update', 'openModal', '{{ $company->id }}')">
                                        <i class="ri-eye-line"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-primary" wire:click.prevent="$emitTo('admin.company.action.update', 'openModal', '{{ $company->id }}')">
                                        <i class="ri-pencil-fill"></i>
                                    </button>
                                    @if ($company->id !== Auth()->user()->id)
                                        @if (!$company->trashed())
                                            <button type="button" class="btn btn-sm btn-danger" wire:click.prevent="$emit('delete_company', '{{ $company->id }}')">
                                                <i class="ri-delete-bin-2-fill"></i>
                                            </button>
                                        @else
                                            <button type="button" class="btn btn-sm btn-danger" wire:click.prevent="$emit('undelete_company', '{{ $company->id }}')">
                                                <i class="ri-arrow-go-back-line"></i>
                                            </button>
                                        @endif
                                    @endif
                                </td>
                            </tr>
                            @foreach ($company->branches as $branch)
                                @php
                                    $branchAddress = $branch->Address->first();
                                    $branchActivities = $branch->contracts->flatMap(fn ($contract) => $contract->services)->unique('id')->values();
                                @endphp
                                <tr class="bg-light" wire:key="company_branch_row_{{ $branch->id }}">
                                    <td>
                                        <div class="d-flex align-items-center gap-3 ps-4">
                                            <i class="ri-corner-down-right-line text-muted"></i>
                                            <img src="{{ $branch->logo_url }}" alt="Logo da Unidade" class="company-logo" style="width: 42px; height: 42px;">
                                            <div>
                                                <div class="fw-bold @if ($branch->trashed()) text-decoration-line-through text-danger @endif">{{ $branch->name }}</div>
                                                <div class="text-muted small">Unidade de {{ $company->name }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div>{{ $branchAddress?->city ?: '-' }}</div>
                                        <div class="text-muted small">{{ $branchAddress?->uf }}</div>
                                    </td>
                                    <td>
                                        <div>{{ $branch->email ?: '-' }}</div>
                                        <div class="text-muted small">{{ $branch->telephone ?: '-' }}</div>
                                    </td>
                                    <td>
                                        <div class="fw-bold">{{ $branch->contracts->count() }}</div>
                                        <div class="text-muted small">unidade</div>
                                    </td>
                                    <td>
                                        <div class="d-flex flex-wrap gap-1">
                                            @forelse ($branchActivities->take(3) as $activity)
                                                <span class="badge text-bg-light border">{{ $activity->service }}</span>
                                            @empty
                                                <span class="text-muted small">Sem atividade</span>
                                            @endforelse
                                            @if ($branchActivities->count() > 3)
                                                <span class="badge text-bg-secondary">+{{ $branchActivities->count() - 3 }}</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td>
                                        @if ($branch->trashed())
                                            <span class="badge text-bg-danger">Inativa</span>
                                        @else
                                            <span class="badge text-bg-success">Ativa</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        <button type="button" class="btn btn-sm btn-light border" wire:click.prevent="$emitTo('admin.company.action.update', 'openModal', '{{ $branch->id }}')">
                                            <i class="ri-eye-line"></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-primary" wire:click.prevent="$emitTo('admin.company.action.update', 'openModal', '{{ $branch->id }}')">
                                            <i class="ri-pencil-fill"></i>
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-5">Nenhuma empresa encontrada.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="card-footer bg-white">
                {{ $companies_l->links() }}
            </div>
        </div>
    </div>

    @livewire('admin.company.delete')

    <div wire:ignore.self class="modal fade" id="create_modal" tabindex="-1" aria-labelledby="create" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content border-0 shadow">
                <div class="modal-header text-white" style="background: linear-gradient(130deg, #0f172a 0%, #0f766e 80%);">
                    <h1 class="modal-title fs-5" id="exampleModalLabel"><i class="ri-community-fill fs-4 align-middle"></i> Criar empresa</h1>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body bg-light">
                    @livewire('admin.company.create', key(hash('ripemd160', now())))
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                    <button type="button" class="btn btn-primary" wire:click.prevent="$emit('save_create_company')">Salvar</button>
                </div>
            </div>
        </div>
    </div>

    @livewire('admin.company.action.update', key('company_update'))
</div>
