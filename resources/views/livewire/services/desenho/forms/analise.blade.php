@php
    use App\Helpers\SelectOptions;
@endphp

<div class="oexterno-page min-vh-100 d-flex flex-column">
    <x-show-loading />

    <style>
        .oexterno-page {
            --oe-bg: #f6f7fb;
            --oe-surface: #ffffff;
            --oe-ink: #1f2933;
            --oe-muted: #6b7280;
            --oe-border: #e5e7eb;
            background: radial-gradient(circle at 10% 0%, #eef2ff, transparent 40%), radial-gradient(circle at 90% 10%, #ecfeff, transparent 35%), var(--oe-bg);
        }

        .form-header {
            background: linear-gradient(120deg, #0f172a, #0f766e 70%);
            color: #f8fafc;
            border-radius: 1rem;
            padding: 1.25rem 1.5rem;
            margin-bottom: 1rem;
        }

        .card-soft {
            background: var(--oe-surface);
            border: 1px solid var(--oe-border);
            border-radius: .85rem;
            box-shadow: 0 12px 24px rgba(15, 23, 42, .06);
        }

        .card-soft .card-body {
            padding: 1.25rem 1.25rem;
        }

        .card-soft .row.no-edge {
            margin-left: 0;
            margin-right: 0;
        }

        .back-to-top {
            display: none !important;
        }
    </style>

    @if ($view_form)
        <main class="container my-4 flex-grow-1">
            <div class="form-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <h4 class="mb-0">Finalização de Desenho</h4>
                    <small class="opacity-75">Fluxo integrado de Análise de Projeto</small>
                </div>
                <div class="text-end">
                    <div><strong>Nota/OV:</strong> {{ $note->note }}</div>
                    <small>{{ $note->client }} - {{ $note->lexp }}</small>
                </div>
            </div>

            @if ($viewOnlyProjectReview && $production && $production->status === \App\Models\Production::STATUS_REJECTED_PROJECT_REVIEW)
                <div class="row g-3">
                    <div class="col-lg-5">
                        <div class="card-soft mb-3">
                            <div class="card-body small">
                                <h6 class="mb-2">Informações da Nota</h6>
                                <div><strong>Nota:</strong> {{ $note->note ?? '---' }}</div>
                                <div><strong>Desenhista:</strong> {{ auth()->user()->name ?? '---' }}</div>
                                <div><strong>Empresa:</strong> {{ $production->Company->name ?? '---' }}</div>
                                <div><strong>Serviço:</strong> {{ $production->Service->service ?? '---' }}</div>
                            </div>
                        </div>

                        <div class="card-soft mb-3">
                            <div class="card-body small">
                                <h6 class="mb-2">Ordens e Valores informados</h6>
                                <div class="table-responsive">
                                    <table class="table table-sm mb-0">
                                        <thead><tr><th>Ordem</th><th>Total</th><th>Empresa</th><th>Cliente</th></tr></thead>
                                        <tbody>
                                            @forelse ($reviewOrders as $row)
                                                <tr>
                                                    <td>{{ $row['order_number'] ?? '---' }}</td>
                                                    <td>{{ $row['total_cost'] ?? '---' }}</td>
                                                    <td>{{ $row['company_cost'] ?? '---' }}</td>
                                                    <td>{{ $row['client_cost'] ?? '---' }}</td>
                                                </tr>
                                            @empty
                                                <tr><td colspan="4" class="text-center text-muted">Sem ordens</td></tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <div class="card-soft mb-3">
                            <div class="card-body small">
                                <h6 class="mb-2">Arquivos do Projeto</h6>
                                @php
                                    $noteFiles = ($production->Note->Files ?? collect())
                                        ->sortBy(fn($f) => [($f->service->service ?? 'OUTROS'), ($f->file_name ?? '')])
                                        ->values();
                                    $fileServices = $noteFiles
                                        ->map(fn($f) => [
                                            'id' => (string) ($f->service_id ?? 'others'),
                                            'name' => $f->service->service ?? 'OUTROS',
                                        ])
                                        ->unique('id')
                                        ->values();
                                @endphp
                                <div x-data="{ serviceFilter: 'all' }">
                                    <div class="row g-2 mb-2">
                                        <div class="col-md-6">
                                            <label class="form-label">Filtrar por serviço</label>
                                            <select class="form-select form-select-sm" x-model="serviceFilter">
                                                <option value="all">Todos</option>
                                                @foreach($fileServices as $svc)
                                                    <option value="{{ $svc['id'] }}">{{ $svc['name'] }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <div class="table-responsive border rounded" style="max-height: 240px;">
                                        <table class="table table-sm mb-0 align-middle">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>Serviço</th>
                                                    <th>Arquivo</th>
                                                    <th>Data</th>
                                                    <th style="width: 90px;"></th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @forelse($noteFiles as $file)
                                                    <tr x-show="serviceFilter === 'all' || serviceFilter === '{{ $file->service_id ?? 'others' }}'">
                                                        <td>{{ $file->service->service ?? 'OUTROS' }}</td>
                                                        <td class="text-break">{{ $file->file_name . ($file->ext ? '.' . $file->ext : '') }}</td>
                                                        <td>{{ $file->created_at ? date('d/m/Y H:i', strtotime($file->created_at)) : '---' }}</td>
                                                        <td>
                                                            <button class="btn btn-sm btn-outline-primary w-100" wire:click="downloadFile({{ $file->id }})">Baixar</button>
                                                        </td>
                                                    </tr>
                                                @empty
                                                    <tr><td colspan="4" class="text-center text-muted">Sem anexos.</td></tr>
                                                @endforelse
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card-soft">
                            <div class="card-body">
                                <h6 class="mb-2">Chat de comentários com o analista</h6>
                                <textarea class="form-control mb-2" rows="2" wire:model.defer="newContestationMessage" placeholder="Escreva sua mensagem"></textarea>
                                <button type="button" class="btn btn-outline-primary btn-sm" wire:click="addContestationMessage">
                                    Enviar mensagem
                                </button>

                                @if (count($reviewMessages))
                                    <div class="mt-3">
                                        @foreach ($reviewMessages as $msg)
                                            <div class="border rounded p-2 mb-1 {{ $msg->user_id === auth()->id() ? 'bg-light' : '' }}">
                                                <div class="small text-muted">
                                                    {{ optional($msg->User)->name }} - {{ date('d/m/Y H:i', strtotime($msg->created_at)) }}
                                                </div>
                                                <div>{{ $msg->message }}</div>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-7">
                        <div class="card-soft h-100">
                            <div class="card-body">
                                <h6 class="mb-2 text-danger">Apontamentos pendentes da análise</h6>
                                <small class="text-muted d-block mb-3">
                                    Exibição somente dos itens pendentes (sem conformidade), com observações do analista.
                                </small>

                                @if (count($rejectedFindings))
                                    @php
                                        $findingsTree = collect($rejectedFindings)->groupBy(function ($finding) {
                                            return data_get($finding, 'category_name') ?: 'Sem categoria';
                                        });
                                    @endphp

                                    @foreach ($findingsTree as $catName => $catRows)
                                        @php
                                            $catId = 'readonly_cat_' . md5($catName);
                                        @endphp
                                        <div class="card mb-2">
                                            <div class="card-header d-flex justify-content-between align-items-center">
                                                <button class="btn btn-link text-decoration-none p-0 fw-semibold text-danger"
                                                    data-bs-toggle="collapse" data-bs-target="#{{ $catId }}">
                                                    {{ $catName }}
                                                </button>
                                                <span class="badge bg-light text-dark">{{ $catRows->count() }} item(ns)</span>
                                            </div>
                                            <div class="collapse show" id="{{ $catId }}">
                                                <div class="card-body py-2">
                                                    @foreach ($catRows->groupBy(fn($f) => data_get($f, 'subcategory_name') ?: 'Sem subcategoria') as $subName => $subRows)
                                                        @php
                                                            $subId = 'readonly_sub_' . md5($catName . '_' . $subName);
                                                        @endphp
                                                        <div class="border rounded mb-2">
                                                            <div class="px-2 py-1 border-bottom d-flex justify-content-between align-items-center">
                                                                <button class="btn btn-link text-decoration-none p-0 fw-semibold"
                                                                    data-bs-toggle="collapse" data-bs-target="#{{ $subId }}">
                                                                    {{ $subName }}
                                                                </button>
                                                                <span class="small text-muted">{{ $subRows->count() }} apontamento(s)</span>
                                                            </div>
                                                            <div class="collapse show" id="{{ $subId }}">
                                                                <div class="table-responsive">
                                                                    <table class="table table-sm mb-0">
                                                                        <thead class="table-light">
                                                                            <tr>
                                                                                <th>Ação</th>
                                                                                <th>Qtd.</th>
                                                                                <th>Origem</th>
                                                                                <th>Observação</th>
                                                                            </tr>
                                                                        </thead>
                                                                        <tbody>
                                                                            @foreach ($subRows as $finding)
                                                                                <tr>
                                                                                    <td>
                                                                                        @if (data_get($finding, 'item_id'))
                                                                                            {{ data_get($finding, 'action_type') ?? 'FALTA' }} {{ data_get($finding, 'item_name') }}
                                                                                        @else
                                                                                            ---
                                                                                        @endif
                                                                                    </td>
                                                                                    <td>{{ data_get($finding, 'quantity') ?? '---' }}</td>
                                                                                    <td>{{ data_get($finding, 'origin') ?? '---' }}</td>
                                                                                    <td>{{ data_get($finding, 'note') ?: '---' }}</td>
                                                                                </tr>
                                                                            @endforeach
                                                                        </tbody>
                                                                    </table>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                @else
                                    <div class="alert alert-warning mb-0">Sem apontamentos pendentes para exibição.</div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @else
            <section class="mb-4">
                <h2 class="h5 mb-3">1. Informações da Nota</h2>
                <div class="row g-3 no-edge">
                    <div class="col-md-6">
                        <div class="card-soft h-100">
                            <div class="card-body">
                                <dl class="row mb-0">
                                    <dt class="col-5">Nota/OV:</dt>
                                    <dd class="col-7">{{ $note->note }}</dd>
                                    <dt class="col-5">Cliente:</dt>
                                    <dd class="col-7">{{ $note->client }}</dd>
                                    <dt class="col-5">Município:</dt>
                                    <dd class="col-7">{{ $note->lexp }}</dd>
                                    <dt class="col-5 text-danger">MMGD:</dt>
                                    <dd class="col-7 text-danger">{{ $note->mmgd ? 'SIM' : 'NÃO' }}</dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card-soft h-100">
                            <div class="card-body">
                                <dl class="row mb-0">
                                    <dt class="col-5">Tipo:</dt>
                                    <dd class="col-7">{{ $note->rubrica }}</dd>
                                    <dt class="col-5">Data:</dt>
                                    <dd class="col-7">{{ date('d/m/Y', strtotime($note->dt_status)) }}</dd>
                                    <dt class="col-5">Pedido:</dt>
                                    <dd class="col-7">{{ $note->numPedido }}</dd>
                                    <dt class="col-5">Rede:</dt>
                                    <dd class="col-7">{{ $note->group2 }}</dd>
                                    <dt class="col-5">Custo:</dt>
                                    <dd class="col-7">{{ $note->group5 }}</dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            @if ($this->requiresProjectReview)
                <section id="project-review-data" class="mb-4">
                    <h2 class="h5 mb-3">2. Dados para Análise de Projeto</h2>
                    <div class="card-soft">
                        <div class="card-body">
                            <h6 class="mb-3">Ordens e Valores</h6>
                            <div class="row g-2 mb-2 align-items-end no-edge" data-order-calc-scope="new">
                                <div class="col-md-3">
                                    <label class="form-label">Número da ordem</label>
                                    <input type="text" class="form-control @error('order_input_number') is-invalid @enderror"
                                        wire:model.defer="order_input_number">
                                    @error('order_input_number')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Custo total</label>
                                    <input type="text" class="form-control @error('order_input_total') is-invalid @enderror"
                                        wire:model.defer="order_input_total" inputmode="decimal" data-br-money data-order-field="total">
                                    @error('order_input_total')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Custo empresa</label>
                                    <input type="text" class="form-control @error('order_input_company') is-invalid @enderror"
                                        wire:model.defer="order_input_company" inputmode="decimal" data-br-money data-order-field="company">
                                    @error('order_input_company')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Custo cliente</label>
                                    <input type="text" class="form-control @error('order_input_client') is-invalid @enderror"
                                        wire:model.defer="order_input_client" inputmode="decimal" data-br-money data-order-field="client">
                                    @error('order_input_client')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-3">
                                    <button type="button" class="btn btn-outline-primary w-100" wire:click="addOrderToList">
                                        <i class="ri-add-line"></i> Adicionar
                                    </button>
                                </div>
                            </div>

                            <div class="table-responsive mt-3">
                                <table class="table table-sm table-hover mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Número da ordem</th>
                                            <th>Custo total</th>
                                            <th>Custo empresa</th>
                                            <th>Custo cliente</th>
                                            <th style="width: 120px;"></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($reviewOrders as $idx => $row)
                                            <tr data-order-calc-scope="row">
                                                <td>
                                                    {{ $row['order_number'] ?? '---' }}
                                                    @if (!empty($row['locked']) && $production->status === \App\Models\Production::STATUS_REJECTED_PROJECT_REVIEW)
                                                        <span class="badge text-bg-light ms-1">existente</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    <input type="text" class="form-control form-control-sm @error('reviewOrders.' . $idx . '.total_cost') is-invalid @enderror"
                                                        wire:model.defer="reviewOrders.{{ $idx }}.total_cost" data-br-money inputmode="decimal" data-order-field="total">
                                                    @error('reviewOrders.' . $idx . '.total_cost')
                                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                                    @enderror
                                                </td>
                                                <td>
                                                    <input type="text" class="form-control form-control-sm @error('reviewOrders.' . $idx . '.company_cost') is-invalid @enderror"
                                                        wire:model.defer="reviewOrders.{{ $idx }}.company_cost" data-br-money inputmode="decimal" data-order-field="company">
                                                    @error('reviewOrders.' . $idx . '.company_cost')
                                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                                    @enderror
                                                </td>
                                                <td>
                                                    <input type="text" class="form-control form-control-sm @error('reviewOrders.' . $idx . '.client_cost') is-invalid @enderror"
                                                        wire:model.defer="reviewOrders.{{ $idx }}.client_cost" data-br-money inputmode="decimal" data-order-field="client">
                                                    @error('reviewOrders.' . $idx . '.client_cost')
                                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                                    @enderror
                                                </td>
                                                <td>
                                                    @if (!$production || $production->status !== \App\Models\Production::STATUS_REJECTED_PROJECT_REVIEW || empty($row['locked']))
                                                        <button type="button" class="btn btn-sm btn-outline-danger w-100"
                                                            wire:click="removeReviewOrder({{ $idx }})">
                                                            Remover
                                                        </button>
                                                    @else
                                                        <span class="text-muted small">Sem remoção</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="5" class="text-center text-muted py-3">Nenhuma ordem adicionada.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>

                            <div class="row g-3 mt-3 no-edge">
                                <div class="col-md-4">
                                    <label class="form-label fw-semibold">Proporcionalidade aplicada</label>
                                    <select class="form-select @error('proportionality_ok') is-invalid @enderror" wire:model.defer="proportionality_ok">
                                        <option value="">Selecione...</option>
                                        <option value="1">Sim</option>
                                        <option value="0">Não</option>
                                    </select>
                                    @error('proportionality_ok')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-semibold">Valor aplicado (%)</label>
                                    <input type="text"
                                        class="form-control @error('proportionality_value') is-invalid @enderror"
                                        wire:model.defer="proportionality_value" inputmode="decimal" data-br-money data-proportionality-field>
                                    @error('proportionality_value')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <div id="proportionality-estimate-hint" class="form-text text-muted"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
            @endif

            <section id="resultado-desenho" class="mb-4">
                <h2 class="h5 mb-3">{{ $this->requiresProjectReview ? '3' : '2' }}. Resultado do Desenho</h2>
                <div class="card-soft">
                    <div class="card-body">
                        <form>
                            <div class="row g-3 align-items-end no-edge">
                                <div class="col-lg-4">
                                    <label class="form-label fw-semibold">Finalidade</label>
                                    <select class="form-select @error('preresult') is-invalid @enderror" wire:model="preresult">
                                        @if ($production->d5)
                                            <option value="RESOLUCAO INTERNA">RESOLUÇÃO INTERNA (RI)</option>
                                        @else
                                            <option value="">Selecione...</option>
                                            <option value="ANALISE">ANÁLISE</option>
                                            <option value="NORMAL">NORMAL</option>
                                            <option value="REVALIDACAO">REVALIDAÇÃO</option>
                                            <option value="CUSTO MODULAR">CUSTO MODULAR</option>
                                            <option value="PROPOSTA MELHORAMENTO">PROPOSTA MELHORAMENTO</option>
                                        @endif
                                    </select>
                                    @error('preresult')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-3">
                                    <label class="form-label fw-semibold">Postes</label>
                                    <input type="number" min="0" max="300" class="form-control @error('postes') is-invalid @enderror"
                                        wire:model.defer="postes" @disabled(($preresult !== 'NORMAL' && $preresult !== 'REVALIDACAO') || in_array($conclusion, ['ARQUIVADO', 'RETORNADO LEVANTAMENTO']))>
                                    @error('postes')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-5">
                                    <label class="form-label fw-semibold">Conclusão</label>
                                    <select class="form-select @error('conclusion') is-invalid @enderror" wire:model="conclusion">
                                        <option value="">Selecione...</option>
                                        @if ($production->d5)
                                            @foreach (SelectOptions::getReclaimsOptions() as $opt)
                                                <option value="{{ $opt->value }}">{{ $opt->info }}</option>
                                            @endforeach
                                        @else
                                            @foreach (SelectOptions::getDrawConclusions() as $opt)
                                                <option value="{{ $opt->value }}">{{ $opt->reason }}</option>
                                            @endforeach
                                        @endif
                                    </select>
                                    @error('conclusion')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="row g-3 mt-2 no-edge">
                                @if (($preresult === 'NORMAL' || $preresult === 'REVALIDACAO') && !$production->d5)
                                    <div class="col-auto form-check ms-2">
                                        <input class="form-check-input @error('eo') is-invalid @enderror" type="checkbox" wire:model.defer="eo" id="eoCheck">
                                        <label class="form-check-label" for="eoCheck">EO</label>
                                    </div>
                                    <div class="col-auto form-check">
                                        <input class="form-check-input @error('iproject') is-invalid @enderror" type="checkbox" wire:model.defer="iproject" id="ipCheck">
                                        <label class="form-check-label" for="ipCheck">iProject</label>
                                    </div>
                                    <div class="col-auto form-check">
                                        <input class="form-check-input @error('cad') is-invalid @enderror" type="checkbox" wire:model.defer="cad" id="cadCheck">
                                        <label class="form-check-label" for="cadCheck">AutoCad</label>
                                    </div>
                                    <div class="col-auto form-check">
                                        <input class="form-check-input @error('cadastro') is-invalid @enderror" type="checkbox" wire:model.defer="cadastro" id="cadCadastroCheck">
                                        <label class="form-check-label" for="cadCadastroCheck">Cadastro</label>
                                    </div>
                                @endif

                                @if ($cadastro)
                                    <div class="col-md-3">
                                        <label class="form-label fw-semibold">Postes Cadastro</label>
                                        <input type="number" min="0" max="300" class="form-control @error('postes_c') is-invalid @enderror" wire:model.defer="postes_c">
                                        @error('postes_c')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                @endif
                            </div>
                        </form>
                    </div>
                </div>
            </section>

            @if ($production->status === 31)
                <section id="project-review-rejected" class="mb-4">
                    <h2 class="h5 mb-3 text-danger">{{ $this->requiresProjectReview ? '4' : '3' }}. Apontamentos da Reprovação</h2>
                    <div class="card border-danger shadow-sm">
                        <div class="card-body">
                            @php
                                $findingsTree = collect($rejectedFindings)->groupBy(function ($finding) {
                                    return data_get($finding, 'category_name') ?: 'Sem categoria';
                                });
                            @endphp

                            @forelse ($findingsTree as $catName => $catRows)
                                    @php
                                        $catId = 'cat_' . md5($catName);
                                    @endphp
                                    <div class="card mb-2">
                                        <div class="card-header d-flex justify-content-between align-items-center">
                                            <button class="btn btn-link text-decoration-none p-0 fw-semibold text-danger"
                                                data-bs-toggle="collapse" data-bs-target="#{{ $catId }}">
                                                {{ $catName }}
                                            </button>
                                            <span class="badge bg-light text-dark">{{ $catRows->count() }} item(ns)</span>
                                        </div>
                                        <div class="collapse show" id="{{ $catId }}">
                                            <div class="card-body py-2">
                                                @foreach ($catRows->groupBy(fn($f) => data_get($f, 'subcategory_name') ?: 'Sem subcategoria') as $subName => $subRows)
                                                    @php
                                                        $subId = 'sub_' . md5($catName . '_' . $subName);
                                                    @endphp
                                                    <div class="border rounded mb-2">
                                                        <div class="px-2 py-1 border-bottom d-flex justify-content-between align-items-center">
                                                            <button class="btn btn-link text-decoration-none p-0 fw-semibold"
                                                                data-bs-toggle="collapse" data-bs-target="#{{ $subId }}">
                                                                {{ $subName }}
                                                            </button>
                                                            <span class="small text-muted">{{ $subRows->count() }} apontamento(s)</span>
                                                        </div>
                                                        <div class="collapse show" id="{{ $subId }}">
                                                            <div class="table-responsive">
                                                                <table class="table table-sm mb-0">
                                                                    <thead class="table-light">
                                                                        <tr>
                                                                            <th>Item</th>
                                                                            <th>Ação</th>
                                                                            <th>Qtd.</th>
                                                                            <th>Origem</th>
                                                                            <th>Observação</th>
                                                                        </tr>
                                                                    </thead>
                                                                    <tbody>
                                                                        @foreach ($subRows as $finding)
                                                                                                    <tr>
                                                                                                        <td>{{ data_get($finding, 'item_name') ?: 'Estrutura sem item' }}</td>
                                                                                                        <td>
                                                                                                            @if (data_get($finding, 'item_id'))
                                                                                                                {{ data_get($finding, 'action_type') ?? 'FALTA' }} {{ data_get($finding, 'item_name') }}
                                                                                                            @else
                                                                                                                ---
                                                                                                            @endif
                                                                                                        </td>
                                                                                                        <td>{{ data_get($finding, 'quantity') ?? '---' }}</td>
                                                                                                        <td>{{ data_get($finding, 'origin') ?? '---' }}</td>
                                                                                                        <td>{{ data_get($finding, 'note') ?: '---' }}</td>
                                                                                                    </tr>
                                                                                                @endforeach
                                                                    </tbody>
                                                                </table>
                                                            </div>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>
                            @empty
                                <div class="alert alert-warning mb-0">
                                    Não há pendências tratáveis para Projeto/Ambos nesta reprovação.
                                </div>
                            @endforelse

                            <hr>
                            <h6>Chat de comentários com o analista</h6>
                            <textarea class="form-control mb-2" rows="2" wire:model.defer="newContestationMessage" placeholder="Escreva sua mensagem"></textarea>
                            <button type="button" class="btn btn-outline-primary btn-sm" wire:click="addContestationMessage">
                                Enviar mensagem
                            </button>

                            @if (count($reviewMessages))
                                <div class="mt-3">
                                    @foreach ($reviewMessages as $msg)
                                        <div class="border rounded p-2 mb-1 {{ $msg->user_id === auth()->id() ? 'bg-light' : '' }}">
                                            <div class="small text-muted">
                                                {{ optional($msg->User)->name }} - {{ date('d/m/Y H:i', strtotime($msg->created_at)) }}
                                            </div>
                                            <div>{{ $msg->message }}</div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>
                </section>
            @endif

            <section id="arquivos-info" class="mb-5">
                <h2 class="h5 mb-3">{{ $this->requiresProjectReview ? '5' : '4' }}. Arquivos & Informações</h2>
                <div class="card-soft">
                    <div class="card-body">
                        @livewire('files.manager.create-prod-files', ['production' => $production, 'needFiles' => $needFiles], key('production_' . $production->id))

                        @if ($nota_divergente)
                            <div class="alert alert-danger mt-3">
                                O arquivo parece divergente da nota/OV trabalhada.
                            </div>
                        @endif

                        <div class="mt-4">
                            <label class="form-label fw-semibold">Informações Adicionais</label>
                            <textarea class="form-control" rows="6" wire:model.defer="info"></textarea>
                        </div>
                    </div>
                </div>
            </section>
            @endif
        </main>

        @if (!$viewOnlyProjectReview)
            <footer id="encerramento-actions" class="bg-white py-3 border-top">
                <div class="container d-flex justify-content-end gap-2">
                    <button class="btn btn-warning" wire:click.prevent="to_pause">Pausar</button>
                    <button class="btn btn-primary" wire:click.prevent="save_info">Salvar</button>
                    <button class="btn btn-success" wire:click.prevent="to_finish({{ $analise->production_id }})">{{ $this->requiresProjectReview ? 'Enviar para análise' : 'Encerrar' }}</button>
                </div>
            </footer>
        @endif
    @else
        <div class="d-flex justify-content-center align-items-center vh-100">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Carregando...</span>
            </div>
        </div>
    @endif
</div>

<script>
    (function() {
        function applyBrMoneyMaskToInput(input) {
            if (!input || input.dataset.brBound === '1') return;

            const format = function(value) {
                let digits = (value || '').replace(/\D/g, '');
                if (!digits.length) return '';
                const intVal = parseInt(digits, 10);
                if (Number.isNaN(intVal)) return '';
                return (intVal / 100).toLocaleString('pt-BR', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                });
            };

            input.addEventListener('input', function(e) {
                e.target.value = format(e.target.value);
            });

            if (input.value) {
                input.value = format(input.value);
            }

            input.dataset.brBound = '1';
        }

        function bindBrMoneyMasks() {
            document.querySelectorAll('[data-br-money]').forEach(applyBrMoneyMaskToInput);
        }

        function parseBrMoney(value) {
            if (value === null || value === undefined) return null;
            const raw = String(value).trim();
            if (!raw.length) return null;
            const normalized = raw.replace(/\./g, '').replace(',', '.').replace(/\s/g, '');
            const n = Number(normalized);
            return Number.isFinite(n) ? n : null;
        }

        function formatBrMoney(value) {
            if (value === null || value === undefined || Number.isNaN(value)) return '';
            return Number(value).toLocaleString('pt-BR', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        }

        function applyAutoFillCosts(scopeEl) {
            if (!scopeEl || scopeEl.dataset.orderCalcLock === '1') return;

            const totalInput = scopeEl.querySelector('[data-order-field="total"]');
            const companyInput = scopeEl.querySelector('[data-order-field="company"]');
            const clientInput = scopeEl.querySelector('[data-order-field="client"]');
            if (!totalInput || !companyInput || !clientInput) return;

            let total = parseBrMoney(totalInput.value);
            let company = parseBrMoney(companyInput.value);
            let client = parseBrMoney(clientInput.value);

            let changed = false;

            if (company !== null && client !== null) {
                const computedTotal = +(company + client).toFixed(2);
                if (total === null || Math.abs(total - computedTotal) > 0.009) {
                    total = computedTotal;
                    totalInput.value = formatBrMoney(total);
                    changed = true;
                }
            } else if (total !== null && company !== null && client === null) {
                const computedClient = +(total - company).toFixed(2);
                if (computedClient >= 0) {
                    clientInput.value = formatBrMoney(computedClient);
                    changed = true;
                }
            } else if (total !== null && client !== null && company === null) {
                const computedCompany = +(total - client).toFixed(2);
                if (computedCompany >= 0) {
                    companyInput.value = formatBrMoney(computedCompany);
                    changed = true;
                }
            }

            if (changed) {
                scopeEl.dataset.orderCalcLock = '1';
                [totalInput, companyInput, clientInput].forEach((el) => {
                    el.dispatchEvent(new Event('input', { bubbles: true }));
                    el.dispatchEvent(new Event('change', { bubbles: true }));
                });
                scopeEl.dataset.orderCalcLock = '0';
            }
        }

        function bindOrderAutoFill() {
            document.querySelectorAll('[data-order-calc-scope]').forEach(function(scopeEl) {
                if (scopeEl.dataset.orderCalcBound === '1') return;
                scopeEl.dataset.orderCalcBound = '1';

                const handler = function(e) {
                    if (!e.target || !e.target.matches('[data-order-field]')) return;
                    applyAutoFillCosts(scopeEl);
                };

                scopeEl.addEventListener('input', handler);
                scopeEl.addEventListener('change', handler);
                scopeEl.addEventListener('blur', handler, true);
            });
        }

        function updateProportionalityEstimate() {
            const rows = document.querySelectorAll('tr[data-order-calc-scope="row"]');
            const hint = document.getElementById('proportionality-estimate-hint');
            if (!hint) return;

            let sumCompany = 0;
            let sumClient = 0;

            rows.forEach(function(row) {
                const company = parseBrMoney(row.querySelector('[data-order-field="company"]')?.value);
                const client = parseBrMoney(row.querySelector('[data-order-field="client"]')?.value);
                if (company !== null && client !== null) {
                    sumCompany += company;
                    sumClient += client;
                }
            });

            const base = sumCompany + sumClient;
            if (base <= 0) {
                hint.textContent = '';
                return;
            }

            const pctCompany = Math.max(0, Math.min(100, +(sumCompany / base * 100).toFixed(2)));
            const pctClient = +(100 - pctCompany).toFixed(2);

            hint.textContent = `Previsão automática: ${formatBrMoney(pctCompany)}% empresa | ${formatBrMoney(pctClient)}% cliente`;
        }

        function bindProportionalityEstimate() {
            if (document.body.dataset.proportionalityGlobalBound !== '1') {
                document.body.dataset.proportionalityGlobalBound = '1';
                document.addEventListener('input', function(e) {
                    if (!e.target) return;
                    if (e.target.matches('[data-order-field="total"], [data-order-field="company"], [data-order-field="client"]')) {
                        updateProportionalityEstimate();
                    }
                });
                document.addEventListener('change', function(e) {
                    if (!e.target) return;
                    if (e.target.matches('[data-order-field="total"], [data-order-field="company"], [data-order-field="client"]')) {
                        updateProportionalityEstimate();
                    }
                });
            }

            updateProportionalityEstimate();
        }

        document.addEventListener('livewire:load', function() {
            bindBrMoneyMasks();
            bindOrderAutoFill();
            bindProportionalityEstimate();
            Livewire.hook('message.processed', function() {
                bindBrMoneyMasks();
                bindOrderAutoFill();
                bindProportionalityEstimate();
            });
        });

        window.addEventListener('projectReviewGoToFinish', function() {
            const target = document.getElementById('encerramento-actions');
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'center'
                });
            }
        });
    })();
</script>
