<div class="finish-five">
    <x-show-loading />

    <div class="modal fade" id="adminWorkReportModal" tabindex="-1" aria-hidden="true" wire:ignore.self>
        <div class="modal-dialog modal-dialog-centered modal-xl modal-fullscreen-xxl-down fivefx-dialog">
            @if ($workReport)
                <form class="modal-content fivefx-card" wire:submit.prevent="save">
                    <div class="modal-header fivefx-header py-2">
                        <h6 class="modal-title d-flex align-items-center gap-2">
                            <i class="ri-edit-2-line me-1"></i>
                            <span>Editar WorkReport</span>
                            <span class="fivefx-pill">ID: {{ $workReport->id }}</span>
                        </h6>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                    </div>

                    <div class="modal-body fivefx-body">
                        <div class="fivefx-context mb-3">
                            <div>
                                <span class="fivefx-eyebrow">Controle do informe</span>
                                <h5 class="mb-1">Nota {{ $workReport->Note?->note ?? '---' }}</h5>
                                <div class="small text-muted">
                                    {{ $workReport->Company?->name ?? 'Empresa não informada' }}
                                    <span class="mx-1">·</span>
                                    Informe #{{ $workReport->id }}
                                </div>
                            </div>
                            <div class="d-flex align-items-center gap-2 flex-wrap">
                                @if ($workReport->acceptance_accepted)
                                    <span class="badge text-bg-success">Aceite aprovado</span>
                                @else
                                    <span class="badge text-bg-warning">Aceite pendente</span>
                                @endif
                                @foreach ($workReport->finalScopeBadges() as $scope)
                                    <span class="badge {{ $scope['class'] }}">{{ $scope['label'] }}</span>
                                @endforeach
                            </div>
                        </div>

                        <div class="fivefx-tabs mb-3" role="tablist" aria-label="Seções do informe">
                            <button type="button" class="fivefx-tab {{ $activeTab === 'details' ? 'is-active' : '' }}" wire:click="setActiveTab('details')">
                                <i class="ri-file-edit-line me-1"></i>Dados do informe
                            </button>
                            <button type="button" class="fivefx-tab {{ $activeTab === 'files' ? 'is-active' : '' }}" wire:click="setActiveTab('files')">
                                <i class="ri-attachment-2 me-1"></i>Arquivos
                                <span class="badge text-bg-secondary ms-1">{{ count($workReportFiles) }}</span>
                            </button>
                        </div>

                        @if ($activeTab === 'details')
                        <div class="row g-3">
                            <div class="col-12 col-xl-7">
                                <div class="fivefx-section mb-3">
                                    <div class="fivefx-section-title">
                                        <div>
                                            <span class="fivefx-step">01</span>
                                            <span>Dados principais</span>
                                        </div>
                                        <span class="fivefx-hint">Informações de identificação e responsabilidade</span>
                                    </div>

                                    <div class="row g-3">
                                        <div class="col-sm-4">
                                            <label class="form-label fivefx-k">ID da nota</label>
                                            <input type="number" class="form-control fivefx-control" wire:model.defer="workReport.note_id">
                                        </div>
                                        <div class="col-sm-8">
                                            <label class="form-label fivefx-k">Número da nota</label>
                                            <input type="text" class="form-control fivefx-control" value="{{ $workReport->Note?->note ?? '---' }}" readonly>
                                        </div>
                                        <div class="col-sm-6">
                                            <label class="form-label fivefx-k">Empresa</label>
                                            <select class="form-select fivefx-select" wire:model.defer="workReport.company_id">
                                                <option value="">Selecione...</option>
                                                @foreach ($companies as $company)
                                                    <option value="{{ $company->id }}">{{ $company->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-sm-6">
                                            <label class="form-label fivefx-k">Usuário</label>
                                            <select class="form-select fivefx-select" wire:model.defer="workReport.user_id">
                                                <option value="">Selecione...</option>
                                                @foreach ($users as $user)
                                                    <option value="{{ $user->id }}">{{ $user->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-sm-4">
                                            <label class="form-label fivefx-k">Data da obra</label>
                                            <input type="date" class="form-control fivefx-control" wire:model.defer="workReport.date">
                                        </div>
                                        <div class="col-sm-4">
                                            <label class="form-label fivefx-k">DD</label>
                                            <input type="text" class="form-control fivefx-control" wire:model.defer="workReport.dd">
                                        </div>
                                        <div class="col-sm-4">
                                            <label class="form-label fivefx-k">Equipe</label>
                                            <input type="text" class="form-control fivefx-control" wire:model.defer="workReport.team">
                                        </div>
                                        <div class="col-sm-6">
                                            <label class="form-label fivefx-k">Informante</label>
                                            <input type="text" class="form-control fivefx-control" wire:model.defer="workReport.informer">
                                        </div>
                                        <div class="col-sm-6">
                                            <label class="form-label fivefx-k">Responsável</label>
                                            <input type="text" class="form-control fivefx-control" wire:model.defer="workReport.responsible">
                                        </div>
                                    </div>
                                </div>

                                <div class="fivefx-section mb-3">
                                    <div class="fivefx-section-title">
                                        <div>
                                            <span class="fivefx-step">02</span>
                                            <span>Datas e aceite</span>
                                        </div>
                                        <span class="fivefx-hint">Controle de auditoria</span>
                                    </div>

                                    <div class="row g-3">
                                        <div class="col-sm-6">
                                            <label class="form-label fivefx-k">Informado em</label>
                                            <input type="datetime-local" class="form-control fivefx-control" wire:model.defer="informedAt">
                                        </div>
                                        <div class="col-sm-6">
                                            <label class="form-label fivefx-k">Aceite em</label>
                                            <input type="datetime-local" class="form-control fivefx-control" wire:model.defer="acceptanceAt">
                                        </div>
                                        <div class="col-sm-8">
                                            <label class="form-label fivefx-k">Nome do aceite</label>
                                            <input type="text" class="form-control fivefx-control" wire:model.defer="workReport.acceptance_name">
                                        </div>
                                        <div class="col-sm-4 d-flex align-items-end">
                                            <div class="form-check pb-2">
                                                <input class="form-check-input" type="checkbox" id="acceptanceAccepted" wire:model.defer="workReport.acceptance_accepted">
                                                <label class="form-check-label" for="acceptanceAccepted">Aceite aprovado</label>
                                            </div>
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label fivefx-k">Acceptance meta (JSON)</label>
                                            <textarea class="form-control fivefx-control fivefx-textarea fivefx-json" rows="4" wire:model.defer="acceptanceMetaJson"></textarea>
                                            <div class="form-text">Dados técnicos do aceite. Edite somente quando necessário.</div>
                                        </div>
                                    </div>
                                </div>

                                <div class="fivefx-section mb-3">
                                    <div class="fivefx-section-title">
                                        <div>
                                            <span class="fivefx-step">03</span>
                                            <span>Observações</span>
                                        </div>
                                    </div>
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="form-label fivefx-k">Observação</label>
                                            <textarea class="form-control fivefx-control fivefx-textarea" rows="5" wire:model.defer="workReport.observation"></textarea>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label fivefx-k">Descrição</label>
                                            <textarea class="form-control fivefx-control fivefx-textarea" rows="5" wire:model.defer="workReport.description"></textarea>
                                        </div>
                                    </div>
                                </div>

                                <div class="fivefx-section">
                                    <div class="fivefx-section-title">
                                        <div>
                                            <span class="fivefx-step">04</span>
                                            <span>Status e flags</span>
                                        </div>
                                    </div>
                                    <div class="row g-2">
                                        @php
                                            $flags = [
                                                'equipment' => 'Teve equipamento',
                                                'connection' => 'Teve ligação',
                                                'changes' => 'Teve alterações',
                                                'damage' => 'Teve danos',
                                                'approved' => 'Aprovado',
                                                'rejected' => 'Rejeitado',
                                                'retry' => 'Reenvio',
                                            ];
                                        @endphp
                                        @foreach ($flags as $field => $label)
                                            <div class="col-sm-6 col-lg-4">
                                                <label class="fivefx-check">
                                                    <input class="form-check-input" type="checkbox" id="flag-{{ $field }}" wire:model.defer="workReport.{{ $field }}">
                                                    <span>{{ $label }}</span>
                                                </label>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>

                            <div class="col-12 col-xl-5">
                                <div class="fivefx-section mb-3">
                                    <div class="fivefx-section-title">
                                        <div>
                                            <span class="fivefx-step">A</span>
                                            <span>Escopo e informes da obra</span>
                                        </div>
                                    </div>
                                    <div class="fivefx-scope-current mb-3">
                                        <span class="fivefx-k d-block mb-2">Escopo atual</span>
                                        @forelse ($workReport->finalScopeBadges() as $scope)
                                            <span class="badge {{ $scope['class'] }} me-1">{{ $scope['label'] }}</span>
                                        @empty
                                            <span class="badge text-bg-secondary">Geral</span>
                                        @endforelse
                                    </div>
                                    @php
                                        $hasBothScopes = count($workReport->finalScopeBadges()) === 2;
                                    @endphp
                                    @if ($hasBothScopes)
                                        <div class="small text-muted mb-2">Selecione o escopo que será movido para um novo informe.</div>
                                        <div class="d-flex flex-wrap gap-2 mb-3">
                                            @foreach ($availableFinalScopes as $scope)
                                                <label class="fivefx-scope-option">
                                                    <input class="form-check-input" type="checkbox" wire:model.defer="splitScopeSelection" value="{{ $scope }}">
                                                    <span>{{ $scope === 'network' ? 'Rede' : 'Ligação' }}</span>
                                                </label>
                                            @endforeach
                                        </div>
                                        <button type="button" class="btn btn-outline-info w-100 fivefx-btn" wire:click="splitWorkReportByScopes">
                                            <i class="ri-git-branch-line me-1"></i> Separar escopo selecionado
                                        </button>
                                    @else
                                        <div class="fivefx-empty">
                                            <i class="ri-information-line me-1"></i>
                                            A separação só está disponível quando este informe possui Rede e Ligação.
                                        </div>
                                    @endif

                                    <div class="fivefx-subtitle mt-4">Informes da mesma obra</div>
                                    <div class="d-flex flex-wrap gap-2">
                                        @forelse ($relatedWorkReportOptions as $related)
                                            <button type="button" class="btn btn-sm text-start {{ (int) $related['id'] === (int) $workReport->id ? 'btn-primary' : 'btn-outline-light' }}" wire:click="switchRelatedWorkReport({{ $related['id'] }})">
                                                <span class="fw-semibold">#{{ $related['id'] }}</span>
                                                <span class="badge text-bg-info ms-1">{{ $related['label'] }}</span>
                                            </button>
                                        @empty
                                            <span class="small text-muted">Nenhum outro informe encontrado.</span>
                                        @endforelse
                                    </div>
                                </div>

                                <div class="fivefx-section mb-3">
                                    <div class="fivefx-section-title">
                                        <div>
                                            <span class="fivefx-step">B</span>
                                            <span>Ordens</span>
                                        </div>
                                    </div>
                                    <div class="fivefx-subtitle">Disponíveis para vincular</div>
                                    @if (!empty($availableOrders))
                                        <div class="fivefx-list">
                                            @foreach ($availableOrders as $order)
                                                <div class="fivefx-list-row">
                                                    <div>
                                                        <strong>{{ $order['ordem'] ?? '' }}</strong>
                                                        <small>{{ $order['statusSist'] ?? 'Status não informado' }}</small>
                                                    </div>
                                                    <button type="button" class="btn btn-outline-primary btn-sm fivefx-btn" wire:click="addOrder({{ $order['id'] ?? 0 }})" title="Vincular ordem">
                                                        <i class="ri-add-line"></i>
                                                    </button>
                                                </div>
                                            @endforeach
                                        </div>
                                    @else
                                        <div class="fivefx-empty">Nenhuma ordem disponível para esta nota.</div>
                                    @endif

                                    <div class="fivefx-subtitle mt-3">Vinculadas a este informe</div>
                                    @if (!empty($linkedOrders))
                                        <div class="fivefx-list">
                                            @foreach ($linkedOrders as $order)
                                                <div class="fivefx-list-row">
                                                    <div>
                                                        <strong>{{ $order['ordem'] ?? '' }}</strong>
                                                        <small>{{ $order['statusSist'] ?? 'Status não informado' }}</small>
                                                    </div>
                                                    <button type="button" class="btn btn-outline-danger btn-sm fivefx-btn" wire:click="removeOrder({{ $order['id'] ?? 0 }})" title="Desvincular ordem">
                                                        <i class="ri-link-unlink-m"></i>
                                                    </button>
                                                </div>
                                            @endforeach
                                        </div>
                                    @else
                                        <div class="fivefx-empty">Nenhuma ordem vinculada.</div>
                                    @endif
                                </div>

                                <div class="fivefx-section mb-3">
                                    <div class="fivefx-section-title">
                                        <div>
                                            <span class="fivefx-step">C</span>
                                            <span>ADS e solicitações</span>
                                        </div>
                                    </div>
                                    <div class="fivefx-subtitle">Primeira solicitação ADS válida</div>
                                    @if ($firstValidAdsRequest)
                                        <div class="fivefx-ads-request">
                                            <div><strong>#{{ $firstValidAdsRequest['id'] }}</strong> <span class="badge text-bg-info">{{ $firstValidAdsRequest['status'] }}</span></div>
                                            <small>{{ $firstValidAdsRequest['requested_by_name'] ?? 'Solicitante não informado' }}</small>
                                            <small>Entrega: {{ $firstValidAdsRequest['delivered_at'] ?? '---' }} · {{ $firstValidAdsRequest['elapsed'] ?? '---' }}</small>
                                            <a href="{{ $firstValidAdsRequest['url'] }}" target="_blank" rel="noopener noreferrer" class="btn btn-outline-info btn-sm fivefx-btn mt-2"><i class="ri-link me-1"></i>Abrir solicitação</a>
                                        </div>
                                    @else
                                        <div class="fivefx-empty">Nenhuma solicitação ADS válida encontrada.</div>
                                    @endif

                                    <div class="fivefx-subtitle mt-3 d-flex align-items-center justify-content-between">
                                        <span>ADSForm vinculado</span>
                                        @if ($adsFormId)
                                            <span class="badge text-bg-info">ID {{ $adsFormId }}</span>
                                        @else
                                            <span class="badge text-bg-secondary">Não cadastrado</span>
                                        @endif
                                    </div>
                                    @if (!$adsFormEnabled)
                                        <button type="button" class="btn btn-outline-primary btn-sm w-100 fivefx-btn mt-2" wire:click="enableAdsForm">
                                            <i class="ri-add-circle-line me-1"></i>Criar/editar ADSForm
                                        </button>
                                    @else
                                        <div class="small text-muted mb-2">Arquivos vinculados: <strong>{{ $adsFilesCount }}</strong></div>
                                        <div class="row g-2">
                                            <div class="col-sm-7">
                                                <label class="form-label fivefx-k">Responsável ADS</label>
                                                <input type="text" class="form-control fivefx-control" wire:model.defer="adsName">
                                            </div>
                                            <div class="col-sm-5">
                                                <label class="form-label fivefx-k">Valor</label>
                                                <input type="text" class="form-control fivefx-control" wire:model.defer="adsAmount" placeholder="0,00">
                                            </div>
                                            <div class="col-sm-4">
                                                <label class="form-label fivefx-k">Contrato</label>
                                                <input type="text" class="form-control fivefx-control" wire:model.defer="adsContract">
                                            </div>
                                            <div class="col-sm-4">
                                                <label class="form-label fivefx-k">Centro</label>
                                                <input type="text" class="form-control fivefx-control" wire:model.defer="adsCenter">
                                            </div>
                                            <div class="col-sm-4">
                                                <label class="form-label fivefx-k">Depósito</label>
                                                <input type="text" class="form-control fivefx-control" wire:model.defer="adsDeposit">
                                            </div>
                                            <div class="col-12">
                                                <label class="form-label fivefx-k">Observação ADS</label>
                                                <textarea class="form-control fivefx-control fivefx-textarea" rows="3" wire:model.defer="adsObs"></textarea>
                                            </div>
                                            <div class="col-sm-6">
                                                <label class="fivefx-check">
                                                    <input class="form-check-input" type="checkbox" id="adsPartial" wire:model.defer="adsPartial">
                                                    <span>ADS parcial</span>
                                                </label>
                                            </div>
                                            <div class="col-sm-6">
                                                <label class="fivefx-check">
                                                    <input class="form-check-input" type="checkbox" id="adsTacit" wire:model.defer="adsTacit">
                                                    <span>ADS tácita</span>
                                                </label>
                                            </div>
                                            <div class="col-sm-6">
                                                <label class="form-label fivefx-k">Prazo tácito</label>
                                                <input type="datetime-local" class="form-control fivefx-control" wire:model.defer="adsTacitDueAt">
                                            </div>
                                            <div class="col-sm-6">
                                                <label class="form-label fivefx-k">Tácita entregue em</label>
                                                <input type="datetime-local" class="form-control fivefx-control" wire:model.defer="adsTacitDeliveredAt">
                                            </div>
                                        </div>
                                        @if ($adsFormId)
                                            <button type="button" class="btn btn-outline-danger btn-sm fivefx-btn mt-3" wire:click="requestDeleteAdsForm">
                                                <i class="ri-delete-bin-6-line me-1"></i>Excluir ADSForm
                                            </button>
                                        @endif
                                    @endif
                                </div>
                            </div>
                        </div>
                        @else
                            <div class="fivefx-files-panel">
                                <div class="fivefx-files-toolbar mb-3">
                                    <div>
                                        <span class="fivefx-eyebrow">Gestão de arquivos</span>
                                        <h5 class="mb-1">Associar arquivos ao informe</h5>
                                        <div class="small text-muted">Selecione um ou vários arquivos e escolha o informe de destino. A atualização ocorre sem fechar este modal.</div>
                                    </div>
                                    <div class="fivefx-files-count">
                                        <strong>{{ count($selectedFileIds) }}</strong>
                                        <span>selecionado(s)</span>
                                    </div>
                                </div>

                                <div class="fivefx-file-bulk mb-3">
                                    <div class="row g-2 align-items-end">
                                        <div class="col-12 col-lg-8">
                                            <label class="form-label fivefx-k">Informe destino para os selecionados</label>
                                            <select class="form-select fivefx-select" wire:model="fileTargetWorkReportId">
                                                <option value="">Selecione o informe destino...</option>
                                                @foreach ($relatedWorkReportOptions as $related)
                                                    <option value="{{ $related['id'] }}">
                                                        #{{ $related['id'] }} · {{ $related['label'] }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-12 col-lg-4">
                                            <button type="button" class="btn btn-primary w-100 fivefx-btn" wire:click="associateSelectedFiles">
                                                <i class="ri-share-forward-line me-1"></i>Associar selecionados
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                @if (!empty($workReportFiles))
                                    <div class="fivefx-file-list">
                                        @foreach ($workReportFiles as $file)
                                            <div class="fivefx-file-row">
                                                <div class="form-check me-2">
                                                    <input class="form-check-input" type="checkbox"
                                                        value="{{ $file['id'] }}" wire:model="selectedFileIds"
                                                        id="file-{{ $file['id'] }}">
                                                </div>
                                                <div class="fivefx-file-icon">
                                                    <i class="ri-file-{{ in_array($file['type'], ['JPG','JPEG','PNG','WEBP']) ? 'image' : 'text' }}-line"></i>
                                                </div>
                                                <div class="fivefx-file-main">
                                                    <label for="file-{{ $file['id'] }}" class="fivefx-file-name">{{ $file['name'] }}</label>
                                                    <small>
                                                        #{{ $file['id'] }} · {{ $file['type'] }} ·
                                                        {{ number_format($file['size'] / 1024, 1, ',', '.') }} KB
                                                        <span class="ms-2">Atual:</span>
                                                        @forelse ($file['report_ids'] as $reportId)
                                                            <span class="badge text-bg-info ms-1">#{{ $reportId }}</span>
                                                        @empty
                                                            <span class="badge text-bg-secondary ms-1">Sem informe</span>
                                                        @endforelse
                                                    </small>
                                                </div>
                                                <div class="fivefx-file-action">
                                                    <select class="form-select form-select-sm fivefx-select"
                                                        wire:model="fileTargetByFile.{{ $file['id'] }}"
                                                        aria-label="Informe destino do arquivo {{ $file['name'] }}">
                                                        @foreach ($relatedWorkReportOptions as $related)
                                                            <option value="{{ $related['id'] }}">
                                                                #{{ $related['id'] }} · {{ $related['label'] }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                    <button type="button" class="btn btn-outline-info btn-sm fivefx-btn mt-1 w-100"
                                                        wire:click="associateFileToWorkReport({{ $file['id'] }})">
                                                        <i class="ri-refresh-line me-1"></i>Atualizar
                                                    </button>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    <div class="fivefx-empty py-5">
                                        <i class="ri-attachment-2 fs-2 d-block mb-2"></i>
                                        Nenhum arquivo associado aos informes desta obra.
                                    </div>
                                @endif
                            </div>
                        @endif
                    </div>

                    <div class="modal-footer fivefx-footer py-2">
                        <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Fechar</button>
                        <button type="submit" class="btn btn-primary fivefx-btn">
                            <i class="ri-save-3-line me-1"></i>Salvar alteracoes
                        </button>
                    </div>
                </form>
            @endif
        </div>
    </div>

    <style>
        .finish-five .fivefx-section {
            padding: 1rem;
            background: rgba(255, 255, 255, .04);
            border: 1px solid rgba(255, 255, 255, .08);
            border-radius: 14px;
        }

        .finish-five .fivefx-card {
            background: radial-gradient(1200px 600px at 100% -20%, rgba(37, 99, 235, .12), transparent 40%),
                radial-gradient(1200px 600px at -10% 120%, rgba(14, 165, 233, .10), transparent 35%),
                linear-gradient(145deg, #1f2937, #0f172a);
            color: #e5e7eb;
            border: 0;
            border-radius: 14px;
            box-shadow: 0 24px 80px rgba(0, 0, 0, .55), 0 12px 30px rgba(0, 0, 0, .35);
            backdrop-filter: saturate(120%) blur(2px);
            transition: transform .18s ease, box-shadow .18s ease
        }

        .finish-five .fivefx-header {
            background: rgba(17, 24, 39, .9);
            border: 0;
            border-top-left-radius: 14px;
            border-top-right-radius: 14px;
            border-bottom: 1px solid rgba(255, 255, 255, .06)
        }

        .finish-five .fivefx-footer {
            background: rgba(17, 24, 39, .9);
            border: 0;
            border-top: 1px solid rgba(255, 255, 255, .06);
            border-bottom-left-radius: 14px;
            border-bottom-right-radius: 14px
        }

        .finish-five .btn-close {
            filter: invert(1);
            opacity: .9
        }

        .finish-five .fivefx-body {
            padding: 1.1rem 1.25rem
        }

        .finish-five .fivefx-pill {
            display: inline-block;
            background: #0ea5e9;
            color: #fff;
            padding: .2rem .55rem;
            border-radius: 999px;
            font-size: .77rem;
            font-weight: 700
        }

        .finish-five .fivefx-k {
            color: #9ca3af;
            font-size: .82rem;
            font-weight: 700;
            margin-bottom: 2px
        }

        .finish-five .fivefx-control {
            background: rgba(255, 255, 255, .06);
            border: 1px solid rgba(255, 255, 255, .12);
            color: #e5e7eb;
            border-radius: 12px;
            padding: .6rem .85rem;
            line-height: 1.35;
            min-height: 42px;
            transition: border-color .15s ease, box-shadow .15s ease, background .15s ease
        }

        .finish-five .fivefx-control:focus {
            background: rgba(255, 255, 255, .08);
            border-color: rgba(59, 130, 246, .65);
            box-shadow: 0 0 0 .18rem rgba(59, 130, 246, .15), inset 0 0 0 9999px rgba(255, 255, 255, .01);
            color: #f3f4f6
        }

        .finish-five .fivefx-select {
            background-color: #0f172a;
            border: 1px solid rgba(255, 255, 255, .18);
            color: #e5e7eb;
            border-radius: 12px;
            min-height: 42px;
            padding: .55rem 2.2rem .55rem .85rem;
            appearance: none;
            background-image:
                var(--bs-form-select-bg-img),
                linear-gradient(#0f172a, #0f172a);
            background-repeat: no-repeat;
            background-position: right .8rem center, 0 0;
            background-size: 16px 12px, 100% 100%;
        }

        .finish-five .fivefx-select:focus {
            border-color: rgba(59, 130, 246, .65);
            box-shadow: 0 0 0 .18rem rgba(59, 130, 246, .15);
            color: #f3f4f6;
        }

        .finish-five .fivefx-textarea {
            min-height: 120px;
            resize: vertical;
        }

        .finish-five .fivefx-dialog {
            width: min(96vw, 1480px);
            max-width: 1480px;
        }

        .finish-five .fivefx-card {
            max-height: min(94vh, 980px);
            overflow: hidden;
        }

        .finish-five .fivefx-body {
            overflow-y: auto;
            max-height: calc(94vh - 122px);
            scrollbar-gutter: stable;
        }

        .finish-five .fivefx-context {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            padding: .85rem 1rem;
            border: 1px solid rgba(148, 163, 184, .2);
            border-radius: 14px;
            background: rgba(15, 23, 42, .55);
        }

        .finish-five .fivefx-context h5 {
            color: #f8fafc;
            font-weight: 800;
        }

        .finish-five .fivefx-eyebrow,
        .finish-five .fivefx-step {
            color: #38bdf8;
            font-size: .68rem;
            font-weight: 800;
            letter-spacing: .08em;
            text-transform: uppercase;
        }

        .finish-five .fivefx-step {
            display: inline-flex;
            min-width: 1.8rem;
            height: 1.8rem;
            align-items: center;
            justify-content: center;
            margin-right: .45rem;
            border-radius: 8px;
            background: rgba(14, 165, 233, .14);
        }

        .finish-five .fivefx-section-title {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: .75rem;
            margin-bottom: 1rem;
            color: #f8fafc;
            font-weight: 800;
        }

        .finish-five .fivefx-hint {
            color: #94a3b8;
            font-size: .72rem;
            font-weight: 500;
        }

        .finish-five .fivefx-subtitle {
            color: #cbd5e1;
            font-size: .78rem;
            font-weight: 800;
            margin-bottom: .55rem;
        }

        .finish-five .fivefx-check,
        .finish-five .fivefx-scope-option {
            display: flex;
            align-items: center;
            gap: .55rem;
            min-height: 40px;
            padding: .55rem .7rem;
            border: 1px solid rgba(148, 163, 184, .16);
            border-radius: 10px;
            background: rgba(15, 23, 42, .25);
            color: #dbeafe;
            cursor: pointer;
            transition: border-color .15s ease, background .15s ease;
        }

        .finish-five .fivefx-check:hover,
        .finish-five .fivefx-scope-option:hover {
            border-color: rgba(56, 189, 248, .55);
            background: rgba(14, 165, 233, .1);
        }

        .finish-five .fivefx-scope-option {
            min-width: 110px;
        }

        .finish-five .fivefx-scope-current {
            padding: .7rem;
            border-left: 3px solid #38bdf8;
            border-radius: 8px;
            background: rgba(14, 165, 233, .08);
        }

        .finish-five .fivefx-list {
            max-height: 220px;
            overflow-y: auto;
            padding: .25rem;
            border: 1px solid rgba(148, 163, 184, .16);
            border-radius: 10px;
            background: rgba(15, 23, 42, .28);
        }

        .finish-five .fivefx-list-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: .75rem;
            padding: .55rem .6rem;
            border-bottom: 1px solid rgba(148, 163, 184, .12);
        }

        .finish-five .fivefx-list-row:last-child {
            border-bottom: 0;
        }

        .finish-five .fivefx-list-row strong,
        .finish-five .fivefx-list-row small {
            display: block;
        }

        .finish-five .fivefx-list-row small {
            color: #94a3b8;
            margin-top: .15rem;
        }

        .finish-five .fivefx-empty {
            padding: .75rem;
            border: 1px dashed rgba(148, 163, 184, .25);
            border-radius: 10px;
            color: #94a3b8;
            font-size: .82rem;
            text-align: center;
        }

        .finish-five .fivefx-ads-request {
            padding: .75rem;
            border-radius: 10px;
            background: rgba(14, 165, 233, .08);
            border: 1px solid rgba(56, 189, 248, .2);
        }

        .finish-five .fivefx-ads-request small {
            display: block;
            color: #94a3b8;
            margin-top: .25rem;
        }

        .finish-five .fivefx-json {
            font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
            font-size: .78rem;
        }

        .finish-five .fivefx-footer {
            position: sticky;
            bottom: 0;
            z-index: 2;
        }

        @media (max-width: 1199.98px) {
            .finish-five .fivefx-card {
                max-height: 100vh;
                border-radius: 0;
            }

            .finish-five .fivefx-body {
                max-height: calc(100vh - 116px);
            }

            .finish-five .fivefx-dialog {
                width: 100%;
                max-width: none;
                margin: 0;
            }
        }

        @media (max-width: 575.98px) {
            .finish-five .fivefx-body {
                padding: .8rem;
            }

            .finish-five .fivefx-context,
            .finish-five .fivefx-section-title {
                align-items: flex-start;
                flex-direction: column;
            }

            .finish-five .fivefx-hint {
                display: none;
            }
        }

        .finish-five .fivefx-tabs {
            display: flex;
            gap: .5rem;
            padding: .25rem;
            border: 1px solid rgba(148, 163, 184, .18);
            border-radius: 12px;
            background: rgba(15, 23, 42, .45);
        }

        .finish-five .fivefx-tab {
            flex: 1;
            border: 0;
            border-radius: 9px;
            padding: .7rem 1rem;
            background: transparent;
            color: #94a3b8;
            font-weight: 800;
            transition: background .15s ease, color .15s ease;
        }

        .finish-five .fivefx-tab:hover,
        .finish-five .fivefx-tab.is-active {
            background: #2563eb;
            color: #fff;
        }

        .finish-five .fivefx-files-toolbar,
        .finish-five .fivefx-file-bulk {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            padding: 1rem;
            border: 1px solid rgba(148, 163, 184, .18);
            border-radius: 14px;
            background: rgba(15, 23, 42, .45);
        }

        .finish-five .fivefx-files-count {
            min-width: 92px;
            padding: .55rem .7rem;
            border-radius: 10px;
            background: rgba(14, 165, 233, .12);
            color: #bae6fd;
            text-align: center;
        }

        .finish-five .fivefx-files-count strong,
        .finish-five .fivefx-files-count span {
            display: block;
        }

        .finish-five .fivefx-files-count strong {
            font-size: 1.2rem;
        }

        .finish-five .fivefx-files-count span {
            font-size: .7rem;
        }

        .finish-five .fivefx-file-list {
            display: flex;
            flex-direction: column;
            gap: .6rem;
        }

        .finish-five .fivefx-file-row {
            display: flex;
            align-items: center;
            gap: .75rem;
            padding: .75rem;
            border: 1px solid rgba(148, 163, 184, .16);
            border-radius: 12px;
            background: rgba(15, 23, 42, .3);
        }

        .finish-five .fivefx-file-icon {
            display: grid;
            width: 2.25rem;
            height: 2.25rem;
            place-items: center;
            flex: 0 0 auto;
            border-radius: 9px;
            background: rgba(14, 165, 233, .14);
            color: #38bdf8;
            font-size: 1.2rem;
        }

        .finish-five .fivefx-file-main {
            min-width: 0;
            flex: 1;
        }

        .finish-five .fivefx-file-name {
            display: block;
            overflow: hidden;
            color: #f8fafc;
            font-weight: 700;
            text-overflow: ellipsis;
            white-space: nowrap;
            cursor: pointer;
        }

        .finish-five .fivefx-file-main small {
            display: block;
            margin-top: .25rem;
            color: #94a3b8;
        }

        .finish-five .fivefx-file-action {
            width: min(31%, 290px);
            flex: 0 0 290px;
        }

        @media (max-width: 767.98px) {
            .finish-five .fivefx-file-row {
                align-items: flex-start;
                flex-wrap: wrap;
            }

            .finish-five .fivefx-file-action {
                width: 100%;
                flex-basis: 100%;
                padding-left: 3rem;
            }

            .finish-five .fivefx-files-toolbar {
                align-items: flex-start;
                flex-direction: column;
            }
        }
    </style>

    <script>
        (function() {
            const modalEl = document.getElementById('adminWorkReportModal');
            if (!modalEl) return;

            modalEl.addEventListener('hidden.bs.modal', () => {
                Livewire.emitTo('admin.control.work-report-edit', 'resetForm');
            });
        })();
    </script>
</div>
