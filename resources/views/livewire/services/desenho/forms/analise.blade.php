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

        .order-guide-card {
            border: 1px solid #bfdbfe;
            background: linear-gradient(135deg, #eff6ff, #f0fdfa);
            border-radius: .85rem;
            padding: .95rem 1rem;
        }

        .order-guide-card h6 {
            color: #1e3a8a;
            margin-bottom: .35rem;
        }

        .order-guide-card p {
            margin-bottom: .4rem;
            color: #1f2937;
        }

        .order-guide-card ul {
            margin: 0;
            padding-left: 1.15rem;
        }

        .order-guide-card li {
            margin-bottom: .2rem;
            color: #374151;
        }

        .chat-stream {
            max-height: 240px;
            overflow: auto;
            border: 1px solid var(--oe-border);
            border-radius: .75rem;
            padding: .5rem;
            background: #f8fafc;
        }

        .chat-bubble {
            max-width: 90%;
            border-radius: .75rem;
            padding: .5rem .65rem;
            border: 1px solid var(--oe-border);
            background: #fff;
        }

        .chat-bubble.mine {
            background: #ecfeff;
            border-color: #99f6e4;
        }
    </style>

    @if ($view_form)
        <main class="container-fluid px-3 px-lg-4 my-4 flex-grow-1">
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

            @if (
                $viewOnlyProjectReview &&
                    $production &&
                    in_array((int) $production->status, [
                        \App\Models\Production::STATUS_IN_PROJECT_REVIEW,
                        \App\Models\Production::STATUS_REJECTED_PROJECT_REVIEW,
                        \App\Models\Production::STATUS_RELEASED_TO_FINISH,
                    ], true)
            )
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
                                <div class="chat-stream mb-2">
                                    @forelse ($reviewMessages as $msg)
                                        @php
                                            $msgUserId = data_get($msg, 'user_id');
                                            $mine = (string) $msgUserId === (string) auth()->id();
                                            $msgAuthor = data_get($msg, 'User.name') ?? data_get($msg, 'user.name');
                                            $msgCreatedAt = data_get($msg, 'created_at');
                                        @endphp
                                        <div class="d-flex mb-2 {{ $mine ? 'justify-content-end' : 'justify-content-start' }}">
                                            <div class="chat-bubble {{ $mine ? 'mine' : '' }}">
                                                <div class="small text-muted">
                                                    {{ $msgAuthor }} -
                                                    {{ $msgCreatedAt ? date('d/m/Y H:i', strtotime((string) $msgCreatedAt)) : '---' }}
                                                </div>
                                                <div>{{ data_get($msg, 'message') }}</div>
                                            </div>
                                        </div>
                                    @empty
                                        <div class="small text-muted">Sem mensagens ainda nesta rodada.</div>
                                    @endforelse
                                </div>
                                <textarea class="form-control mt-2" rows="2" wire:model.defer="newContestationMessage" placeholder="Escreva uma mensagem"></textarea>
                                <button type="button" class="btn btn-sm btn-outline-primary mt-2" wire:click="addContestationMessage">Enviar mensagem</button>
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

                                @php
                                    $filteredFindings = collect($this->filteredRejectedFindings ?? [])->values();
                                @endphp
                                @if ($filteredFindings->count() > 0)
                                    <div class="row g-2 mb-3">
                                        <div class="col-md-5">
                                            <label class="form-label mb-1">Filtrar por ref:</label>
                                            <select class="form-select form-select-sm" wire:model="selectedReviewPointFilter">
                                                <option value="">Todas as refs</option>
                                                @foreach ($this->availableReviewPoints as $point)
                                                    <option value="{{ $point }}">{{ $point }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    @php
                                        $pointsTree = $filteredFindings->groupBy(fn($finding) => data_get($finding, 'point_label') ?: 'SEM REFERENCIA');
                                    @endphp

                                    @foreach ($pointsTree as $pointLabel => $pointRows)
                                        @php
                                            $pointId = 'readonly_point_' . md5($pointLabel);
                                            $findingsTree = $pointRows->groupBy(function ($finding) {
                                                return data_get($finding, 'category_name') ?: 'Sem categoria';
                                            });
                                        @endphp
                                        <div class="card mb-2 border-primary-subtle">
                                            <div class="card-header d-flex justify-content-between align-items-center bg-primary-subtle">
                                                <button class="btn btn-link text-decoration-none p-0 fw-semibold text-primary"
                                                    data-bs-toggle="collapse" data-bs-target="#{{ $pointId }}">
                                                    Ref: {{ $pointLabel }}
                                                </button>
                                                <span class="badge bg-primary">{{ $pointRows->count() }} item(ns)</span>
                                            </div>
                                            <div class="collapse show" id="{{ $pointId }}">
                                                <div class="card-body py-2">
                                                    @foreach ($findingsTree as $catName => $catRows)
                                                        @php
                                                            $catId = 'readonly_cat_' . md5($pointLabel . '_' . $catName);
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
                                                                            $subId = 'readonly_sub_' . md5($pointLabel . '_' . $catName . '_' . $subName);
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

            @if ($production->d5 && $riRequest)
                <section id="ri-request" class="mb-4">
                    <h2 class="h5 mb-3">2. Solicitação Original da RI</h2>
                    <div class="card-soft border-warning">
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <div class="small text-muted">Categoria RI</div>
                                    <div class="fw-semibold">{{ $riRequest['category'] ?? '---' }}</div>
                                </div>
                                <div class="col-md-4">
                                    <div class="small text-muted">Subcategoria</div>
                                    <div class="fw-semibold">{{ $riRequest['subcategory_group'] ?? '---' }} / {{ $riRequest['subcategory'] ?? '---' }}</div>
                                </div>
                                <div class="col-md-4">
                                    <div class="small text-muted">Solicitado por</div>
                                    <div class="fw-semibold">
                                        {{ $riRequest['requested_by'] ?? '---' }}
                                        @if (!empty($riRequest['requested_at']))
                                            <span class="text-muted">({{ $riRequest['requested_at'] }})</span>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            @if (!empty($riRequest['message']))
                                <hr>
                                <div class="small text-muted mb-1">Descrição da solicitação</div>
                                <div class="border rounded p-2 bg-light" style="white-space: pre-line;">{{ $riRequest['message'] }}</div>
                            @endif
                        </div>
                    </div>
                </section>
            @endif

            @if (!$this->isSapReleaseFinalizeFlow)
            <section id="resultado-desenho" class="mb-4">
                <h2 class="h5 mb-3">{{ $production->d5 ? '3' : '2' }}. Resultado do Desenho</h2>
                <div class="card-soft">
                    <div class="card-body">
                        <form>
                            <div class="row g-3 align-items-end no-edge">
                                <div class="col-lg-4">
                                    <label class="form-label fw-semibold">Finalidade</label>
                                    <select class="form-select @error('preresult') is-invalid @enderror" wire:model="preresult"
                                        @disabled($production && (int) $production->status === \App\Models\Production::STATUS_REJECTED_PROJECT_REVIEW)>
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
                                        wire:model="postes" @disabled(($preresult !== 'NORMAL' && $preresult !== 'REVALIDACAO') || in_array($conclusion, ['ARQUIVADO', 'RETORNADO LEVANTAMENTO']))>
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
                                        <input class="form-check-input @error('eo') is-invalid @enderror" type="checkbox" wire:model="eo" id="eoCheck">
                                        <label class="form-check-label" for="eoCheck">EO</label>
                                    </div>
                                    <div class="col-auto form-check">
                                        <input class="form-check-input @error('iproject') is-invalid @enderror" type="checkbox" wire:model="iproject" id="ipCheck">
                                        <label class="form-check-label" for="ipCheck">iProject</label>
                                    </div>
                                    <div class="col-auto form-check">
                                        <input class="form-check-input @error('cad') is-invalid @enderror" type="checkbox" wire:model="cad" id="cadCheck">
                                        <label class="form-check-label" for="cadCheck">AutoCad</label>
                                    </div>
                                    <div class="col-auto form-check">
                                        <input class="form-check-input @error('cadastro') is-invalid @enderror" type="checkbox" wire:model="cadastro" id="cadCadastroCheck">
                                        <label class="form-check-label" for="cadCadastroCheck">Cadastro</label>
                                    </div>
                                @endif

                                @if ($cadastro)
                                    <div class="col-md-3">
                                        <label class="form-label fw-semibold">Postes Cadastro</label>
                                        <input type="number" min="0" max="300" class="form-control @error('postes_c') is-invalid @enderror" wire:model="postes_c">
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
            @else
            <section id="sap-release-flow" class="mb-4">
                <h2 class="h5 mb-3">2. Finalização no SAP</h2>
                <div class="card-soft border-success">
                    <div class="card-body">
                        <div class="alert alert-success mb-0">
                            <strong>Liberado para finalizar.</strong><br>
                            Esta nota já foi aprovada na Análise de Projeto com necessidade de liberação no SAP.
                            Neste fluxo, não é necessário ajustar campos técnicos de desenho.
                            Se precisar, apenas anexe novo arquivo e registre informações adicionais antes de finalizar.
                        </div>
                    </div>
                </div>
            </section>
            @endif

            @if ($this->shouldSendToProjectReview)
                <section id="project-review-data" class="mb-4">
                    <h2 class="h5 mb-3">{{ $production->d5 ? '4' : '3' }}. Dados para Análise de Projeto</h2>
                        <div class="card-soft">
                        <div class="card-body">
                            <h6 class="mb-3">Ordens e Valores</h6>
                            <div class="order-guide-card mb-3 small">
                                <h6>Instruções de Uso: Como Informar as Ordens</h6>
                                <p class="mb-2">Para evitar erro no envio, siga este padrão:</p>
                                <ul>
                                    <li>Digite <strong>apenas 1 número de ordem</strong> por vez.</li>
                                    <li>Preencha os valores da mesma ordem e clique em <strong>Adicionar</strong>.</li>
                                    <li>Se houver outra ordem, repita em um novo lançamento.</li>
                                </ul>
                                @if ($production && (int) $production->status === \App\Models\Production::STATUS_REJECTED_PROJECT_REVIEW)
                                    <p class="mt-2 mb-0 text-danger fw-semibold">
                                        Em retorno para encerramento, não adicione nova ordem para corrigir valor.
                                        Basta ajustar os valores da ordem já exibida na tela.
                                    </p>
                                @endif
                                <p class="mt-2 mb-0">
                                    Exemplo correto: <code>123456</code>.<br>
                                    Exemplo incorreto: <code>123456 789012</code>, <code>123456,789012</code>, <code>123456;789012</code>.
                                </p>
                            </div>
                            <div class="row g-2 mb-2 align-items-end no-edge" data-order-calc-scope="new">
                                <div class="col-md-3">
                                    <label class="form-label">Número da ordem</label>
                                    <input type="text" class="form-control @error('order_input_number') is-invalid @enderror"
                                        wire:model.defer="order_input_number" data-order-number-field>
                                    <div class="invalid-feedback d-none" data-order-number-feedback>
                                        Número da ordem inválido.
                                    </div>
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
                                    <button type="button" class="btn btn-outline-primary w-100" wire:click="addOrderToList"
                                        data-order-add-button>
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

                        </div>
                    </div>
                </section>
            @endif

            @if ($production->status === 31 && $this->shouldSendToProjectReview)
                <section id="project-review-rejected" class="mb-4">
                <h2 class="h5 mb-3 text-danger">{{ $this->shouldSendToProjectReview ? '4' : '3' }}. Apontamentos da Reprovação</h2>
                    <div class="card border-danger shadow-sm">
                        <div class="card-body">
                            @php
                                $filteredFindings = collect($this->filteredRejectedFindings ?? [])->values();
                            @endphp
                            @if ($filteredFindings->count() > 0)
                                <div class="row g-2 mb-3">
                                    <div class="col-md-5">
                                        <label class="form-label mb-1">Filtrar por ref:</label>
                                        <select class="form-select form-select-sm" wire:model="selectedReviewPointFilter">
                                            <option value="">Todas as refs</option>
                                            @foreach ($this->availableReviewPoints as $point)
                                                <option value="{{ $point }}">{{ $point }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                @php
                                    $pointsTree = $filteredFindings->groupBy(fn($finding) => data_get($finding, 'point_label') ?: 'SEM REFERENCIA');
                                @endphp
                                @foreach ($pointsTree as $pointLabel => $pointRows)
                                    @php
                                        $pointId = 'point_' . md5($pointLabel);
                                        $findingsTree = $pointRows->groupBy(function ($finding) {
                                            return data_get($finding, 'category_name') ?: 'Sem categoria';
                                        });
                                    @endphp
                                    <div class="card mb-2 border-primary-subtle">
                                        <div class="card-header d-flex justify-content-between align-items-center bg-primary-subtle">
                                            <button class="btn btn-link text-decoration-none p-0 fw-semibold text-primary"
                                                data-bs-toggle="collapse" data-bs-target="#{{ $pointId }}">
                                                Ref: {{ $pointLabel }}
                                            </button>
                                            <span class="badge bg-primary">{{ $pointRows->count() }} item(ns)</span>
                                        </div>
                                        <div class="collapse show" id="{{ $pointId }}">
                                            <div class="card-body py-2">
                                                @foreach ($findingsTree as $catName => $catRows)
                                                    @php
                                                        $catId = 'cat_' . md5($pointLabel . '_' . $catName);
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
                                                                        $subId = 'sub_' . md5($pointLabel . '_' . $catName . '_' . $subName);
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
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            @else
                                <div class="alert alert-warning mb-0">
                                    Não há pendências tratáveis para Projeto/Ambos nesta reprovação.
                                </div>
                            @endif

                            <hr>
                            <h6>Chat de comentários com o analista</h6>
                            <div class="chat-stream mb-2">
                                @forelse ($reviewMessages as $msg)
                                    @php
                                        $msgUserId = data_get($msg, 'user_id');
                                        $mine = (string) $msgUserId === (string) auth()->id();
                                        $msgAuthor = data_get($msg, 'User.name') ?? data_get($msg, 'user.name');
                                        $msgCreatedAt = data_get($msg, 'created_at');
                                    @endphp
                                    <div class="d-flex mb-2 {{ $mine ? 'justify-content-end' : 'justify-content-start' }}">
                                        <div class="chat-bubble {{ $mine ? 'mine' : '' }}">
                                            <div class="small text-muted">
                                                {{ $msgAuthor }} -
                                                {{ $msgCreatedAt ? date('d/m/Y H:i', strtotime((string) $msgCreatedAt)) : '---' }}
                                            </div>
                                            <div>{{ data_get($msg, 'message') }}</div>
                                        </div>
                                    </div>
                                @empty
                                    <div class="small text-muted">Sem mensagens ainda nesta rodada.</div>
                                @endforelse
                            </div>
                            <textarea class="form-control mt-2" rows="2" wire:model.defer="newContestationMessage" placeholder="Escreva uma mensagem"></textarea>
                            <button type="button" class="btn btn-sm btn-outline-primary mt-2" wire:click="addContestationMessage">Enviar mensagem</button>
                        </div>
                    </div>
                </section>
            @endif

            <section id="arquivos-info" class="mb-5">
                <h2 class="h5 mb-3">{{ $this->isSapReleaseFinalizeFlow ? '3' : ($this->shouldSendToProjectReview ? ((int) ($production->status ?? 0) === \App\Models\Production::STATUS_REJECTED_PROJECT_REVIEW ? '5' : '4') : '3') }}. Arquivos & Informações</h2>
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
                @php
                    $isAnalysisProduction = in_array((int) ($production->status ?? 0), [
                        \App\Models\Production::STATUS_IN_PROJECT_REVIEW,
                        \App\Models\Production::STATUS_REJECTED_PROJECT_REVIEW,
                        \App\Models\Production::STATUS_RELEASED_TO_FINISH,
                    ], true);
                @endphp
                <div class="container-fluid px-3 px-lg-4 d-flex justify-content-end gap-2">
                    @if ($this->isSapReleaseFinalizeFlow)
                        <button class="btn btn-success" wire:click.prevent="to_finish({{ $analise->production_id }})">
                            Finalizar no SAP
                        </button>
                    @else
                        @if (!(bool) ($production->completed ?? false) && !$isAnalysisProduction)
                            <button class="btn btn-warning" wire:click.prevent="to_pause">Pausar</button>
                        @endif
                        @if ($isAnalysisProduction)
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                                Fechar
                            </button>
                        @endif
                        <button class="btn btn-primary" wire:click.prevent="save_info">Salvar</button>
                        <button class="btn btn-success" wire:click.prevent="to_finish({{ $analise->production_id }})">{{ $this->shouldSendToProjectReview ? 'Enviar para análise' : 'Encerrar' }}</button>
                    @endif
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
        const orderGuardNoteReference = @json((string) ($note->note ?? ''));

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

        function roundMoney(value) {
            return Math.round((Number(value) + Number.EPSILON) * 100) / 100;
        }

        function writeInputValue(input, value) {
            if (!input) return;
            input.value = value === null ? '' : formatBrMoney(value);
        }

        function applyAutoFillCosts(scopeEl, changedField = null) {
            if (!scopeEl || scopeEl.dataset.orderCalcLock === '1') return;

            const totalInput = scopeEl.querySelector('[data-order-field="total"]');
            const companyInput = scopeEl.querySelector('[data-order-field="company"]');
            const clientInput = scopeEl.querySelector('[data-order-field="client"]');
            if (!totalInput || !companyInput || !clientInput) return;

            let total = parseBrMoney(totalInput.value);
            let company = parseBrMoney(companyInput.value);
            let client = parseBrMoney(clientInput.value);

            const prevCompanyRatio = (() => {
                const prev = parseFloat(scopeEl.dataset.companyRatio || '');
                return Number.isFinite(prev) ? Math.max(0, Math.min(1, prev)) : 0;
            })();

            const lockedTotalEditRatio = (() => {
                const locked = parseFloat(scopeEl.dataset.totalEditCompanyRatio || '');
                return Number.isFinite(locked) ? Math.max(0, Math.min(1, locked)) : null;
            })();

            let changed = false;

            if (changedField === 'total') {
                if (total !== null) {
                    if (company !== null && client !== null) {
                        const ratioBase = company + client;
                        const ratioCompany = lockedTotalEditRatio !== null
                            ? lockedTotalEditRatio
                            : (ratioBase > 0 ? (company / ratioBase) : prevCompanyRatio);

                        company = roundMoney(total * ratioCompany);
                        client = roundMoney(total - company);
                        writeInputValue(companyInput, company);
                        writeInputValue(clientInput, client);
                        changed = true;
                    } else if (company !== null && client === null) {
                        client = roundMoney(total - company);
                        if (client < 0) {
                            company = total;
                            client = 0;
                            writeInputValue(companyInput, company);
                        }
                        writeInputValue(clientInput, client);
                        changed = true;
                    } else if (client !== null && company === null) {
                        company = roundMoney(total - client);
                        if (company < 0) {
                            client = total;
                            company = 0;
                            writeInputValue(clientInput, client);
                        }
                        writeInputValue(companyInput, company);
                        changed = true;
                    } else {
                        company = 0;
                        client = total;
                        writeInputValue(companyInput, company);
                        writeInputValue(clientInput, client);
                        changed = true;
                    }
                }
            } else if (changedField === 'company') {
                if (company !== null && total !== null) {
                    if (company > total) {
                        company = total;
                        writeInputValue(companyInput, company);
                    }
                    client = roundMoney(total - company);
                    writeInputValue(clientInput, client);
                    changed = true;
                } else if (company !== null && client !== null && total === null) {
                    total = roundMoney(company + client);
                    writeInputValue(totalInput, total);
                    changed = true;
                }
            } else if (changedField === 'client') {
                if (client !== null && total !== null) {
                    if (client > total) {
                        client = total;
                        writeInputValue(clientInput, client);
                    }
                    company = roundMoney(total - client);
                    writeInputValue(companyInput, company);
                    changed = true;
                } else if (client !== null && company !== null && total === null) {
                    total = roundMoney(company + client);
                    writeInputValue(totalInput, total);
                    changed = true;
                }
            } else if (company !== null && client !== null && total === null) {
                total = roundMoney(company + client);
                writeInputValue(totalInput, total);
                changed = true;
            } else if (total !== null && company !== null && client === null) {
                client = roundMoney(total - company);
                if (client < 0) client = 0;
                writeInputValue(clientInput, client);
                changed = true;
            } else if (total !== null && client !== null && company === null) {
                company = roundMoney(total - client);
                if (company < 0) company = 0;
                writeInputValue(companyInput, company);
                changed = true;
            } else if (total !== null && company === null && client === null) {
                company = 0;
                client = total;
                writeInputValue(companyInput, company);
                writeInputValue(clientInput, client);
                changed = true;
            }

            const base = (company ?? 0) + (client ?? 0);
            if (base > 0) {
                scopeEl.dataset.companyRatio = String((company ?? 0) / base);
            } else {
                scopeEl.dataset.companyRatio = '0';
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

        function syncOrderRatios() {
            document.querySelectorAll('[data-order-calc-scope]').forEach(function(scopeEl) {
                const companyInput = scopeEl.querySelector('[data-order-field="company"]');
                const clientInput = scopeEl.querySelector('[data-order-field="client"]');
                if (!companyInput || !clientInput) return;

                const company = parseBrMoney(companyInput.value);
                const client = parseBrMoney(clientInput.value);
                const base = (company ?? 0) + (client ?? 0);
                scopeEl.dataset.companyRatio = base > 0 ? String((company ?? 0) / base) : '0';
            });
        }

        function bindOrderAutoFill() {
            document.querySelectorAll('[data-order-calc-scope]').forEach(function(scopeEl) {
                if (scopeEl.dataset.orderCalcBound === '1') return;
                scopeEl.dataset.orderCalcBound = '1';
                scopeEl.dataset.companyRatio = scopeEl.dataset.companyRatio || '0';

                const rememberField = function(e) {
                    if (!e.target || !e.target.matches('[data-order-field]')) return;
                    const field = e.target.getAttribute('data-order-field') || '';
                    scopeEl.dataset.lastEditedField = field;

                    if (field === 'total') {
                        const companyInput = scopeEl.querySelector('[data-order-field="company"]');
                        const clientInput = scopeEl.querySelector('[data-order-field="client"]');
                        const company = parseBrMoney(companyInput?.value);
                        const client = parseBrMoney(clientInput?.value);
                        const base = (company ?? 0) + (client ?? 0);

                        if (base > 0) {
                            scopeEl.dataset.totalEditCompanyRatio = String((company ?? 0) / base);
                        } else {
                            scopeEl.dataset.totalEditCompanyRatio = scopeEl.dataset.companyRatio || '0';
                        }
                    }
                };

                const recalcHandler = function(e) {
                    if (!e.target || !e.target.matches('[data-order-field]')) return;
                    const changedField = e.target.getAttribute('data-order-field') || scopeEl.dataset.lastEditedField || null;
                    applyAutoFillCosts(scopeEl, changedField);
                };

                scopeEl.addEventListener('focusin', rememberField);
                scopeEl.addEventListener('input', recalcHandler);
                scopeEl.addEventListener('change', recalcHandler);
                scopeEl.addEventListener('blur', recalcHandler, true);
                scopeEl.addEventListener('focusout', function(e) {
                    if (!e.target || !e.target.matches('[data-order-field="total"]')) return;
                    delete scopeEl.dataset.totalEditCompanyRatio;
                });
            });

            syncOrderRatios();
            document.querySelectorAll('[data-order-calc-scope]').forEach(function(scopeEl) {
                applyAutoFillCosts(scopeEl);
            });
        }

        function hasMultipleNumericValues(rawValue) {
            if (rawValue === null || rawValue === undefined) return false;
            const matches = String(rawValue).match(/\d+/g) || [];
            return matches.length > 1;
        }

        function bindOrderNumberGuard() {
            function noteRequiresPrefix200(noteValue) {
                const digits = String(noteValue || '').replace(/\D+/g, '');
                if (!digits.length) return false;
                return Number(digits.charAt(0)) >= 3;
            }

            function getOrderNumberValidationMessage(rawValue) {
                const value = String(rawValue || '').trim();
                if (!value.length) return null;
                if (!/^\d+$/.test(value)) return 'Número da ordem inválido: use apenas números.';
                if (value.length !== 12) return 'Número da ordem inválido: informe exatamente 12 dígitos.';
                if (noteRequiresPrefix200(orderGuardNoteReference) && !/^200/.test(value)) {
                    return 'Número da ordem inválido: para esta Nota/OV o prefixo deve iniciar com 200.';
                }
                if (!/^(170|190|150|200)/.test(value)) return 'Número da ordem inválido: o prefixo deve iniciar com 170, 190, 150 ou 200.';
                return null;
            }

            document.querySelectorAll('[data-order-number-field]').forEach(function(input) {
                if (input.dataset.orderNumberBound === '1') return;
                input.dataset.orderNumberBound = '1';

                const scopeEl = input.closest('[data-order-calc-scope="new"]') || document;
                const feedback = scopeEl.querySelector('[data-order-number-feedback]');
                const addButton = scopeEl.querySelector('[data-order-add-button]');

                const validate = function() {
                    const value = String(input.value || '').trim();
                    let message = null;
                    if (value.length > 0 && hasMultipleNumericValues(value)) {
                        message = 'Informe somente uma ordem por campo.';
                    } else {
                        message = getOrderNumberValidationMessage(value);
                    }
                    const invalid = !!message;
                    input.classList.toggle('is-invalid', invalid);
                    if (feedback) {
                        feedback.classList.toggle('d-none', !invalid);
                        if (invalid) {
                            feedback.textContent = message;
                        }
                    }
                };

                input.addEventListener('input', validate);
                input.addEventListener('change', validate);
                input.addEventListener('blur', validate);
                validate();
            });
        }

        document.addEventListener('livewire:load', function() {
            bindBrMoneyMasks();
            bindOrderAutoFill();
            bindOrderNumberGuard();
            Livewire.hook('message.processed', function() {
                bindBrMoneyMasks();
                bindOrderAutoFill();
                bindOrderNumberGuard();
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

        window.addEventListener('confirmProjectReviewNewOrderPrefix', function(e) {
            const payload = e.detail || {};
            const componentId = payload.componentId || null;
            const orderNumber = payload.orderNumber || '---';
            const prefix = payload.prefix || '---';

            Swal.fire({
                title: 'Confirmar nova ordem com prefixo existente?',
                html: `
                    <div class="text-start">
                        <p class="mb-2">Você está adicionando a ordem <strong>${orderNumber}</strong> com prefixo <strong>${prefix}</strong> já existente nesta reprovação.</p>
                        <p class="mb-2">Normalmente isso indica <strong>correção da ordem existente</strong>, não inclusão de novo número.</p>
                        <p class="mb-0 text-danger"><strong>Confirma ciência</strong> de que não cancelar o número anterior pode gerar problemas nas próximas etapas?</p>
                    </div>
                `,
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Sim, incluir novo número',
                cancelButtonText: 'Não, vou corrigir o existente',
                confirmButtonColor: '#0f766e',
            }).then((result) => {
                if (!result.isConfirmed || !componentId) return;
                const component = Livewire.find(componentId);
                if (component) {
                    component.call('confirmAddOrderAfterPrefixCheck');
                }
            });
        });
    })();
</script>
