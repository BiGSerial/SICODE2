@php
    use App\Helpers\SelectOptions;
@endphp
<div class="workreports-container">
    <x-show-loading />

    <!-- Search Note Section -->
    @if (!$this->note)
        <div class="card shadow-sm rounded-3 mx-auto" style="max-width: 30rem;">
            <div class="card-body p-4">
                <div class="text-center mb-4">
                    <h5 class="fw-bold">BUSCAR OBRA</h5>
                    <div class="input-group mb-3">
                        <input class="form-control form-control-lg border-primary" type="text"
                            placeholder="Digite Nota, OV, Ordem ou Diagrama" aria-label="Search Note"
                            wire:model.defer="search">
                        <button type="button" class="btn btn-primary" wire:click.prevent="search()">
                            <i class="ri-search-line me-1"></i>BUSCAR
                        </button>
                    </div>
                </div>

                @if ($notes && $notes->count())
                    <div class="search-results">
                        <h6 class="fw-bold mb-3">SELECIONE UMA OBRA PARA INFORMAR</h6>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr class="table-light">
                                        <th>Nota</th>
                                        <th>Ordens</th>
                                        <th>Viabilidade</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($notes as $note)
                                        <tr wire:key="{{ $note->id }}"
                                            wire:click="toConfirmWork({{ $note }})"
                                            class="{{ !$note->WorkForm ? 'cursor-pointer hover-highlight' : 'text-muted' }}"
                                            title="{{ !$note->WorkForm ? 'Clique para informar esta obra' : 'Esta obra já foi informada' }}">
                                            <td class="fw-bold align-middle">{{ $note->note }}</td>
                                            <td class="align-middle">
                                                @if ($note->Orders->count())
                                                    @foreach ($note->Orders->filter(function ($order) {
        return !(strpos($order->statusSist, 'ENT') === 0 || strpos($order->statusSist, 'ENC') === 0);
    }) as $order)
                                                        <span
                                                            class="badge bg-light text-dark mb-1">{{ $order->ordem }}</span>
                                                    @endforeach
                                                @endif
                                            </td>
                                            <td class="align-middle">
                                                @if ($note->Viabilities->count())
                                                    <span
                                                        class="badge {{ $note->Viabilities->last()->completed ? 'bg-success' : 'bg-warning text-dark' }}">
                                                        {{ $note->Viabilities->last()->completed ? 'VIABILIZADO' : 'NÃO VIABILIZADO' }}
                                                    </span>
                                                @else
                                                    <span class="badge bg-secondary">SEM VIABILIDADE</span>
                                                @endif
                                            </td>
                                            <td class="align-middle">
                                                <span
                                                    class="badge {{ $note->WorkForm ? 'bg-success' : 'bg-info text-dark' }}">
                                                    {{ $note->WorkForm ? 'INFORMADA' : 'NÃO INFORMADA' }}
                                                </span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    @endif

    <!-- Work Report Form -->
    @if ($this->note)
        <form wire:submit.prevent="submit">
            <div class="container">
                <div class="card shadow-sm">
                    <div class="card-header edp-bg-sprucegreen-70 text-edp-verde py-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <h4 class="mb-0"><i class="ri-file-list-3-line me-2"></i>INFORME DE ENTREGA DE OBRA</h4>
                            <button type="button" class="btn btn-outline-secondary btn-sm" wire:click="calcelForm()">
                                <i class="ri-arrow-left-line"></i> Voltar
                            </button>
                        </div>
                    </div>
                    <div class="card-body p-4">
                        <!-- Note Data Card -->
                        <div class="card mb-4 shadow-sm">
                            <div
                                class="card-header py-2 edp-bg-sprucegreen-70 text-edp-verde d-flex justify-content-between align-items-center">
                                <h5 class="my-1">Dados da Nota</h5>
                                <span class="badge bg-primary">{{ $note->note }}</span>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-striped-columns mb-0">
                                    <tbody>
                                        <tr>
                                            <td class="align-middle text-end fw-bold" style="width: 150px;">Nota/Ov</td>
                                            <td class="align-middle">{{ $note->note }}</td>
                                            <td class="align-middle text-end fw-bold" style="width: 150px;">Rubrica</td>
                                            <td class="align-middle">{{ $note->rubrica }}</td>
                                        </tr>
                                        <tr>
                                            <td class="align-middle text-end fw-bold">Município</td>
                                            <td class="align-middle">{{ $note->lexp }}</td>
                                            <td class="align-middle text-end fw-bold">Centro Trabalho</td>
                                            <td class="align-middle">{{ $note->centerjob }}</td>
                                        </tr>
                                        <tr>
                                            <td class="align-middle text-end fw-bold">Group1</td>
                                            <td class="align-middle">{{ $note->group1 }}</td>
                                            <td class="align-middle text-end fw-bold">Group2</td>
                                            <td class="align-middle">{{ $note->group2 }}</td>
                                        </tr>
                                        <tr>
                                            <td class="align-middle text-end fw-bold">Group3</td>
                                            <td class="align-middle">{{ $note->group3 }}</td>
                                            <td class="align-middle text-end fw-bold">Group5</td>
                                            <td class="align-middle">{{ $note->group5 }}</td>
                                        </tr>
                                        <tr>
                                            <td class="align-middle text-end fw-bold">Status Atual</td>
                                            <td class="align-middle" colspan="3">
                                                <span class="badge bg-primary">{{ $note->nstats }}</span>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Orders Section -->
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <div class="card shadow-sm h-100">
                                    <div class="card-header bg-light">
                                        <h5 class="card-title mb-0">
                                            <i class="ri-list-check me-2"></i>Ordens Relacionadas
                                            <span class="text-danger">*</span>
                                        </h5>
                                    </div>
                                    <div class="card-body">
                                        <p class="text-muted small">
                                            Adicione todas as Ordens que constem no projeto.
                                            <i class="ri-question-line text-primary" data-bs-toggle="tooltip"
                                                title="Selecione todas as ordens de serviço associadas a esta obra"></i>
                                        </p>

                                        <div class="input-group mb-3">
                                            <select class="form-select" aria-label="Selecionar ordem"
                                                wire:model.defer="s_order">
                                                <option value="">Selecionar ordem</option>
                                                @if ($note->Orders->count())
                                                    @foreach ($note->Orders->filter(function ($order) {
        return !(strpos($order->statusSist, 'ENT') === 0 || strpos($order->statusSist, 'ENC') === 0);
    }) as $order)
                                                        <option value="{{ $order->id }}">{{ $order->ordem }}
                                                        </option>
                                                    @endforeach
                                                @endif
                                            </select>
                                            <button type="button" class="btn btn-primary" wire:click="addOrders()">
                                                <i class="ri-add-line"></i> Adicionar
                                            </button>
                                        </div>

                                        @if (!empty($temp_orders))
                                            <div class="table-responsive mt-3">
                                                <table class="table table-sm table-hover">
                                                    <thead>
                                                        <tr class="table-light">
                                                            <th class="text-center">Ordem</th>
                                                            <th class="text-center">Ação</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach ($temp_orders as $index => $t_order)
                                                            <tr>
                                                                <td class="text-center">{{ $t_order['ordem'] }}</td>
                                                                <td class="text-center">
                                                                    <button type="button"
                                                                        class="btn btn-sm btn-danger"
                                                                        wire:click="remOrders({{ $index }})">
                                                                        <i class="ri-delete-bin-2-line"></i>
                                                                    </button>
                                                                </td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                        @else
                                            <div class="alert alert-warning text-center">
                                                <i class="ri-information-line me-2"></i>Nenhuma ordem associada
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>

                        @if (session()->has('message'))
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <i class="ri-check-double-line me-2"></i>{{ session('message') }}
                                <button type="button" class="btn-close" data-bs-dismiss="alert"
                                    aria-label="Close"></button>
                            </div>
                        @endif

                        @if (!empty($temp_orders))
                            <!-- Main Form Fields -->
                            <div class="row g-4">
                                <!-- Date Field -->
                                <div class="col-md-4">
                                    <div class="form-floating mb-3">
                                        <input type="date"
                                            class="form-control @error('form.date') is-invalid @enderror"
                                            id="dateWork" max="{{ date('Y-m-d') }}" wire:model.defer="form.date">
                                        <label for="dateWork">Data Conclusão da Obra <span
                                                class="text-danger">*</span></label>
                                        @error('form.date')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <!-- Equipment Selection -->
                                <div class="col-md-4">
                                    <div class="form-floating mb-3">
                                        <select class="form-select @error('form.equipment') is-invalid @enderror"
                                            wire:model="form.equipment">
                                            <option value="">Selecione</option>
                                            <option value="1">Sim</option>
                                            <option value="0">Não</option>
                                        </select>
                                        <label>Houve Instalação/Desinstalação de Equipamento? <span
                                                class="text-danger">*</span></label>
                                        @error('form.equipment')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <!-- Project Changes -->
                                <div class="col-md-4">
                                    <div class="form-floating mb-3">
                                        <select class="form-select @error('form.changes') is-invalid @enderror"
                                            wire:model="form.changes">
                                            <option value="">Selecione</option>
                                            <option value="1">Sim</option>
                                            <option value="0">Não</option>
                                        </select>
                                        <label>Houve Alterações no projeto? <span class="text-danger">*</span></label>
                                        @error('form.changes')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <!-- Equipment Section (Conditional) -->
                            @if ($form['equipment'])
                                <div class="card shadow-sm mb-4 border-primary border-top border-2">
                                    <div class="card-header bg-light">
                                        <h5 class="mb-0"><i class="ri-tools-line me-2"></i>Equipamentos</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="row g-3">
                                            <div class="col-md-3">
                                                <div class="form-floating">
                                                    <select class="form-select" id="type"
                                                        wire:model.defer="model_equipment.type">
                                                        <option value="" selected>Selecione</option>
                                                        @foreach (SelectOptions::getEquipmentOptions() as $item)
                                                            <option value="{{ $item->nick }}">{{ $item->info }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                    <label for="type">Tipo de Equipamento</label>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="form-floating">
                                                    <input type="text" class="form-control" id="patrimony"
                                                        wire:model.defer="model_equipment.patrimony">
                                                    <label for="patrimony">Patrimônio</label>
                                                </div>
                                            </div>
                                            <div class="col-md-2">
                                                <div class="form-floating">
                                                    <select class="form-select"
                                                        wire:model.defer="model_equipment.installed">
                                                        <option value="">Selecione</option>
                                                        <option value="1">Instalação</option>
                                                        <option value="0">Desinstalação</option>
                                                    </select>
                                                    <label>Movimento</label>
                                                </div>
                                            </div>
                                            <div class="col-md-2">
                                                <div class="form-floating">
                                                    <select class="form-select" id="fases"
                                                        wire:model.defer="model_equipment.fases">
                                                        <option value="">Selecione</option>
                                                        @foreach (SelectOptions::getFasesOptions() as $item)
                                                            <option value="{{ $item->nick }}">{{ $item->info }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                    <label for="fases">Fases Ligadas</label>
                                                </div>
                                            </div>
                                            <div class="col-md-2">
                                                <div class="form-floating">
                                                    <input type="text" class="form-control" id="pole"
                                                        wire:model.defer="model_equipment.pole">
                                                    <label for="pole">Poste Referencial</label>
                                                </div>
                                            </div>
                                            <div class="col-12">
                                                <button type="button" class="btn btn-primary"
                                                    wire:click="addEquipment()">
                                                    <i class="ri-add-line me-1"></i> Adicionar Equipamento
                                                </button>
                                            </div>
                                        </div>

                                        @if (!empty($temp_equipment))
                                            <div class="table-responsive mt-4">
                                                <table class="table table-striped table-hover">
                                                    <thead>
                                                        <tr class="table-primary">
                                                            <th scope='col'>Tipo</th>
                                                            <th scope='col'>Patrimônio</th>
                                                            <th scope='col'>Movimento</th>
                                                            <th scope='col'>Fases</th>
                                                            <th scope='col'>Poste</th>
                                                            <th scope='col' width="80">Ação</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach ($temp_equipment as $index => $equip)
                                                            <tr>
                                                                <td>{{ $equip['type'] }}</td>
                                                                <td>{{ $equip['patrimony'] }}</td>
                                                                <td>
                                                                    <span
                                                                        class="badge {{ $equip['installed'] ? 'bg-success' : 'bg-warning text-dark' }}">
                                                                        {{ $equip['installed'] ? 'INSTALAÇÃO' : 'DESINSTALAÇÃO' }}
                                                                    </span>
                                                                </td>
                                                                <td>{{ $equip['fases'] }}</td>
                                                                <td>{{ $equip['pole'] }}</td>
                                                                <td>
                                                                    <button type="button"
                                                                        class="btn btn-sm btn-danger"
                                                                        wire:click="remEquipment({{ $index }})">
                                                                        <i class="ri-delete-bin-2-line"></i>
                                                                    </button>
                                                                </td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                        @else
                                            <div class="alert alert-info mt-3">
                                                <i class="ri-information-line me-2"></i>Nenhum equipamento adicionado
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @endif

                            <!-- File Upload Section (Conditional) -->
                            @if ($form['changes'])
                                <div class="card shadow-sm mb-4 border-primary border-top border-2">
                                    <div class="card-header bg-light">
                                        <h5 class="mb-0"><i class="ri-file-upload-line me-2"></i>Arquivos de
                                            Alterações</h5>
                                    </div>
                                    <div class="card-body">
                                        @livewire('files.manager.create-gen-files', ['note' => $note, 'service' => 'INFORME DE OBRA'], key('files_forms'))
                                    </div>
                                </div>
                            @endif

                            <!-- Observations -->
                            <div class="card shadow-sm mb-4">
                                <div class="card-header bg-light">
                                    <h5 class="mb-0"><i class="ri-information-line me-2"></i>Informações Adicionais
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div class="row g-3">
                                        <div class="col-md-12">
                                            <div class="form-floating">
                                                <textarea class="form-control" id="observacao" style="height: 100px" wire:model.defer="form.observation"></textarea>
                                                <label for="observacao">Observações (Desligamento
                                                    programado/Alterações/Informações Gerais)</label>
                                            </div>
                                        </div>

                                        <div class="col-md-6">
                                            <div class="form-floating">
                                                <select class="form-select @error('form.damage') is-invalid @enderror"
                                                    wire:model="form.damage" id="damage">
                                                    <option value="">Selecione</option>
                                                    <option value="1">Sim</option>
                                                    <option value="0">Não</option>
                                                </select>
                                                <label for="damage">Houveram danos a propriedade de particulares?
                                                    <span class="text-danger">*</span></label>
                                                @error('form.damage')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>

                                        <div class="col-md-6">
                                            <div class="form-floating">
                                                <select
                                                    class="form-select @error('form.connection') is-invalid @enderror"
                                                    wire:model="form.connection" id="connection">
                                                    <option value="">Selecione</option>
                                                    <option value="1">Sim</option>
                                                    <option value="0">Não</option>
                                                </select>
                                                <label for="connection">Ligação foi executada no momento da obra? <span
                                                        class="text-danger">*</span></label>
                                                @error('form.connection')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>

                                        @if ($form['damage'])
                                            <div class="col-md-12">
                                                <div class="form-floating">
                                                    <textarea class="form-control @error('form.description') is-invalid @enderror" id="description" style="height: 100px"
                                                        wire:model.defer="form.description"></textarea>
                                                    <label for="description">Detalhar os Danos Causados e Previsão de
                                                        reparo <span class="text-danger">*</span></label>
                                                    @error('form.description')
                                                        <div class="invalid-feedback">{{ $message }}</div>
                                                    @enderror
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <!-- Meeters Section -->
                            <div class="card shadow-sm mb-4">
                                <div class="card-header bg-light">
                                    <h5 class="mb-0"><i class="ri-dashboard-line me-2"></i>Medidores</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <div class="form-floating">
                                                <select class="form-select @error('meeters') is-invalid @enderror"
                                                    wire:model="meeters">
                                                    <option value="">Selecione</option>
                                                    <option value="1">Sim</option>
                                                    <option value="0">Não</option>
                                                </select>
                                                <label>Foram Instalados Medidores? <span
                                                        class="text-danger">*</span></label>
                                                @error('meeters')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>
                                    </div>

                                    @if ($meeters)
                                        <div class="row g-3 mt-2">
                                            <div class="col-md-3">
                                                <div class="form-floating">
                                                    <input type="text" class="form-control" id="number"
                                                        wire:model.defer="model_meeter.number">
                                                    <label for="number">Número</label>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="form-floating">
                                                    <input type="text" class="form-control" id="borne"
                                                        wire:model.defer="model_meeter.borne">
                                                    <label for="borne">Bornes</label>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="form-floating">
                                                    <select class="form-select" id="m_fases"
                                                        wire:model.defer="model_meeter.fases">
                                                        <option value="">Selecione</option>
                                                        @foreach (SelectOptions::getFasesOptions() as $item)
                                                            <option value="{{ $item->nick }}">{{ $item->info }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                    <label for="m_fases">Fases Ligadas</label>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <button type="button" class="btn btn-primary w-100 h-100"
                                                    wire:click="addMeeters()">
                                                    <i class="ri-add-line me-1"></i> Adicionar Medidor
                                                </button>
                                            </div>
                                        </div>

                                        @if (!empty($temp_meeters))
                                            <div class="table-responsive mt-3">
                                                <table class="table table-striped table-hover">
                                                    <thead>
                                                        <tr class="table-primary">
                                                            <th scope='col'>Número</th>
                                                            <th scope='col'>Borne</th>
                                                            <th scope='col'>Fases</th>
                                                            <th scope='col' width="80">Ação</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach ($temp_meeters as $index => $sMeeter)
                                                            <tr>
                                                                <td>{{ $sMeeter['number'] }}</td>
                                                                <td>{{ $sMeeter['borne'] }}</td>
                                                                <td>{{ $sMeeter['fases'] }}</td>
                                                                <td>
                                                                    <button type="button"
                                                                        class="btn btn-sm btn-danger"
                                                                        wire:click="remMeeters({{ $index }})">
                                                                        <i class="ri-delete-bin-2-line"></i>
                                                                    </button>
                                                                </td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                        @else
                                            <div class="alert alert-info mt-3">
                                                <i class="ri-information-line me-2"></i>Nenhum medidor adicionado
                                            </div>
                                        @endif
                                    @endif
                                </div>
                            </div>

                            <!-- Final Information Section -->
                            <div class="card shadow-sm mb-4">
                                <div class="card-header bg-light">
                                    <h5 class="mb-0"><i class="ri-user-settings-line me-2"></i>Informações de
                                        Conclusão</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <div class="form-floating">
                                                <input type="text"
                                                    class="form-control @error('form.dd') is-invalid @enderror"
                                                    id="dd" wire:model.defer="form.dd">
                                                <label for="dd">Número da DD (Último relacionado a esta obra)
                                                    <span class="text-danger">*</span></label>
                                                @error('form.dd')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-floating">
                                                <input type="text"
                                                    class="form-control @error('form.team') is-invalid @enderror"
                                                    id="team" wire:model.defer="form.team">
                                                <label for="team">Nome da Equipe (WPA) <span
                                                        class="text-danger">*</span></label>
                                                @error('form.team')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-floating">
                                                <input type="text"
                                                    class="form-control @error('form.responsible') is-invalid @enderror"
                                                    id="responsible" wire:model.defer="form.responsible">
                                                <label for="responsible">Encarregado responsável pela execução <span
                                                        class="text-danger">*</span></label>
                                                @error('form.responsible')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-floating">
                                                <input type="text"
                                                    class="form-control @error('form.informer') is-invalid @enderror"
                                                    id="informer" wire:model.defer="form.informer">
                                                <label for="informer">Responsável por este informe <span
                                                        class="text-danger">*</span></label>
                                                @error('form.informer')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Form Buttons -->
                            <div class="d-flex gap-2 mb-4">
                                <button class="btn btn-primary" type="submit">
                                    <i class="ri-save-line me-1"></i> ENVIAR INFORME
                                </button>
                                <button class="btn btn-danger" type="reset" wire:click='calcelForm()'>
                                    <i class="ri-close-line me-1"></i> CANCELAR
                                </button>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </form>
    @endif
</div>

@push('script')
    <script>
        // Initialize tooltips instead of popovers for better mobile experience
        document.addEventListener('livewire:load', function() {
            const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
            [...tooltipTriggerList].map(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl));

            // Prevent form submission on button clicks
            document.querySelectorAll('form button[type="button"]').forEach(button => {
                button.addEventListener('click', function(event) {
                    event.preventDefault();
                });
            });
        });
    </script>
@endpush
@push('css')
    <style>
        .cursor-pointer {
            cursor: pointer;
        }

        .hover-highlight:hover {
            background-color: rgba(13, 110, 253, 0.1);
        }

        /* Enhanced input styling for better visibility */
        .form-control,
        .form-select {
            border-width: 1px !important;
            border-color: #010898 !important;
            box-shadow: 0 0 5px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: #0d6efd;
            box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
        }

        /* Make invalid inputs more noticeable */
        .is-invalid {
            border-color: #dc3545 !important;
            border-width: 2px !important;
            box-shadow: 0 0 0 0.25rem rgba(220, 53, 69, 0.25) !important;
        }

        /* Better styling for floating labels */
        .form-floating>.form-control:focus,
        .form-floating>.form-control:not(:placeholder-shown) {
            padding-top: 1.625rem;
            padding-bottom: 0.625rem;
            border-width: 2px;
        }



        .form-floating>.form-control:-webkit-autofill {
            padding-top: 1.625rem;
            padding-bottom: 0.625rem;
        }

        /* Highlight required fields */
        .form-floating>label span.text-danger {
            font-weight: bold;
        }

        .form-floating>label {
            color: #111112;
        }

        /* Improve search input visibility */
        input[wire\:model\.defer="search"] {
            border-width: 2px;
            height: 50px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        /* Better button styling */
        .btn {
            font-weight: 500;
            border-width: 2px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        /* Add subtle highlight to all form fields */
        .form-floating {
            margin-bottom: 1rem;
        }

        textarea.form-control {
            border-width: 2px;
        }

        /* Add highlight effect on hover */
        .form-control:hover,
        .form-select:hover {
            border-color: #0d6efd;
        }

        .required-field::after {
            content: "*";
            color: red;
            margin-left: 4px;
        }
    </style>
@endpush
