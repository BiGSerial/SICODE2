<div class="closure-dash">
    <div class="dash-hero mb-3">
        <div class="d-flex flex-wrap align-items-end justify-content-between gap-3">
            <div>
                <h2 class="dash-title">Caixa do Encerramento — {{ $areaLabel }}</h2>
                <div class="dash-subtitle">O que o Encerramento está acompanhando e o que enviou para {{ $areaLabel }}.</div>
            </div>
            @if ($canConfigure)
                <a href="{{ route('closure.status_map') }}" class="btn btn-light btn-sm"><i class="ri-settings-3-line"></i> Configurar status desta caixa</a>
            @endif
        </div>
    </div>

    <x-closure.inbox-tabs class="mb-3" :active="$tab" :tabs="[
        ['key' => 'accompanying', 'label' => 'Em acompanhamento', 'count' => $totalWatched, 'tone' => 'blue', 'icon' => 'ri-eye-line'],
        ['key' => 'dispatched', 'label' => 'Despachadas para ' . $areaLabel, 'count' => $dispatched, 'tone' => 'red', 'icon' => 'ri-send-plane-line'],
    ]" />

    @if ($tab === 'accompanying')
        <div class="inbox-note my-3">
            <i class="ri-information-line" aria-hidden="true"></i>
            <span><strong>Só visão.</strong> São Ordens do Encerramento que estão em status de {{ $areaLabel }}. Nada a responder aqui: a Ordem sai da caixa sozinha quando o status muda.</span>
        </div>

        @if (count($mapped) === 0)
            <div class="table-card p-4">
                <x-closure.empty-state icon="ri-route-line" title="Esta caixa ainda não tem status configurados" text="Escolha quais status do usuário pertencem a {{ $areaLabel }} para as Ordens aparecerem aqui." />
                @if ($canConfigure)
                    <div class="text-center mt-2"><a href="{{ route('closure.status_map') }}" class="btn btn-dash text-white" style="background: var(--dash-green);">Configurar agora</a></div>
                @else
                    <div class="text-center small text-muted">Peça à gestão do Encerramento para configurar o mapa de status.</div>
                @endif
            </div>
        @else
            <div class="row g-2 mb-3">
                @foreach ($counts as $prefix => $count)
                    <div class="col-6 col-md-4 col-xl-2" wire:key="inbox-kpi-{{ $prefix }}">
                        <x-closure.stat-card :label="$prefix" :value="$count" tone="blue" :active="in_array($prefix, $prefixes, true)" wire:click="togglePrefix('{{ $prefix }}')" />
                    </div>
                @endforeach
            </div>

            @php $hasFilters = $search !== '' || $type !== 'all' || count($prefixes) || $holderUserId || $companyId || count($bulkTerms); @endphp
            <div class="table-card filter-bar p-3 mb-3" id="closure-inbox-filters">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <div class="d-flex align-items-center gap-2 fw-bold"><i class="ri-filter-3-line"></i> Filtros</div>
                    <div class="d-flex flex-wrap gap-2">
                        @if ($hasFilters)
                            <button type="button" class="btn btn-link btn-sm text-decoration-none p-0 text-danger" wire:click="clearFilters"><i class="ri-close-circle-line"></i> Limpar filtros</button>
                        @endif
                        <button type="button" class="btn btn-outline-success btn-sm" wire:click="exportList" wire:loading.attr="disabled" wire:target="exportList"><i class="ri-file-excel-2-line"></i> Exportar Excel</button>
                    </div>
                </div>
                <x-closure.bulk-search-result :report="$this->bulkReport()" class="mb-3" />
                <div class="row g-3">
                    <div class="col-12 col-md-6 col-xl-2">
                        <label class="ctl-label" for="inbox-type">Tipo de ordem</label>
                        <select id="inbox-type" class="form-select" wire:model="type">
                            <option value="all">Todos (P / M / F)</option>
                            <option value="P">P — Passivo</option>
                            <option value="M">M — Meta atual</option>
                            <option value="F">F — Meta futura</option>
                        </select>
                    </div>
                    <div class="col-12 col-md-6 col-xl-3">
                        <label class="ctl-label" for="inbox-search">Busca específica</label>
                        <div class="input-group"><span class="input-group-text bg-white"><i class="ri-search-line"></i></span><input id="inbox-search" type="search" class="form-control" wire:model.debounce.400ms="search" placeholder="Ordem ou nota"></div>
                        <div class="mt-1"><x-closure.bulk-search :terms="$bulkTerms" :open="$bulkOpen" /></div>
                    </div>
                    <div class="col-12 col-md-6 col-xl-2">
                        <label class="ctl-label">Status usuário</label>
                        <div class="dropdown" wire:key="inbox-status-filter">
                            <button type="button" class="form-select text-start text-truncate" data-bs-toggle="dropdown" data-bs-auto-close="outside" data-closure-fixed-dropdown aria-expanded="false">{{ count($prefixes) ? 'Status (' . count($prefixes) . ')' : 'Todos os status' }}</button>
                            <div class="dropdown-menu p-2" style="min-width: 260px; max-height: 320px; overflow-y: auto;">
                                @foreach ($counts as $prefix => $count)
                                    <label class="dropdown-item d-flex align-items-center gap-2 rounded" style="cursor:pointer;" wire:key="inbox-su-{{ $prefix }}">
                                        <input type="checkbox" class="form-check-input mt-0" value="{{ $prefix }}" wire:model.defer="prefixes">
                                        <span class="small fw-semibold">{{ $prefix }}</span>
                                        <span class="ms-auto badge bg-light text-dark border">{{ $count }}</span>
                                    </label>
                                @endforeach
                                <div class="d-flex justify-content-between align-items-center border-top pt-2 mt-2 px-1">
                                    <button type="button" class="btn btn-link btn-sm text-decoration-none p-0 text-muted" wire:click="$set('prefixes', [])">Limpar</button>
                                    <button type="button" class="btn btn-sm btn-primary" wire:click="$refresh">Aplicar</button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-md-6 col-xl-2">
                        <label class="ctl-label" for="inbox-user">Responsável (Encerramento)</label>
                        <select id="inbox-user" class="form-select" wire:model="holderUserId">
                            <option value="">Todos</option>
                            @foreach ($holders['users'] as $holder)
                                <option value="{{ $holder->id }}">{{ $holder->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12 col-md-6 col-xl-3">
                        <label class="ctl-label" for="inbox-company">Empresa (informe)</label>
                        <select id="inbox-company" class="form-select" wire:model="companyId">
                            <option value="">Todas</option>
                            @foreach ($holders['companies'] as $holder)
                                <option value="{{ $holder->id }}">{{ $holder->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            <div class="table-card">
                <div class="chart-head">
                    <div>
                        <h3 class="chart-title">Ordens em acompanhamento</h3>
                        <div class="chart-subtitle">Todas as Ordens do Encerramento, despachadas ou não, que estão nos status deste serviço. O tempo no status só existe para as já despachadas.</div>
                    </div>
                    <span class="badge bg-secondary">{{ number_format($accompanying->total(), 0, ',', '.') }} Ordem(ns)</span>
                </div>
                <x-closure.signal-legend />
                <div class="table-responsive">
                    <table class="table table-sm table-hover align-middle mb-0 dash-table">
                        <thead>
                            <tr>
                                <th class="ps-3 text-center">Tipo</th>
                                <th>Nota</th>
                                <th>Ordem</th>
                                <th>Compet.</th>
                                <th>Obra / Município</th>
                                <th class="text-end" title="Custo planejado (acima) e custo realizado (abaixo) da Ordem">Valor da obra</th>
                                <th>Status SAP</th>
                                <th>Status usuário</th>
                                <th>No status desde</th>
                                <th>Situação no Encerramento</th>
                                <th class="pe-3 text-end">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($accompanying as $order)
                                @php
                                    $sig      = \App\Services\Closure\ClosureSignal::forRow($order);
                                    $rowClass = $sig ? 'signal-row signal-' . $sig['key'] : '';
                                    $track    = $order->state_id ? $since->get($order->state_id) : null;
                                @endphp
                                <tr wire:key="inbox-order-{{ $order->id }}" @class([$rowClass => $rowClass !== '']) @if ($sig) data-signal="{{ $sig['key'] }}" title="{{ $sig['label'] }}: {{ $sig['hint'] }}" @endif data-ordem="{{ $order->ordem }}">
                                    <td class="ps-3 text-center"><span class="type-dot badge {{ \App\Services\Closure\ClosureMetaClassifier::badgeClass($order->meta_type) }}" title="{{ \App\Services\Closure\ClosureMetaClassifier::label($order->meta_type) }}">{{ \App\Services\Closure\ClosureMetaClassifier::letter($order->meta_type) ?? '—' }}</span></td>
                                    <td>{{ $order->note_number }}@if ($order->partial_id) <span class="badge bg-light text-dark border" title="Parcial #{{ $order->partial_id }}">Parcial</span>@endif</td>
                                    <td class="fw-semibold">{{ $order->ordem }}</td>
                                    <td class="small text-nowrap">{{ \App\Services\Closure\ClosureMetaClassifier::competenceLabel($order->competence) }}</td>
                                    <td class="small">
                                        <div class="text-truncate" title="{{ $order->descricao }}">{{ $order->descricao ?: '—' }}</div>
                                        <div class="text-muted text-truncate">{{ $order->city_name ?: '—' }}</div>
                                    </td>
                                    <x-closure.order-cost :planned="$order->custPlanejado" :actual="$order->custRealizado" />
                                    <td class="text-muted small"><div class="text-truncate" title="{{ $order->statusSist }}">{{ $order->statusSist ?: '—' }}</div></td>
                                    <td><span class="badge bg-light text-dark border">{{ \App\Services\Closure\ClosureEligibleOrders::statusUserPrefix($order->statusUser) ?: '—' }}</span></td>
                                    <td>
                                        @if ($track?->status_user_changed_at)
                                            <x-closure.age-chip :since="$track->status_user_changed_at" :estimated="(bool) $track->status_user_estimated" />
                                        @else
                                            <span class="small text-muted" title="Sem data registrada: a Ordem ainda não foi despachada ao Encerramento.">sem registro</span>
                                        @endif
                                    </td>
                                    <td class="small">
                                        @if ($order->state_id)
                                            <span class="badge {{ \App\Enum\ClosureOperationalStatus::badgeClassFor($order->operational_status) }}">{{ \App\Enum\ClosureOperationalStatus::labelFor($order->operational_status) }}</span>
                                            <div class="text-muted text-truncate">{{ $order->assignee_name ?: ($order->holder_company_name ? 'Pilha de ' . $order->holder_company_name : '') }}</div>
                                        @else
                                            <span class="d-inline-flex align-items-center gap-1 text-muted"><span class="status-dot" style="background: #cbd5e1;"></span>Não despachada</span>
                                        @endif
                                    </td>
                                    <td class="pe-3 text-end text-nowrap">
                                        <a href="{{ route('closure.work.show', ['note' => $order->note_id, 'ctx' => 'service']) }}" class="icon-btn" title="Abrir obra" aria-label="Abrir obra da Ordem {{ $order->ordem }}"><i class="ri-external-link-line"></i></a>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="11"><x-closure.empty-state title="Nenhuma Ordem neste momento" text="Quando uma Ordem do Encerramento entrar em um status de {{ $areaLabel }}, ela aparece aqui." /></td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="p-3">{{ $accompanying->links() }}</div>
            </div>
        @endif
    @else
        <div class="inbox-note my-3">
            <i class="ri-send-plane-line" aria-hidden="true"></i>
            <span><strong>Ação.</strong> Pendências que o Encerramento enviou para {{ $areaLabel }}.</span>
        </div>
        <div class="table-card">
            <div class="chart-head">
                <div>
                    <h3 class="chart-title">Pendências despachadas</h3>
                </div>
                <select class="form-select form-select-sm w-auto" wire:model="issueStatus" aria-label="Situação">
                    <option value="open">Em aberto</option>
                    <option value="done">Concluídas</option>
                    <option value="all">Todas</option>
                </select>
            </div>
            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="ps-3">Nota</th>
                            <th>Ordem</th>
                            <th>Assunto</th>
                            <th>Situação</th>
                            <th class="pe-3">Enviada</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($issues as $issue)
                            <tr wire:key="inbox-issue-{{ $issue->id }}">
                                <td class="ps-3">{{ $issue->Order?->Note?->note ?? '—' }}</td>
                                <td class="fw-semibold">{{ $issue->Order?->ordem ?? '—' }}</td>
                                <td class="small">{{ $issue->Category?->name ?? '—' }}@if ($issue->Subcategory) <span class="text-muted">/ {{ $issue->Subcategory->name }}</span>@endif</td>
                                <td><span class="badge bg-light text-dark border">{{ $issue->status }}</span></td>
                                <td class="pe-3"><x-closure.age-chip :since="$issue->requested_at ?? $issue->opened_at" /></td>
                            </tr>
                        @empty
                            <tr><td colspan="5"><x-closure.empty-state icon="ri-send-plane-line" title="Nenhuma pendência despachada" text="Quando o Encerramento enviar uma pendência para {{ $areaLabel }}, ela aparece aqui." /></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="p-3">{{ $issues->links() }}</div>
        </div>
    @endif
</div>
