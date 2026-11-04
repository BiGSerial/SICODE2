@php
    $selectedName = $detail['user']->name ?? null;
    $situation = fn ($row) => $row->valid_from->isFuture()
        ? ['amber', 'Agendada']
        : ['green', 'Vigente'];
@endphp

{{-- Hierarquia de usuários: árvore montada no navegador (resources/js/hierarchy/org.js), painel e gravações no Livewire. Padrão: docs/admin/HIERARQUIA.md. --}}
<div class="closure-dash my-panel ops-list hier-org" x-data="hierOrg()" x-init="boot()"
     data-nodes-url="{{ route('admin.user.hierarchy.nodes') }}" data-selected="{{ $selectedId }}">

    <x-ops.hero eyebrow="Administração · Cadastros" title="Hierarquia de usuários"
        subtitle="Veja o organograma, arraste uma pessoa para o novo gestor e confira quem cobre quem.">
        <div class="d-flex flex-wrap gap-2 justify-content-end">
            <div class="hier-tabs" role="tablist" aria-label="Visão">
                <button type="button" role="tab" class="{{ $tab === 'tree' ? 'is-on' : '' }}" wire:click="setTab('tree')"><i class="ri-organization-chart" aria-hidden="true"></i> Organograma</button>
                <button type="button" role="tab" class="{{ $tab === 'coverage' ? 'is-on' : '' }}" wire:click="setTab('coverage')"><i class="ri-shield-user-line" aria-hidden="true"></i> Quem cobre quem</button>
            </div>
            <button type="button" class="btn btn-light btn-sm btn-dash" @click="load()" title="Recarregar a árvore"><i class="ri-refresh-line" aria-hidden="true"></i> Atualizar</button>
        </div>
    </x-ops.hero>

    {{-- Contêiner sempre presente: se o aviso entrasse e saísse como irmão, o diff do Livewire deslocaria os blocos seguintes e recriaria a árvore. --}}
    <div wire:key="hier-flash">
    @if ($flash)
        <div class="alert alert-{{ $flashType }} d-flex justify-content-between align-items-center py-2 hier-flash" role="status">
            <span>{{ $flash }}</span>
            <button type="button" class="btn-close" aria-label="Fechar" wire:click="$set('flash', null)"></button>
        </div>
    @endif
    </div>

    <div class="hier-grid" @class(['d-none' => $tab !== 'tree'])>
        {{-- ===== Árvore (todo o DOM é do Alpine; o Livewire não mexe aqui) ===== --}}
        <section class="mp-panel hier-tree-panel" wire:ignore>
            <div class="hier-toolbar">
                <x-ops.search x-model.debounce.200ms="q" placeholder="Buscar nome ou e-mail na árvore" label="Buscar na árvore" />
                <select class="form-select form-select-sm hier-company" x-model="company" aria-label="Filtrar por empresa">
                    <option value="">Todas as empresas</option>
                    <template x-for="c in companies" :key="c"><option :value="c" x-text="c"></option></template>
                </select>
                <button type="button" class="btn btn-outline-secondary btn-sm" :class="{ 'active': onlyCover }" @click="onlyCover = !onlyCover" title="Mostra só quem tem cobertura ou observação vigente"><i class="ri-shield-user-line" aria-hidden="true"></i> Com cobertura</button>
                <div class="btn-group btn-group-sm" role="group" aria-label="Níveis" x-show="view === 'list'">
                    <button type="button" class="btn btn-outline-secondary" @click="expandTo(1)" title="Mostrar só o topo">Topo</button>
                    <button type="button" class="btn btn-outline-secondary" @click="expandTo(2)" title="Abrir dois níveis">2 níveis</button>
                    <button type="button" class="btn btn-outline-secondary" @click="expandTo(99)" title="Abrir tudo">Tudo</button>
                </div>
                <div class="btn-group btn-group-sm ms-auto" role="group" aria-label="Tipo de visão">
                    <button type="button" class="btn btn-outline-secondary" :class="{ active: view === 'chart' }" @click="view = 'chart'"><i class="ri-organization-chart" aria-hidden="true"></i> Organograma</button>
                    <button type="button" class="btn btn-outline-secondary" :class="{ active: view === 'list' }" @click="view = 'list'"><i class="ri-list-check" aria-hidden="true"></i> Lista</button>
                </div>
            </div>

            <div class="hier-cycle tone-red" x-show="loops.length" x-cloak>
                <i class="ri-error-warning-line" aria-hidden="true"></i>
                <div>
                    <strong>Ciclo na hierarquia.</strong>
                    Estas pessoas respondem umas às outras e, por isso, não têm ninguém acima. O desenho abre o ciclo só na tela, sem mudar o cadastro.
                    <template x-for="l in loops" :key="l.id">
                        <div class="hier-cycle-item">
                            <span x-text="l.members.map((id) => nameOf(id)).join(' → ') + ' → ' + nameOf(l.members[0])"></span>
                            <button type="button" class="btn btn-sm btn-outline-danger" @click="select(l.id); view = 'chart'" x-text="'Corrigir ' + firstName(l.id)"></button>
                        </div>
                    </template>
                    <small class="d-block">Para corrigir, abra uma das pessoas e use "Mover para…" ou "Sem gestor".</small>
                </div>
            </div>

            {{-- ===== Visão gráfica (d3-org-chart): cartões ligados por linhas; arraste um cartão sobre outro para mudar de gestor ===== --}}
            <div class="hier-chart-wrap" x-show="view === 'chart'">
                <div class="hier-chart-bar">
                    <div class="btn-group btn-group-sm" role="group" aria-label="Zoom">
                        <button type="button" class="btn btn-outline-secondary" @click="chartZoom(1)" title="Aproximar"><i class="ri-zoom-in-line" aria-hidden="true"></i></button>
                        <button type="button" class="btn btn-outline-secondary" @click="chartZoom(-1)" title="Afastar"><i class="ri-zoom-out-line" aria-hidden="true"></i></button>
                        <button type="button" class="btn btn-outline-secondary" @click="chartFit()" title="Enquadrar tudo na tela"><i class="ri-focus-3-line" aria-hidden="true"></i> Enquadrar</button>
                    </div>
                    <div class="btn-group btn-group-sm" role="group" aria-label="Ramos">
                        <button type="button" class="btn btn-outline-secondary" @click="chartExpandAll()" title="Abrir todos os ramos"><i class="ri-node-tree" aria-hidden="true"></i> Abrir tudo</button>
                        <button type="button" class="btn btn-outline-secondary" @click="chartCollapseAll()" title="Recolher todos os ramos">Recolher</button>
                    </div>
                    <button type="button" class="btn btn-outline-secondary btn-sm" @click="chartLayout()" title="Alternar vertical / horizontal"><i class="ri-layout-masonry-line" aria-hidden="true"></i> <span x-text="layout === 'top' ? 'Horizontal' : 'Vertical'"></span></button>
                    <button type="button" class="btn btn-outline-secondary btn-sm" @click="chartImage()" title="Baixar o organograma como imagem"><i class="ri-image-line" aria-hidden="true"></i> Imagem</button>
                    <button type="button" class="btn btn-sm btn-outline-primary" x-show="focusId" x-cloak @click="focusId = null"><i class="ri-close-line" aria-hidden="true"></i> Ver toda a organização</button>
                    <button type="button" class="btn btn-outline-secondary btn-sm" :class="{ active: showLinks }" @click="showLinks = !showLinks" title="Mostra no desenho as linhas de cobertura e de observação"><i class="ri-links-line" aria-hidden="true"></i> Relações</button>
                    <span class="hier-legend">
                        <span class="hier-legend-line tone-amber" x-show="showLinks"><i></i> coberto por</span>
                        <span class="hier-legend-line tone-blue" x-show="showLinks"><i></i> observa</span>
                        <template x-for="c in companies.slice(0, 6)" :key="c"><span :class="'tone-' + toneOf(c)"><i class="hier-dot"></i> <span x-text="c"></span></span></template>
                    </span>
                </div>

                <div class="hier-tray" x-show="solos.length" x-cloak>
                    <button type="button" class="hier-tray-head" @click="trayOpen = !trayOpen">
                        <i :class="trayOpen ? 'ri-arrow-down-s-line' : 'ri-arrow-right-s-line'" aria-hidden="true"></i> Sem gestor e sem equipe <span class="ops-badge ops-badge--sm tone-muted" x-text="solos.length"></span>
                    </button>
                    <div class="hier-tray-body" x-show="trayOpen">
                        <template x-for="p in solos.filter((x) => !q.trim() || norm(x.n).includes(norm(q.trim())))" :key="p.id">
                            <div class="hier-chip" :class="[ 'tone-' + toneOf(p.c), { 'is-selected': selected === p.id } ]" draggable="true" @click="select(p.id)" @dragstart="dragStart($event, p.id)" @dragend="dragEnd()" :title="p.n + (p.c ? ' · ' + p.c : '') + ' — arraste para um gestor'">
                                <span class="ops-avatar" x-text="initials(p.n)"></span><span x-text="p.n"></span>
                            </div>
                        </template>
                    </div>
                </div>

                <div class="hier-chart" x-ref="chart" wire:ignore></div>
                <div class="hier-empty" x-show="loading">Carregando o organograma…</div>
            </div>

            <div x-show="view === 'list'">
            <div class="hier-marks" x-show="markedIds().length" x-cloak>
                <span><strong x-text="markedIds().length"></strong> marcado(s). Arraste um deles para o novo gestor.</span>
                <button type="button" class="btn btn-sm btn-outline-secondary" @click="marks = {}">Limpar</button>
            </div>

            <div class="hier-empty" x-show="loading && view === 'list'">Carregando o organograma…</div>
            <div class="hier-empty" x-show="!loading && rows.length === 0 && view === 'list'" x-cloak>Ninguém encontrado com esses critérios.</div>

            <div class="hier-drop-root" x-show="dragIds.length" x-cloak
                 :class="{ 'is-over': overId === '__root' }"
                 @dragover.prevent="overId = '__root'" @dragleave="overId = null" @drop.prevent="drop(null)">
                <i class="ri-arrow-up-circle-line" aria-hidden="true"></i> Solte aqui para deixar sem gestor
            </div>

            <ul class="hier-tree" role="tree" x-show="!loading">
                <template x-for="r in rows" :key="r.key">
                    <li role="treeitem" :aria-level="r.depth + 1" :aria-expanded="r.hasKids ? String(isOpen(r)) : null">
                        <template x-if="r.group">
                            <button type="button" class="hier-group" :style="`--depth:${r.depth}`" @click="toggle(r)">
                                <i :class="isOpen(r) ? 'ri-arrow-down-s-line' : 'ri-arrow-right-s-line'" aria-hidden="true"></i>
                                <span x-text="r.label"></span> <span class="ops-badge ops-badge--sm tone-muted" x-text="r.count"></span>
                            </button>
                        </template>
                        <template x-if="!r.group">
                            <div class="hier-row" :style="`--depth:${r.depth}`"
                                 :class="{ 'is-selected': selected === r.id, 'is-over': overId === r.id, 'is-invalid': dragIds.length && invalidTarget(r.id), 'is-marked': marks[r.id], 'is-dragging': dragIds.includes(r.id) }"
                                 draggable="true"
                                 @click="select(r.id)"
                                 @dragstart="dragStart($event, r.id)" @dragend="dragEnd()"
                                 @dragover.prevent="dragOver(r.id)" @dragleave="overId = null" @drop.prevent="drop(r.id)">
                                <button type="button" class="hier-caret" :class="{ 'is-empty': !r.hasKids }" @click.stop="toggle(r)" :aria-label="isOpen(r) ? 'Recolher' : 'Expandir'">
                                    <i :class="isOpen(r) ? 'ri-arrow-down-s-line' : 'ri-arrow-right-s-line'" aria-hidden="true"></i>
                                </button>
                                <input type="checkbox" class="form-check-input hier-mark" :checked="marks[r.id]" @click.stop="mark(r.id)" aria-label="Marcar para mover junto">
                                <span class="ops-avatar" x-text="initials(r.n)"></span>
                                <span class="hier-name">
                                    <strong x-html="hl(r.n)"></strong>
                                    <small x-html="hl(r.e || '')"></small>
                                </span>
                                <span class="hier-tags">
                                    <span class="ops-badge ops-badge--sm tone-teal" x-show="r.c" x-text="r.c" :title="'Empresa: ' + r.c"></span>
                                    <span class="ops-badge ops-badge--sm tone-amber" x-show="covers[r.id]" :title="coverTitle(r.id)"><i class="ri-shield-user-line" aria-hidden="true"></i> <span x-text="'Coberto por ' + firstName(covers[r.id] && covers[r.id].d)"></span></span>
                                    <span class="ops-badge ops-badge--sm tone-blue" x-show="covering[r.id]" :title="'Cobre ' + nameOf(covering[r.id])"><i class="ri-user-follow-line" aria-hidden="true"></i> <span x-text="'Cobre ' + firstName(covering[r.id])"></span></span>
                                    <span class="ops-badge ops-badge--sm tone-muted" x-show="observers[r.id]" title="Observações vigentes"><i class="ri-eye-line" aria-hidden="true"></i> <span x-text="observers[r.id]"></span></span>
                                    <span class="ops-badge ops-badge--sm tone-muted" x-show="r.hasKids" :title="direct(r.id) + ' direto(s), ' + total(r.id) + ' no total'"><i class="ri-team-line" aria-hidden="true"></i> <span x-text="direct(r.id) + (total(r.id) > direct(r.id) ? ' / ' + total(r.id) : '')"></span></span>
                                </span>
                            </div>
                        </template>
                    </li>
                </template>
            </ul>
            </div>
        </section>

        {{-- ===== Painel do usuário selecionado ===== --}}
        <aside class="mp-panel hier-detail" aria-live="polite">
            @if ($detail)
                @php $u = $detail['user']; @endphp
                <div class="mp-panel-head">
                    <div>
                        <h3 class="mp-panel-title">{{ $u->name }}</h3>
                        <div class="mp-panel-sub">{{ $u->email }}@if ($u->company) · {{ $u->company->name }}@endif</div>
                    </div>
                    <button type="button" class="btn-close" wire:click="select(null)" aria-label="Fechar painel"></button>
                </div>
                <div class="mp-panel-body">
                    <div class="hier-actions">
                        @if ($detail['directs']->isNotEmpty())
                            <button type="button" class="btn btn-sm btn-outline-primary" @click="focus('{{ $u->id }}')" title="Mostra só esta equipe no organograma"><i class="ri-focus-3-line" aria-hidden="true"></i> Ver só esta equipe</button>
                        @endif
                        <button type="button" class="btn btn-sm btn-outline-primary" wire:click="openMovePicker"><i class="ri-corner-down-right-line" aria-hidden="true"></i> Mover para…</button>
                        <button type="button" class="btn btn-sm btn-outline-secondary" wire:click="makeRoot" @disabled(!$u->manager_id)><i class="ri-arrow-up-circle-line" aria-hidden="true"></i> Sem gestor</button>
                        <button type="button" class="btn btn-sm btn-outline-primary" wire:click="openDelegation"><i class="ri-shield-user-line" aria-hidden="true"></i> Definir quem cobre</button>
                        <button type="button" class="btn btn-sm btn-outline-secondary" wire:click="openObservation"><i class="ri-eye-line" aria-hidden="true"></i> Observar equipe</button>
                    </div>

                    <h4 class="hier-h">Cadeia de gestores</h4>
                    @if ($detail['chain']->isEmpty())
                        <p class="text-muted small mb-3">Está no topo: não responde a ninguém.</p>
                    @else
                        <ol class="hier-chain">
                            @foreach ($detail['chain'] as $c)
                                <li><button type="button" class="btn btn-link p-0" wire:click="select('{{ $c->id }}')">{{ $c->name }}</button></li>
                            @endforeach
                            <li class="is-current">{{ $u->name }}</li>
                        </ol>
                    @endif

                    <h4 class="hier-h">Equipe <x-ops.badge tone="muted" sm :label="$detail['directs']->count() . ' direta(s) · ' . $detail['teamSize'] . ' no total'" /></h4>
                    @forelse ($detail['directs'] as $d)
                        <button type="button" class="hier-person" wire:click="select('{{ $d->id }}')">
                            <span class="ops-avatar">{{ mb_strtoupper(mb_substr($d->name, 0, 1)) }}</span>
                            <span>{{ $d->name }}<small>{{ $d->email }}</small></span>
                        </button>
                    @empty
                        <p class="text-muted small">Sem equipe direta.</p>
                    @endforelse

                    <h4 class="hier-h">Quem cobre quem</h4>
                    @forelse ($detail['delegations'] as $dl)
                        @php [$tone, $label] = $situation($dl); $isPrincipal = (string) $dl->principal_id === (string) $u->id; @endphp
                        <div class="hier-item">
                            <div>
                                <x-ops.badge :tone="$tone" sm :label="$label" />
                                <strong>{{ $isPrincipal ? 'Coberto por ' . $dl->delegate?->name : 'Cobre ' . $dl->principal?->name }}</strong>
                                <small>{{ $dl->valid_from->format('d/m/Y') }} → {{ $dl->valid_to ? $dl->valid_to->format('d/m/Y') : 'sem data de fim' }}@if ($dl->reason) · {{ $dl->reason }}@endif</small>
                            </div>
                            <div class="hier-item-actions">
                                <button type="button" class="btn btn-sm btn-outline-secondary" wire:click="askConfirm('delegation', 'finish', '{{ $dl->id }}')" title="Encerrar agora"><i class="ri-stop-circle-line" aria-hidden="true"></i></button>
                                <button type="button" class="btn btn-sm btn-outline-danger" wire:click="askConfirm('delegation', 'delete', '{{ $dl->id }}')" title="Remover"><i class="ri-delete-bin-line" aria-hidden="true"></i></button>
                            </div>
                        </div>
                    @empty
                        <p class="text-muted small">Nenhuma cobertura vigente ou agendada.</p>
                    @endforelse

                    <h4 class="hier-h">Observações</h4>
                    @forelse ($detail['observations'] as $ob)
                        @php [$tone, $label] = $situation($ob); $isObserver = (string) $ob->observer_id === (string) $u->id; @endphp
                        <div class="hier-item">
                            <div>
                                <x-ops.badge :tone="$tone" sm :label="$label" />
                                <strong>{{ $isObserver ? 'Observa ' . $ob->target?->name : 'Observado por ' . $ob->observer?->name }}</strong>
                                <small>{{ $ob->mode === 'subtree' ? 'Com a equipe' : 'Só a pessoa' }} · {{ $ob->valid_from->format('d/m/Y') }} → {{ $ob->valid_to ? $ob->valid_to->format('d/m/Y') : 'sem data de fim' }}</small>
                            </div>
                            <div class="hier-item-actions">
                                <button type="button" class="btn btn-sm btn-outline-secondary" wire:click="askConfirm('observation', 'finish', '{{ $ob->id }}')" title="Encerrar agora"><i class="ri-stop-circle-line" aria-hidden="true"></i></button>
                                <button type="button" class="btn btn-sm btn-outline-danger" wire:click="askConfirm('observation', 'delete', '{{ $ob->id }}')" title="Remover"><i class="ri-delete-bin-line" aria-hidden="true"></i></button>
                            </div>
                        </div>
                    @empty
                        <p class="text-muted small">Nenhuma observação vigente ou agendada.</p>
                    @endforelse
                </div>
            @else
                <div class="mp-panel-body hier-placeholder">
                    <i class="ri-cursor-line" aria-hidden="true"></i>
                    <p>Clique em alguém no organograma para ver gestores, equipe e coberturas.</p>
                    <p class="small text-muted mb-0">Para realinhar, arraste a pessoa sobre o novo gestor. Marque várias caixas para mover um grupo de uma vez.</p>
                </div>
            @endif
        </aside>
    </div>

    {{-- ===== Visão "quem cobre quem" ===== --}}
    @if ($tab === 'coverage')
        <div class="row g-3">
            <div class="col-12 col-xl-6">
                <section class="mp-panel h-100">
                    <div class="mp-panel-head"><h3 class="mp-panel-title">Delegações (quem cobre quem)</h3></div>
                    @if (($coverage['delegations'] ?? collect())->isEmpty())
                        <div class="mp-panel-body text-center text-muted">Nenhuma delegação vigente ou agendada.</div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-sm align-middle mb-0 mp-table">
                                <colgroup><col style="width: 28%"><col style="width: 28%"><col style="width: 28%"><col style="width: 16%"></colgroup>
                                <thead><tr><th>Titular</th><th>Quem cobre</th><th>Período</th><th class="text-center">Situação</th></tr></thead>
                                <tbody>
                                    @foreach ($coverage['delegations'] as $dl)
                                        @php [$tone, $label] = $situation($dl); @endphp
                                        <tr>
                                            <td><button type="button" class="btn btn-link p-0 fw-bold" wire:click="select('{{ $dl->principal_id }}'); setTab('tree')">{{ $dl->principal?->name ?? '—' }}</button></td>
                                            <td>{{ $dl->delegate?->name ?? '—' }}</td>
                                            <td><x-ops.date :date="$dl->valid_from" /> <small class="text-muted">até {{ $dl->valid_to ? $dl->valid_to->format('d/m/Y') : '—' }}</small></td>
                                            <td class="text-center"><x-ops.badge :tone="$tone" sm :label="$label" /></td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </section>
            </div>
            <div class="col-12 col-xl-6">
                <section class="mp-panel h-100">
                    <div class="mp-panel-head"><h3 class="mp-panel-title">Observações de equipe</h3></div>
                    @if (($coverage['observations'] ?? collect())->isEmpty())
                        <div class="mp-panel-body text-center text-muted">Nenhuma observação vigente ou agendada.</div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-sm align-middle mb-0 mp-table">
                                <colgroup><col style="width: 28%"><col style="width: 28%"><col style="width: 28%"><col style="width: 16%"></colgroup>
                                <thead><tr><th>Observador</th><th>Observa</th><th>Período</th><th class="text-center">Situação</th></tr></thead>
                                <tbody>
                                    @foreach ($coverage['observations'] as $ob)
                                        @php [$tone, $label] = $situation($ob); @endphp
                                        <tr>
                                            <td><button type="button" class="btn btn-link p-0 fw-bold" wire:click="select('{{ $ob->observer_id }}'); setTab('tree')">{{ $ob->observer?->name ?? '—' }}</button></td>
                                            <td>{{ $ob->target?->name ?? '—' }} <small class="text-muted">{{ $ob->mode === 'subtree' ? '(com a equipe)' : '(só a pessoa)' }}</small></td>
                                            <td><x-ops.date :date="$ob->valid_from" /> <small class="text-muted">até {{ $ob->valid_to ? $ob->valid_to->format('d/m/Y') : '—' }}</small></td>
                                            <td class="text-center"><x-ops.badge :tone="$tone" sm :label="$label" /></td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </section>
            </div>
        </div>
    @endif

    {{-- ===== Modais (uma raiz por componente; abertos por evento hier-modal) ===== --}}
    <div class="modal fade ops-modal" id="hierMoveModal" tabindex="-1" aria-labelledby="hierMoveTitle" aria-hidden="true" wire:ignore.self>
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content closure-dash ops-list">
                <div class="modal-header">
                    <div><span class="modal-eyebrow">Realinhar hierarquia</span><h5 class="modal-title" id="hierMoveTitle">Confirmar mudança de gestor</h5></div>
                    <button type="button" class="btn-close btn-close-white ms-auto" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body">
                    @if ($moveImpact)
                        <p class="ops-modal-note">Novo gestor: <strong>{{ $moveImpact['target'] ?? 'Ninguém (topo do organograma)' }}</strong></p>
                        <ul class="hier-impact">
                            @foreach ($moveImpact['items'] as $it)
                                <li>
                                    <strong>{{ $it['user'] }}</strong>
                                    @if ($it['subtree'])<x-ops.badge tone="blue" sm :label="'leva ' . $it['subtree'] . ' da equipe'" />@endif
                                    <small>Hoje: {{ $it['from'] ? implode(' › ', $it['from']) : 'topo' }}<br>Depois: {{ $it['to'] ? implode(' › ', $it['to']) : 'topo' }}</small>
                                </li>
                            @endforeach
                        </ul>
                        @if ($moveImpact['skipped'])
                            <p class="text-muted small mb-0">{{ $moveImpact['skipped'] }} selecionado(s) ignorado(s) por já estarem nesse gestor ou criarem ciclo.</p>
                        @endif
                    @endif
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary btn-dash" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary btn-dash" wire:click="confirmMove" wire:loading.attr="disabled" wire:target="confirmMove"><i class="ri-check-line" aria-hidden="true"></i> Confirmar</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade ops-modal" id="hierPickModal" tabindex="-1" aria-labelledby="hierPickTitle" aria-hidden="true" wire:ignore.self>
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content closure-dash ops-list">
                <div class="modal-header">
                    <div><span class="modal-eyebrow">{{ $selectedName }}</span><h5 class="modal-title" id="hierPickTitle">Escolher o novo gestor</h5></div>
                    <button type="button" class="btn-close btn-close-white ms-auto" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body">
                    <input type="search" class="form-control mb-2" placeholder="Digite 2 letras do nome ou e-mail" wire:model.debounce.300ms="pickSearch" aria-label="Buscar gestor">
                    @foreach ($pickResults as $p)
                        <button type="button" class="hier-person" wire:click="pickManager('{{ $p->id }}')"><span class="ops-avatar">{{ mb_strtoupper(mb_substr($p->name, 0, 1)) }}</span><span>{{ $p->name }}<small>{{ $p->email }}</small></span></button>
                    @endforeach
                    @if ($pickSearch !== '' && $pickResults->isEmpty())<p class="text-muted small mb-0">Ninguém encontrado.</p>@endif
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade ops-modal" id="hierDelegationModal" tabindex="-1" aria-labelledby="hierDlgTitle" aria-hidden="true" wire:ignore.self>
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content closure-dash ops-list">
                <div class="modal-header">
                    <div><span class="modal-eyebrow">{{ $selectedName }}</span><h5 class="modal-title" id="hierDlgTitle">Definir quem cobre</h5></div>
                    <button type="button" class="btn-close btn-close-white ms-auto" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body">
                    <label class="form-label text-uppercase">Quem vai cobrir</label>
                    @if ($dlg_delegate_id)
                        <div class="ops-modal-note d-flex justify-content-between align-items-center"><strong>{{ $picked[$dlg_delegate_id] ?? '—' }}</strong><button type="button" class="btn btn-sm btn-outline-secondary" wire:click="$set('dlg_delegate_id', null)">Trocar</button></div>
                    @else
                        <input type="search" class="form-control mb-2" placeholder="Buscar pelo nome ou e-mail" wire:model.debounce.300ms="pickSearch" aria-label="Buscar quem cobre">
                        @if ($pickMode === 'delegate')
                            @foreach ($pickResults as $p)
                                <button type="button" class="hier-person" wire:click="chooseDelegate('{{ $p->id }}')"><span class="ops-avatar">{{ mb_strtoupper(mb_substr($p->name, 0, 1)) }}</span><span>{{ $p->name }}<small>{{ $p->email }}</small></span></button>
                            @endforeach
                        @endif
                    @endif
                    @error('dlg_delegate_id')<div class="text-danger small">{{ $message }}</div>@enderror
                    <div class="row g-2 mt-1">
                        <div class="col-6"><label class="form-label text-uppercase">Início</label><input type="date" class="form-control" wire:model.defer="dlg_from">@error('dlg_from')<div class="text-danger small">{{ $message }}</div>@enderror</div>
                        <div class="col-6"><label class="form-label text-uppercase">Fim (opcional)</label><input type="date" class="form-control" wire:model.defer="dlg_to">@error('dlg_to')<div class="text-danger small">{{ $message }}</div>@enderror</div>
                    </div>
                    <label class="form-label text-uppercase mt-2">Motivo</label>
                    <input type="text" class="form-control" wire:model.defer="dlg_reason" maxlength="120">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary btn-dash" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary btn-dash" wire:click="saveDelegation"><i class="ri-save-line" aria-hidden="true"></i> Salvar</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade ops-modal" id="hierObservationModal" tabindex="-1" aria-labelledby="hierObsTitle" aria-hidden="true" wire:ignore.self>
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content closure-dash ops-list">
                <div class="modal-header">
                    <div><span class="modal-eyebrow">{{ $selectedName }}</span><h5 class="modal-title" id="hierObsTitle">Observar equipe</h5></div>
                    <button type="button" class="btn-close btn-close-white ms-auto" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body">
                    <label class="form-label text-uppercase">Quem será observado</label>
                    @if ($obs_target_id)
                        <div class="ops-modal-note d-flex justify-content-between align-items-center"><strong>{{ $picked[$obs_target_id] ?? '—' }}</strong><button type="button" class="btn btn-sm btn-outline-secondary" wire:click="$set('obs_target_id', null)">Trocar</button></div>
                    @else
                        <input type="search" class="form-control mb-2" placeholder="Buscar pelo nome ou e-mail" wire:model.debounce.300ms="pickSearch" aria-label="Buscar quem observar">
                        @if ($pickMode === 'observe')
                            @foreach ($pickResults as $p)
                                <button type="button" class="hier-person" wire:click="chooseTarget('{{ $p->id }}')"><span class="ops-avatar">{{ mb_strtoupper(mb_substr($p->name, 0, 1)) }}</span><span>{{ $p->name }}<small>{{ $p->email }}</small></span></button>
                            @endforeach
                        @endif
                    @endif
                    @error('obs_target_id')<div class="text-danger small">{{ $message }}</div>@enderror
                    <label class="form-label text-uppercase mt-2">Alcance</label>
                    <select class="form-select" wire:model.defer="obs_mode">
                        <option value="subtree">A pessoa e toda a equipe dela</option>
                        <option value="node_only">Só a pessoa</option>
                    </select>
                    <div class="row g-2 mt-1">
                        <div class="col-6"><label class="form-label text-uppercase">Início</label><input type="date" class="form-control" wire:model.defer="obs_from">@error('obs_from')<div class="text-danger small">{{ $message }}</div>@enderror</div>
                        <div class="col-6"><label class="form-label text-uppercase">Fim (opcional)</label><input type="date" class="form-control" wire:model.defer="obs_to">@error('obs_to')<div class="text-danger small">{{ $message }}</div>@enderror</div>
                    </div>
                    <label class="form-label text-uppercase mt-2">Motivo</label>
                    <input type="text" class="form-control" wire:model.defer="obs_reason" maxlength="120">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary btn-dash" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary btn-dash" wire:click="saveObservation"><i class="ri-save-line" aria-hidden="true"></i> Salvar</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade ops-modal" id="hierConfirmModal" tabindex="-1" aria-labelledby="hierConfirmTitle" aria-hidden="true" wire:ignore.self>
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content closure-dash ops-list">
                <div class="modal-header">
                    <div><span class="modal-eyebrow">Confirmação</span><h5 class="modal-title" id="hierConfirmTitle">{{ ($confirm['action'] ?? '') === 'delete' ? 'Remover registro' : 'Encerrar agora' }}</h5></div>
                    <button type="button" class="btn-close btn-close-white ms-auto" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-0">{{ ($confirm['action'] ?? '') === 'delete' ? 'Remove o registro de vez, sem deixar histórico.' : 'O registro deixa de valer neste instante e fica no histórico.' }}</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary btn-dash" data-bs-dismiss="modal">Voltar</button>
                    <button type="button" class="btn btn-primary btn-dash" wire:click="runConfirm"><i class="ri-check-line" aria-hidden="true"></i> Confirmar</button>
                </div>
            </div>
        </div>
    </div>
</div>
