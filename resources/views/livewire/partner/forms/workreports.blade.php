@php
    use App\Helpers\SelectOptions;
@endphp
<div class="workreports-container modern-informe">
    <x-show-loading />

    <!-- Search Note Section -->
    @if (!$this->note)
        <div class="card shadow-sm rounded-4 mx-auto glass-card" style="max-width: 34rem;">
            <div class="card-body p-4">
                <div class="text-center mb-4">
                    <span class="badge badge-soft">Informe de Obra</span>
                    <h5 class="fw-bold mt-2">BUSCAR OBRA</h5>
                    <p class="text-muted small mb-3">Localize por Nota, OV, Ordem ou Diagrama.</p>
                    <div class="input-group input-group-lg mb-3">
                        <span class="input-group-text bg-transparent border-0">
                            <i class="ri-search-line"></i>
                        </span>
                        <input class="form-control border-0 bg-transparent" type="text"
                            placeholder="Ex.: 123456 / OV / Ordem / Diagrama" aria-label="Search Note"
                            wire:model.defer="search">
                        <button type="button" class="btn btn-primary" wire:click.prevent="search()">
                            Buscar
                        </button>
                    </div>
                </div>

                @if ($notes && $notes->count())
                    <div class="search-results">
                        <h6 class="fw-bold mb-3">SELECIONE UMA OBRA PARA INFORMAR</h6>
                        <div class="table-responsive">
                            <table class="table table-hover modern-table">
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
                <div class="card shadow-sm modern-hero">
                    <div class="card-body p-4 p-md-5">
                        <div class="d-flex flex-column flex-md-row justify-content-between align-items-start gap-3">
                            <div>
                                <span class="badge badge-soft">{{ ($reinform ?? false) ? 'Reenvio oficial' : 'Entrega oficial' }}</span>
                                <h3 class="fw-bold mb-1">{{ ($reinform ?? false) ? 'Reenviar Informe de Obra' : 'Informe de Entrega de Obra' }}</h3>
                                <p class="text-muted mb-0">Confirme as informações com atenção.</p>
                            </div>
                            <button type="button" class="btn btn-outline-secondary btn-sm"
                                wire:click="calcelForm()">
                                <i class="ri-arrow-left-line"></i> Voltar
                            </button>
                        </div>
                    </div>
                </div>
                <div class="card shadow-sm mt-3">
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

                        @php
                            $activeWorkForm = $note->WorkForm;
                            $latestReturnwork = ($activeWorkForm && $activeWorkForm->rejected)
                                ? ($activeWorkForm->LatestReturnwork
                                    ?? $activeWorkForm->Returnwork()->latest('id')->first())
                                : null;
                        @endphp

                        <!-- Orders Section -->
                        <div class="row mb-4 g-3">
                            <div class="{{ $latestReturnwork ? 'col-md-6' : 'col-md-12' }}">
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

                            @if ($latestReturnwork)
                                <div class="col-md-6">
                                    <div class="card shadow-sm h-100 border-warning border-top border-2">
                                        <div class="card-header bg-warning-subtle">
                                            <h5 class="card-title mb-0">
                                                <i class="ri-error-warning-line me-2"></i>Ultima Rejeicao Ativa
                                            </h5>
                                        </div>
                                        <div class="card-body">
                                            <div class="mb-3">
                                                <div class="text-muted small mb-1">Motivo da rejeicao</div>
                                                <div class="fw-semibold">{{ $latestReturnwork->category ?: 'Nao informado' }}</div>
                                            </div>

                                            <div class="mb-3">
                                                <div class="text-muted small mb-1">Descricao</div>
                                                <div class="bg-light border rounded p-2 small">
                                                    {!! nl2br(e($latestReturnwork->text_obs ?: 'Nao informada')) !!}
                                                </div>
                                            </div>

                                            <div>
                                                <div class="text-muted small mb-1">Rejeitado por</div>
                                                <div>
                                                    <span class="fw-semibold">{{ $latestReturnwork->User?->name ?: 'Nao informado' }}</span>
                                                    <span class="text-muted"> em </span>
                                                    <span class="fw-semibold">
                                                        {{ $latestReturnwork->created_at ? $latestReturnwork->created_at->format('d/m/Y H:i') : 'Nao informado' }}
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </div>

                        @if (session()->has('message'))
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <i class="ri-check-double-line me-2"></i>{{ session('message') }}
                                <button type="button" class="btn-close" data-bs-dismiss="alert"
                                    aria-label="Close"></button>
                            </div>
                        @endif

                        @if (($reinform ?? false) && ($hasTacitAds ?? false))
                            <div class="card shadow-sm mb-4 border-warning border-top border-2">
                                <div class="card-header bg-warning-subtle">
                                    <h5 class="mb-0"><i class="ri-file-warning-line me-2"></i>ADS tácita associada</h5>
                                </div>
                                <div class="card-body">
                                    <p class="mb-0">
                                        Este informe possui ADS tácita. O vencimento de uma ADS tácita não pode ser
                                        alterado pelo reenvio do informe.
                                    </p>
                                </div>
                            </div>
                        @elseif (($reinform ?? false) && ($hasExistingAds ?? false))
                            <div class="card shadow-sm mb-4 border-warning border-top border-2">
                                <div class="card-header bg-warning-subtle">
                                    <h5 class="mb-0"><i class="ri-file-warning-line me-2"></i>ADS já associada</h5>
                                </div>
                                <div class="card-body">
                                    <p class="mb-3">
                                        Já existe uma ADS para este informe. Escolha como deseja tratar essa ADS no
                                        reenvio.
                                    </p>

                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="radio" id="keep_ads_yes"
                                            value="1" wire:model="keepExistingAds">
                                        <label class="form-check-label" for="keep_ads_yes">
                                            Manter ADS existente. A data de entrega da ADS será atualizada para a data
                                            do reenvio do informe e o prazo de fiscalização será contado a partir da nova data.
                                        </label>
                                    </div>

                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" id="keep_ads_no"
                                            value="0" wire:model="keepExistingAds">
                                        <label class="form-check-label" for="keep_ads_no">
                                            Remover ADS existente. Se houver arquivo vinculado, ele será apagado do
                                            servidor junto com a associação da ADS. Será necessário enviar uma nova ADS
                                            pela área <strong>Entregar ADS</strong>, com prazo de 6 dias a partir deste reenvio.
                                        </label>
                                    </div>
                                </div>
                            </div>
                        @endif

                        @if (!empty($temp_orders))
                            <!-- Main Form Fields -->
                            <div class="row g-4">
                                @if ($canSelectCompany)
                                    <div class="col-md-4">
                                        <div class="form-floating mb-3">
                                            <select class="form-select @error('form.company_id') is-invalid @enderror"
                                                wire:model.defer="form.company_id">
                                                <option value="">Selecione</option>
                                                @foreach ($companies as $company)
                                                    <option value="{{ $company->id }}">{{ $company->name }}</option>
                                                @endforeach
                                            </select>
                                            <label>Empreiteira do Informe <span class="text-danger">*</span></label>
                                            @error('form.company_id')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                @endif

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

                            <div class="card shadow-sm mb-4 border-primary border-top border-2">
                                <div class="card-header bg-light">
                                    <h5 class="mb-0"><i class="ri-file-upload-line me-2"></i>Arquivos de
                                        Evidencias de Obras</h5>
                                </div>
                                <div class="card-body">
                                    <div class="alert alert-info d-flex align-items-start" role="alert">
                                        <i class="ri-information-line me-2 fs-5"></i>
                                        <div>
                                            <strong>Atenção:</strong> Apartir de <strong>01/12/2025</strong> torna-se
                                            obrigatório anexar as evidências de realização das obras, incluindo fotos
                                            dos ativos cadastrados e instalados.
                                        </div>
                                    </div>
                                    @if (!($reinform ?? false))
                                        <div class="alert alert-warning d-flex align-items-start" role="alert">
                                            <i class="ri-file-warning-line me-2 fs-5"></i>
                                            <div>
                                                <strong>ASBUILT obrigatório:</strong> anexe o ASBUILT de acordo com a
                                                informação declarada em <strong>Houve Alterações no projeto?</strong>.
                                                Se houve alteração, anexe o ASBUILT com as alterações executadas. Se não
                                                houve alteração, o executor declara, sob sua responsabilidade, que a obra foi
                                                executada conforme o projeto original, devendo anexar o projeto seguido da
                                                informação <strong>executado conforme projeto</strong> registrada no ASBUILT.
                                                Informações divergentes da execução realizada em campo poderão acarretar
                                                retrabalho, reprovação no encerramento da obra e aplicação das tratativas e
                                                sanções cabíveis.
                                            </div>
                                        </div>
                                    @endif
                                    @if ($showAsbuiltMissingFeedback)
                                        <div class="alert alert-danger d-flex align-items-start border border-danger" role="alert">
                                            <i class="ri-error-warning-line me-2 fs-5"></i>
                                            <div>
                                                @if ($reinform ?? false)
                                                    <strong>ASBUILT obrigatório pela alteração da informação.</strong>
                                                    No informe anterior, <strong>Houve Alterações no projeto?</strong>
                                                    estava marcado como <strong>Não</strong>. Neste reenvio, foi alterado
                                                    para <strong>Sim</strong>. Anexe o ASBUILT e confirme a veracidade
                                                    da informação antes de reenviar.
                                                @else
                                                    <strong>ASBUILT não anexado.</strong> Para enviar o informe, selecione
                                                    <strong>ASBUILT</strong> no Tipo de Envio e anexe o arquivo antes de
                                                    confirmar a entrega.
                                                @endif
                                            </div>
                                        </div>
                                    @endif
                                    @php
                                        $fileUploaderParams = [
                                            'note' => $note,
                                            'service' => 'INFORME DE OBRA',
                                            'manage_existing' => $reinform ?? false,
                                            'existing_file_types' => $existingFileTypes ?? ['ASBUILT', 'CROQUI', 'EVIDENCIA', 'FTVEO', 'IMAGEM', 'LISTA', 'PROJETO', 'OUTROS'],
                                        ];

                                        if (($reinform ?? false) && isset($workReport) && $workReport) {
                                            $fileUploaderParams['work_report'] = $workReport;
                                        }
                                    @endphp

                                    @livewire(
                                        'files.manager.create-gen-files',
                                        $fileUploaderParams,
                                        key('files_forms_' . $note->id . '_' . (($reinform ?? false) ? 'reinform' : 'new'))
                                    )

                                    @if ($this->shouldShowAsbuiltConfirmation())
                                        <div class="alert alert-success d-flex align-items-start mt-3" role="alert">
                                            <i class="ri-checkbox-circle-line me-2 fs-5"></i>
                                            <div class="w-100">
                                                <label class="form-check-label fw-semibold d-flex gap-2 align-items-start">
                                                    <input type="checkbox"
                                                        class="form-check-input mt-1 @error('form.asbuilt_confirmation') is-invalid @enderror"
                                                        id="asbuilt_confirmation"
                                                        wire:model.defer="form.asbuilt_confirmation">
                                                    <span>
                                                        Confirmo que o ASBUILT anexado corresponde à informação
                                                        declarada sobre alteração ou não alteração do projeto.
                                                    </span>
                                                </label>
                                                @error('form.asbuilt_confirmation')
                                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </div>


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

                            <!-- Acceptance Term -->
                            <div class="card shadow-sm mb-4">
                                <div class="card-header bg-light">
                                    <h5 class="mb-0"><i class="ri-shield-check-line me-2"></i>Termo de Aceite</h5>
                                </div>
                                <div class="card-body">
                                    <div class="term-box">
                                        <div class="term-icon">
                                            <i class="ri-shield-check-line"></i>
                                        </div>
                                        <div>
                                            <p class="mb-2 fw-semibold">Declaração de responsabilidade</p>
                                            <p class="text-muted mb-2">
                                                Ao informar a obra no sistema, o usuário está em acordo que as informações
                                                passadas nesse Informe de Conclusão são verdadeiras e não existem divergências.
                                                Tendo ciência que existe um prazo para entrega da ADS conforme previsto em
                                                contrato, que a data do prazo será considerado o momento do envio deste
                                                informe, e não poderá ser contestado posteriormente. Você confirma o
                                                entendimento e ciência dessa informação?
                                            </p>
                                            <div class="term-quote mt-3">
                                                <div class="term-quote-icon">
                                                    <i class="ri-double-quotes-l"></i>
                                                </div>
                                                <div>
                                                    <p class="small text-uppercase fw-semibold mb-2">Citação contratual</p>
                                                    <p class="text-muted mb-0"><em>
                                                        "Conforme estabelecido na Especificação Técnica corporativa <strong>ES.DT.PDN.02.01.006 – Construção e Manutenção em Redes Aéreas de Distribuição – Condições Específicas</strong>, em especial no item <strong>6.3 – Medição dos Serviços e Inventário de Materiais</strong>, a comunicação de conclusão da obra, acompanhada da documentação pertinente, é condição necessária para viabilizar a fiscalização, o aceite dos serviços e o faturamento. Adicionalmente, de acordo com o <strong>item 6.3.4.d</strong>, para a EDP ES, a CONTRATADA dispõe do <strong>prazo de 6 (seis) dias</strong>, contados a partir da conclusão da obra ou serviços, para a entrega do inventário, sendo que, expirado esse prazo,  <strong>prevalecerá o inventário elaborado pela CONTRATANTE</strong>".
                                                    </em></p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row g-3 mt-2">
                                        <div class="col-md-7">
                                            <div class="form-floating">
                                                <input type="text"
                                                    class="form-control @error('form.acceptance_name') is-invalid @enderror"
                                                    id="acceptance_name" wire:model.defer="form.acceptance_name"
                                                    placeholder="Nome completo">
                                                <label for="acceptance_name">Nome completo do aceite <span
                                                        class="text-danger">*</span></label>
                                                @error('form.acceptance_name')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>
                                        <div class="col-md-5 d-flex align-items-center">
                                            <label class="check-pill @error('form.acceptance_accepted') is-invalid @enderror">
                                                <input type="checkbox" id="acceptance_accepted"
                                                    wire:model.defer="form.acceptance_accepted">
                                                <span>Confirmo o aceite do termo</span>
                                            </label>
                                            @error('form.acceptance_accepted')
                                                <div class="invalid-feedback d-block ms-2">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>

                                    <input type="hidden" wire:model.defer="acceptance_meta_json" id="acceptance_meta_json">
                                </div>
                            </div>

                            @if (($reinform ?? false) && !empty($acceptanceHistory))
                                <div class="card shadow-sm mb-4">
                                    <div class="card-header bg-light">
                                        <h5 class="mb-0"><i class="ri-history-line me-2"></i>Histórico de Aceites</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="table-responsive">
                                            <table class="table table-sm table-striped">
                                                <thead>
                                                    <tr>
                                                        <th>Data</th>
                                                        <th>Nome</th>
                                                        <th>Usuário</th>
                                                        <th>Origem</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach ($acceptanceHistory as $acceptance)
                                                        <tr>
                                                            <td>{{ $acceptance['acceptance_at'] ?? $acceptance['captured_at'] ?? $acceptance['collected_at'] ?? '---' }}</td>
                                                            <td>{{ $acceptance['acceptance_name'] ?? '---' }}</td>
                                                            <td>{{ $acceptance['app_user']['name'] ?? '---' }}</td>
                                                            <td>{{ $acceptance['server_ip'] ?? $acceptance['host'] ?? '---' }}</td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            @endif

                            <!-- Form Buttons -->
                            <div class="d-flex gap-2 mb-4">
                                <button class="btn btn-primary" type="submit">
                                    <i class="ri-save-line me-1"></i> {{ ($reinform ?? false) ? 'REENVIAR INFORME' : 'ENVIAR INFORME' }}
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
            window.addEventListener('swal-redirect', function(e) {
                const detail = e.detail || {};
                const url = detail.url;
                const options = { ...detail };
                delete options.url;

                Swal.fire(options).then(() => {
                    if (url) {
                        window.location.href = url;
                    }
                });
            });

            const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
            [...tooltipTriggerList].map(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl));

            // Prevent form submission on button clicks
            document.querySelectorAll('form button[type="button"]').forEach(button => {
                button.addEventListener('click', function(event) {
                    event.preventDefault();
                });
            });

            function collectMetaBase() {
                const asbuiltConfirmation = document.getElementById('asbuilt_confirmation');

                return {
                    user_agent: navigator.userAgent || null,
                    user_agent_data: navigator.userAgentData ? {
                        brands: navigator.userAgentData.brands || null,
                        mobile: navigator.userAgentData.mobile || null,
                        platform: navigator.userAgentData.platform || null,
                    } : null,
                    platform: navigator.platform || null,
                    language: navigator.language || null,
                    languages: navigator.languages || [],
                    timezone: Intl.DateTimeFormat().resolvedOptions().timeZone || null,
                    cookie_enabled: navigator.cookieEnabled || false,
                    do_not_track: navigator.doNotTrack || null,
                    hardware_concurrency: navigator.hardwareConcurrency || null,
                    device_memory_gb: navigator.deviceMemory || null,
                    screen: {
                        width: window.screen?.width || null,
                        height: window.screen?.height || null,
                        avail_width: window.screen?.availWidth || null,
                        avail_height: window.screen?.availHeight || null,
                        pixel_ratio: window.devicePixelRatio || null,
                        color_depth: window.screen?.colorDepth || null,
                    },
                    window: {
                        width: window.innerWidth || null,
                        height: window.innerHeight || null,
                    },
                    network: navigator.connection ? {
                        effective_type: navigator.connection.effectiveType || null,
                        downlink: navigator.connection.downlink || null,
                        rtt: navigator.connection.rtt || null,
                        save_data: navigator.connection.saveData || false,
                    } : null,
                    touch_support: ('ontouchstart' in window) || (navigator.maxTouchPoints > 0),
                    webdriver: navigator.webdriver || false,
                    browser_online: navigator.onLine,
                    host: window.location?.hostname || null,
                    path: window.location?.pathname || null,
                    referrer: document.referrer || null,
                    local_hostname_available: false,
                    local_os_username_available: false,
                    local_os_username: null,
                    local_machine_hostname: null,
                    local_user_capture_method: null,
                    asbuilt_visual_confirmation: {
                        checkbox_visible: Boolean(asbuiltConfirmation),
                        checkbox_checked: Boolean(asbuiltConfirmation?.checked),
                    },
                    signature_input_version: 'v2',
                };
            }

            function captureLocalUserBestEffort() {
                const local = {
                    local_os_username: null,
                    local_machine_hostname: null,
                    local_os_username_available: false,
                    local_hostname_available: false,
                    local_user_capture_method: null,
                };

                // Tentativa 1: IE/Edge IE Mode com ActiveX habilitado (ambiente corporativo legado)
                try {
                    if (typeof window.ActiveXObject !== 'undefined') {
                        const network = new window.ActiveXObject('WScript.Network');
                        if (network) {
                            local.local_os_username = network.UserName || null;
                            local.local_machine_hostname = network.ComputerName || null;
                            local.local_os_username_available = Boolean(local.local_os_username);
                            local.local_hostname_available = Boolean(local.local_machine_hostname);
                            local.local_user_capture_method = 'activex_wscript_network';
                            return local;
                        }
                    }
                } catch (e) {
                    // sem suporte/permissão de ActiveX
                }

                // Tentativa 2: app desktop/wrapper (ex.: Electron) com acesso controlado a env local
                try {
                    const env = window.process?.env;
                    if (env) {
                        local.local_os_username = env.USERNAME || env.USER || null;
                        local.local_machine_hostname = env.COMPUTERNAME || env.HOSTNAME || null;
                        local.local_os_username_available = Boolean(local.local_os_username);
                        local.local_hostname_available = Boolean(local.local_machine_hostname);
                        local.local_user_capture_method = 'desktop_wrapper_env';
                        return local;
                    }
                } catch (e) {
                    // sem suporte ao objeto process
                }

                local.local_user_capture_method = 'browser_restricted';
                return local;
            }

            function toHex(buffer) {
                return Array.from(new Uint8Array(buffer))
                    .map((b) => b.toString(16).padStart(2, '0'))
                    .join('');
            }

            async function hashText(text) {
                if (window.crypto?.subtle?.digest) {
                    const data = new TextEncoder().encode(text);
                    const hash = await window.crypto.subtle.digest('SHA-256', data);
                    return toHex(hash);
                }

                // Fallback simples para ambientes sem WebCrypto
                let hash = 0;
                for (let i = 0; i < text.length; i++) {
                    hash = ((hash << 5) - hash) + text.charCodeAt(i);
                    hash |= 0;
                }
                return `fallback_${Math.abs(hash)}`;
            }

            async function fillAcceptanceMeta() {
                const metaField = document.getElementById('acceptance_meta_json');

                if (!metaField) {
                    return;
                }

                const base = collectMetaBase();
                const localCapture = captureLocalUserBestEffort();
                Object.assign(base, localCapture);
                const signatureSource = JSON.stringify({
                    user_agent: base.user_agent,
                    platform: base.platform,
                    language: base.language,
                    timezone: base.timezone,
                    screen: base.screen,
                    hardware_concurrency: base.hardware_concurrency,
                    device_memory_gb: base.device_memory_gb,
                    touch_support: base.touch_support,
                    local_os_username: base.local_os_username,
                    local_machine_hostname: base.local_machine_hostname,
                });

                base.device_fingerprint = await hashText(signatureSource);
                base.collected_at = new Date().toISOString();

                metaField.value = JSON.stringify(base);
                metaField.dispatchEvent(new Event('input', { bubbles: true }));
            }

            fillAcceptanceMeta();
        });
    </script>
@endpush
@push('css')
    <style>
        :root {
            --informe-bg: #f2f5f9;
            --informe-card: #ffffff;
            --informe-ink: #0f172a;
            --informe-muted: #64748b;
            --informe-accent: #0b7285;
            --informe-accent-2: #0ea5a4;
            --informe-border: rgba(15, 23, 42, 0.08);
        }

        .modern-informe {
            background: radial-gradient(1000px 400px at 10% 0%, #eaf3ff 0%, transparent 65%),
                        radial-gradient(900px 500px at 90% 10%, #e7fff6 0%, transparent 60%),
                        var(--informe-bg);
            border-radius: 16px;
            padding: 18px;
        }

        .glass-card {
            background: rgba(255, 255, 255, 0.85);
            border: 1px solid var(--informe-border);
            backdrop-filter: blur(6px);
        }

        .badge-soft {
            background: rgba(14, 165, 164, 0.15);
            color: #0f766e;
            border: 1px solid rgba(14, 165, 164, 0.3);
            padding: 6px 10px;
            border-radius: 999px;
            font-weight: 600;
        }

        .modern-hero {
            border: 1px solid var(--informe-border);
            background: linear-gradient(135deg, rgba(11, 114, 133, 0.12), rgba(14, 165, 164, 0.06));
        }

        .modern-table tbody tr:hover {
            background-color: rgba(14, 165, 164, 0.08);
        }

        .term-box {
            display: grid;
            grid-template-columns: 52px 1fr;
            gap: 12px;
            padding: 16px;
            border-radius: 12px;
            border: 1px dashed rgba(14, 165, 164, 0.5);
            background: rgba(14, 165, 164, 0.08);
        }

        .term-icon {
            width: 52px;
            height: 52px;
            border-radius: 12px;
            background: rgba(14, 165, 164, 0.2);
            display: grid;
            place-items: center;
            color: #0f766e;
            font-size: 24px;
        }

        .term-quote {
            display: grid;
            grid-template-columns: 44px 1fr;
            gap: 12px;
            padding: 14px;
            border-radius: 12px;
            border: 1px dashed rgba(14, 165, 164, 0.65);
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.95), rgba(14, 165, 164, 0.1));
            box-shadow: inset 0 0 0 1px rgba(14, 165, 164, 0.15);
        }

        .term-quote-icon {
            width: 44px;
            height: 44px;
            border-radius: 10px;
            background: rgba(14, 165, 164, 0.2);
            display: grid;
            place-items: center;
            color: #0f766e;
            font-size: 20px;
        }

        .term-quote .small {
            color: #0f766e;
            letter-spacing: 0.04em;
        }

        .check-pill {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 10px 14px;
            border-radius: 999px;
            border: 1px solid rgba(15, 23, 42, 0.12);
            background: #ffffff;
            cursor: pointer;
            font-weight: 600;
        }

        .check-pill input {
            width: 18px;
            height: 18px;
        }
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
