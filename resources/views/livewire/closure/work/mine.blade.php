@php
    $classifier     = \App\Services\Closure\ClosureMetaClassifier::class;
    $presenter      = \App\Services\Closure\ClosureDispatchRowPresenter::class;
    $typeTone       = ['P' => 'red', 'M' => 'green', 'F' => 'blue'];
    $avgMin         = $stats['avg_min'];
    $avgLabel       = $avgMin >= 1440 ? intdiv($avgMin, 1440) . 'd ' . intdiv($avgMin % 1440, 60) . 'h' : ($avgMin >= 60 ? intdiv($avgMin, 60) . 'h ' . str_pad((string) ($avgMin % 60), 2, '0', STR_PAD_LEFT) . 'm' : $avgMin . ' min');
    $showHolder     = auth()->user()->can('closure.manage');
    $riByNote       = app(\App\Services\Closure\ClosureInternalReturnService::class)->activeForNotes($states->getCollection()->map(fn ($st) => $st->Order?->note_id)->filter());
    $allSelected    = $states->count() > 0 && $states->every(fn ($st) => in_array($st->id, $selectedStateIds));
    $loadingTargets = 'search,type,toggleCard,gotoPage,nextPage,previousPage,perPage,clearFilters,removeAnyFilter,applyListFilters,removeListFilter,clearListFilters,applyBulkSearch,selectAllFilteredStates';
    $follow         = $this->isFollow();
    $emptyText      = $follow && !$hasCriteria ? 'Nada aguardando outras pessoas no momento.' : ($hasCriteria ? 'Ajuste ou limpe os filtros para ver mais Ordens.' : ($selfAssignEnabled ? 'Use Pegar atividades para assumir novas Ordens.' : 'Quando o despacho atribuir Ordens a você, elas aparecem aqui.'));
    $initials       = fn (?string $name) => collect(preg_split('/\s+/', trim((string) $name), -1, PREG_SPLIT_NO_EMPTY))->take(2)->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->implode('');
@endphp

{{-- Minha atividade (Serviço): lista operacional (docs/PADRAO_LISTAS_OPERACIONAIS.md). Ordens atribuídas ao usuário; a gestão vê as de todos. --}}
<div class="closure-dash my-panel ops-list">

    <x-ops.hero eyebrow="Serviço · Encerramento" :title="$follow ? 'Acompanhamento' : 'Minha atividade'" :subtitle="$follow ? 'Ordens que não dependem de você agora: aguardando a parceira, o Desenho (RI), a liberação do depósito, a meta futura ou o SAP encerrar. Só acompanhamento.' : 'Ordens atribuídas a você para executar' . ($showHolder ? ' (como gestor, você vê as de todos)' : '') . '. Inicie, pause, finalize ou passe para outra pessoa.'">
        <div class="d-flex flex-wrap gap-2 justify-content-end">
            <x-ops.legend />
            @if ($selfAssignEnabled && !$follow)
                <a href="{{ \App\Services\Closure\ClosureServiceLocator::url('services.main', ['aba' => 'disponiveis']) }}" class="btn btn-light btn-sm btn-dash"><i class="ri-inbox-line" aria-hidden="true"></i> Pegar atividades</a>
            @endif
            <a href="{{ \App\Services\Closure\ClosureServiceLocator::url('services.transfers') }}" class="btn btn-light btn-sm btn-dash"><i class="ri-exchange-line" aria-hidden="true"></i> Transferências</a>
            <button type="button" class="btn btn-light btn-sm btn-dash" wire:click="exportList" wire:loading.attr="disabled" wire:target="exportList"><i class="ri-file-excel-2-line" aria-hidden="true"></i> Exportar Excel</button>
        </div>
    </x-ops.hero>

    {{-- Cartões = filtros de status que ligam e desligam (clicar de novo desfaz). Contam no mesmo recorte, sem o filtro de status. --}}
    @if ($follow)
    <div class="ops-stats">
        <x-ops.stat wire:key="mstat-all" wire:click="toggleCard('all')" tone="teal" icon="ri-list-check" label="Total na fila" :value="number_format($stats['total'], 0, ',', '.')" :active="empty($filters['status']) && !$onlyPriority" title="Todas as suas Ordens do recorte atual, em qualquer status" />
        <x-ops.stat wire:key="fstat-partner" wire:click="toggleCard('WAITING_PARTNER')" tone="amber" icon="ri-team-line" label="Aguardando parceira" :value="number_format($stats['partner'], 0, ',', '.')" :active="$this->cardActive('WAITING_PARTNER')" title="Pendência com a parceira: volta a você quando ela responder" />
        <x-ops.stat wire:key="fstat-deposit" wire:click="toggleCard('DEPOSIT_BLOCKED')" tone="red" icon="ri-lock-line" label="Depósito bloqueado" :value="number_format($stats['deposit'], 0, ',', '.')" :active="$this->cardActive('DEPOSIT_BLOCKED')" title="Aguardando a liberação do depósito (inventário)" />
        <x-ops.stat wire:key="fstat-future" wire:click="toggleCard('WAITING')" tone="blue" icon="ri-calendar-event-line" label="Meta futura" :value="number_format($stats['future'], 0, ',', '.')" :active="$this->cardActive('WAITING')" title="Resolvidas, aguardando o mês da meta para finalizar" />
        <x-ops.stat wire:key="fstat-finished" wire:click="toggleCard('FINISHED')" tone="green" icon="ri-checkbox-circle-line" label="Finalizadas" :value="number_format($stats['finished'], 0, ',', '.')" :active="$this->cardActive('FINISHED')" title="Você finalizou: aguardando o SAP encerrar" />
        <x-ops.stat wire:key="fstat-reopened" wire:click="toggleCard('CLOSED')" tone="amber" icon="ri-refresh-line" label="Reaberta no SAP" :value="number_format($stats['reopened'], 0, ',', '.')" :active="$this->cardActive('CLOSED')" title="Estavam encerradas e voltaram a ficar abertas no SAP: aguardam novo despacho" />
        <x-ops.stat wire:key="fstat-ri" tone="teal" icon="ri-draft-line" label="Com o Desenho (RI)" :value="number_format($stats['ri'], 0, ',', '.')" disabled title="Obras com RI ativo: destravam quando o Desenho concluir" />
        <x-ops.stat wire:key="mstat-avg" tone="muted" icon="ri-time-line" label="Tempo médio no status" :value="$avgLabel" disabled title="Média de tempo da fila no status atual" />
    </div>
    @else
    <div class="ops-stats">
        <x-ops.stat wire:key="mstat-all" wire:click="toggleCard('all')" tone="teal" icon="ri-list-check" label="Total na fila" :value="number_format($stats['total'], 0, ',', '.')" :active="empty($filters['status']) && !$onlyPriority" title="Todas as suas Ordens do recorte atual, em qualquer status" />
        <x-ops.stat wire:key="mstat-assigned" wire:click="toggleCard('ASSIGNED')" tone="blue" icon="ri-user-follow-line" label="A iniciar" :value="number_format($stats['assigned'], 0, ',', '.')" :active="$this->cardActive('ASSIGNED')" title="Atribuídas a você, ainda não iniciadas" />
        <x-ops.stat wire:key="mstat-progress" wire:click="toggleCard('IN_PROGRESS')" tone="green" icon="ri-user-settings-line" label="Em andamento" :value="number_format($stats['progress'], 0, ',', '.')" :active="$this->cardActive('IN_PROGRESS')" title="Você está trabalhando nelas" />
        <x-ops.stat wire:key="mstat-paused" wire:click="toggleCard('PAUSED')" tone="amber" icon="ri-pause-circle-line" label="Pausadas" :value="number_format($stats['paused'], 0, ',', '.')" :active="$this->cardActive('PAUSED')" title="Você pausou: retome quando puder" />
        <x-ops.stat wire:key="mstat-priority" wire:click="toggleCard('priority')" tone="red" icon="ri-flag-2-line" label="Prioritárias" :value="number_format($stats['priority'], 0, ',', '.')" :active="$onlyPriority" title="Ordens marcadas como prioridade" />
        <x-ops.stat wire:key="mstat-avg" tone="muted" icon="ri-time-line" label="Tempo médio no status" :value="$avgLabel" disabled title="Média de tempo da fila no status atual" />
    </div>
    @endif

    @if ($activeDeposits->isNotEmpty())
        <div class="ops-notice ops-notice--danger" role="status">
            <i class="ri-lock-line" aria-hidden="true"></i>
            <div>
                <strong>{{ $activeDeposits->count() === 1 ? 'Depósito bloqueado' : 'Depósitos bloqueados' }}:</strong>
                @foreach ($activeDeposits as $deposit)
                    <x-ops.badge tone="red" mono :hint="$deposit->deposit_label . ' · desde ' . $deposit->created_at?->format('d/m/Y')">{{ $deposit->deposit_code }}</x-ops.badge>
                @endforeach
                As Ordens travadas aparecem como "Depósito Bloqueado"; as que só estão no alcance trazem um selo âmbar.
            </div>
        </div>
    @endif

    <section class="ops-toolbar" aria-label="Filtros da lista">
        <div class="ops-toolbar-row">
            <x-ops.search wire:model.debounce.500ms="search" id="closure-work-search" placeholder="Ordem ou nota" label="Buscar por Ordem ou Nota" />
            <x-closure.bulk-search :terms="$bulkTerms" :open="$bulkOpen" />
            <x-ops.segmented name="closureWorkType" label="Tipo de Ordem" wire:model="type" :options="['all' => 'Todos', 'P' => 'Passivo', 'M' => 'Meta atual', 'F' => 'Meta futura']" />

            <span class="ops-toolbar-spacer"></span>

            @if ($hasCriteria)
                <button type="button" class="ops-chips-clear" wire:click="clearFilters"><i class="ri-filter-off-line" aria-hidden="true"></i> Limpar tudo</button>
            @endif
        </div>

        <div class="ops-toolbar-row">
            <span class="ops-toolbar-label">Filtros</span>
            <x-ops.filters :payload="$this->listFilterPayload()" />
        </div>

        @if ($hasCriteria)
            <div class="ops-toolbar-row">
                <x-ops.chips :chips="array_merge($criteriaChips, $this->listFilterChips())" remove-action="removeAnyFilter" clear-action="clearFilters" />
            </div>
        @endif

        <x-closure.bulk-search-result :report="$this->bulkReport()" />
    </section>

    <section class="mp-panel" aria-label="{{ $follow ? 'Ordens em acompanhamento' : 'Minhas Ordens' }}">
        <div class="mp-panel-head">
            <div>
                <h2 class="mp-panel-title">{{ $follow ? 'Aguardando outras pessoas' : 'Minhas Ordens' }}</h2>
                <div class="mp-panel-sub">{{ number_format($states->total(), 0, ',', '.') }} {{ $states->total() === 1 ? 'Ordem' : 'Ordens' }}. O status SAP é informativo; as ações não o alteram.</div>
            </div>
            <div class="ops-panel-actions">
                <span class="mp-pill" wire:loading.delay wire:target="{{ $loadingTargets }}">Atualizando…</span>
                <button type="button" class="btn btn-outline-secondary btn-sm btn-dash" wire:click="toggleVisibleStates">{{ $allSelected ? 'Limpar página' : 'Selecionar página' }}</button>
                <button type="button" class="btn btn-outline-secondary btn-sm btn-dash" wire:click="selectAllFilteredStates" wire:loading.attr="disabled" wire:target="selectAllFilteredStates">Selecionar todas filtradas ({{ number_format(min($states->total(), \App\Http\Livewire\Closure\Work\Index::SELECT_ALL_LIMIT), 0, ',', '.') }})</button>
            </div>
        </div>

        <x-ops.skeleton wire:loading.delay.class.remove="d-none" wire:target="{{ $loadingTargets }}" class="d-none" :rows="min(10, max(4, $states->count()))" :cols="8" />

        @if ($states->isEmpty())
            <div class="mp-panel-body">
                <x-closure.empty-state
                    :title="$hasCriteria ? 'Nenhuma Ordem para os filtros escolhidos' : 'Você não tem Ordens em andamento'"
                    :text="$emptyText"
                    icon="ri-inbox-2-line" />
                @if ($hasCriteria)
                    <div class="text-center mt-2"><button type="button" class="btn btn-outline-primary btn-sm btn-dash" wire:click="clearFilters">Limpar filtros</button></div>
                @endif
            </div>
        @else
            <div class="ops-table-wrap" wire:loading.delay.class="d-none" wire:target="{{ $loadingTargets }}">
                <table class="table ops-table">
                    <colgroup>
                        <col style="width: 38px"><col style="width: 52px"><col style="width: 150px"><col style="width: 84px"><col style="width: 180px"><col style="width: 120px">
                        <col style="width: 92px"><col style="width: 64px">@if ($showHolder)<col style="width: 160px">@endif<col style="width: 210px"><col style="width: 110px"><col style="width: 150px">
                    </colgroup>
                    <thead>
                        <tr>
                            <th><input type="checkbox" class="form-check-input" @checked($allSelected) wire:click="toggleVisibleStates" aria-label="Selecionar ou limpar as Ordens da página"></th>
                            <th>Tipo</th>
                            <th class="is-left">Ordem / Nota</th>
                            <th title="Mês de competência da meta">Compet.</th>
                            <th class="is-left">Obra / Município</th>
                            <th class="is-right" title="Custo planejado (acima) e custo realizado (abaixo) da Ordem">Valor da obra</th>
                            <th>Status SAP</th>
                            <th title="Operações confirmadas / total">Ops.</th>
                            @if ($showHolder)<th class="is-left">Responsável</th>@endif
                            <th class="is-left">Status da atividade</th>
                            <th title="Há quanto tempo a Ordem está neste status">Tempo no status</th>
                            <th><span class="visually-hidden">Ações</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($states as $state)
                            @php
                                $selected = in_array($state->id, $selectedStateIds);
                                $order    = $state->Order;
                                $ops      = $order?->Operations ?? collect();
                                $conf     = $ops->filter(fn ($op) => $this->isConfirmedOperation($op->status))->count();
                                $holder   = $state->CurrentHolderUser?->name;
                                $letter   = $classifier::letter($state->meta_type);
                                $deposit  = $presenter::deposit($state->id, $state->operational_status, $state->cen_trab, $depositSignals, $centerSignals);
                                $ri       = $riByNote->get($order?->note_id);
                                $since    = $state->entered_current_status_at;
                                $signal   = \App\Services\Closure\ClosureSignal::forState($state);
                                // Executor: azul (atribuída) e amarelo (pilha) são da visão de controle; aqui só vale "SAP pendente" (vermelho) e "concluída" (verde).
                                $rowState = $signal && in_array($signal['key'], ['overdue', 'done'], true) ? ($signal['key'] === 'overdue' ? 'late' : 'done') : 'free';
                                $canStart  = $this->canRun($state, 'start');
                                $canFinish = $this->canRun($state, 'finish');
                                $finishWhy = $canFinish ? $this->finishBlockedReason($state) : null;
                            @endphp
                            <tr wire:key="closure-work-state-{{ $state->id }}" @class(['is-selected' => $selected, 'ops-row-' . $rowState => $rowState !== 'free', 'ops-row-priority' => (bool) $state->priority])
                                @if ($rowState !== 'free') data-signal="{{ $signal['key'] }}" title="{{ $signal['label'] }}: {{ $signal['hint'] }}" @endif>
                                <td><input type="checkbox" class="form-check-input" @checked($selected) wire:click="toggleState({{ $state->id }})" aria-label="Selecionar Ordem {{ $order?->ordem }}"></td>
                                <td>
                                    @if ($letter)
                                        <x-ops.badge :tone="$typeTone[$letter] ?? 'muted'" mono :label="$letter" :hint="$classifier::label($state->meta_type)" />
                                    @else
                                        <span class="ops-empty">—</span>
                                    @endif
                                </td>
                                <td class="is-left copy-text" data-value="{{ $order?->ordem }}">
                                    <div class="ops-note">{{ $order?->ordem ?? '—' }} @if ($state->priority)<i class="ri-flag-2-fill ops-priority-flag" title="Prioritária" aria-label="Prioritária"></i>@endif</div>
                                    <span class="ops-sub">Nota {{ $order?->Note?->note ?? '—' }}</span>
                                </td>
                                <td><span class="ops-sub text-body">{{ $classifier::competenceLabel($state->competence ?? null) }}</span></td>
                                <td class="is-left">
                                    <div class="text-truncate fw-semibold" title="{{ $order?->Note?->lexp }}">{{ $order?->Note?->lexp ?: '—' }}</div>
                                    <span class="ops-sub text-truncate" title="{{ $order?->Note?->pep }}">{{ $order?->Note?->pep ?: '—' }}</span>
                                </td>
                                <x-closure.order-cost :planned="$order?->custPlanejado" :actual="$order?->custRealizado" />
                                <td><x-ops.badge tone="muted" mono :label="$this->sapHeadline($order?->statusSist)" :hint="'Status do sistema (SAP): ' . ($order?->statusSist ?: '—')" /></td>
                                <td><button type="button" class="btn btn-outline-secondary btn-sm ops-action" wire:click="showOperations({{ $state->id }})" title="Ver as operações da Ordem" aria-label="Ver operações da Ordem {{ $order?->ordem }}">{{ $conf }}/{{ $ops->count() }}</button></td>
                                @if ($showHolder)
                                    <td class="is-left">
                                        <div class="ops-person">
                                            <span class="ops-avatar" aria-hidden="true">{{ $holder ? $initials($holder) : '—' }}</span>
                                            <div class="min-w-0">
                                                <div class="text-truncate fw-semibold" title="{{ $holder ?? 'Sem responsável' }}">{{ $holder ?? 'Sem responsável' }}</div>
                                                <span class="ops-sub text-truncate">{{ $state->CurrentHolderCompany?->name ?? '—' }}</span>
                                            </div>
                                        </div>
                                    </td>
                                @endif
                                <td class="is-left">
                                    <div class="ops-cell-stack">
                                        @if ($state->operational_status === 'CLOSED')
                                            <x-ops.badge tone="amber" label="Reaberta no SAP" hint="Obra que estava encerrada e voltou a ficar aberta no SAP." />
                                            <span class="ops-sub">Estava encerrada; aguarda novo despacho.</span>
                                        @else
                                        <x-ops.badge :tone="\App\Enum\ClosureOperationalStatus::toneFor($state->operational_status)" :label="\App\Enum\ClosureOperationalStatus::labelFor($state->operational_status)" :hint="$presenter::statusHint($state->operational_status)" />
                                        <span class="ops-sub">{{ $presenter::statusHint($state->operational_status) }}</span>
                                        @endif
                                        @if ($deposit)
                                            <x-ops.badge sm :tone="$deposit['tone']" :icon="$deposit['locked'] ? 'ri-lock-line' : 'ri-error-warning-line'" :label="$deposit['label']" :hint="$deposit['hint']" />
                                        @endif
                                        @if ($ri)
                                            <x-ops.badge sm tone="red" icon="ri-lock-line" label="RI com o Desenho" :hint="'Retorno interno enviado em ' . $ri->sent_at?->format('d/m/Y') . '. As Ordens da obra ficam travadas até o Desenho concluir.'" />
                                        @endif
                                    </div>
                                </td>
                                <td><span class="ops-sub text-body" title="{{ $this->holderLabel($state) }}{{ $since ? ' · desde ' . $since->format('d/m/Y H:i') : '' }}">{{ $this->elapsedLabel($state) }}</span></td>
                                <td>
                                    <div class="ops-buttons">
                                        {{-- Iniciar vive só no menu ⋮ (a ação principal aparece quando a Ordem já foi iniciada). --}}
                                        @if ($canFinish && !$canStart)
                                            <span @if ($finishWhy) title="{{ $finishWhy }}" tabindex="0" @endif><button type="button" class="btn btn-primary btn-sm ops-action" wire:click="runAction({{ $state->id }}, 'finish')" @disabled($finishWhy)><i class="ri-checkbox-circle-line" aria-hidden="true"></i> Finalizar</button></span>
                                        @endif
                                        @include('livewire.closure.dispatch._control-actions')
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        <x-ops.pager :paginator="$states" :per-page="$perPage" :sizes="\App\Http\Livewire\Closure\Work\Index::PER_PAGE_OPTIONS" />
    </section>

    @include('livewire.closure.dispatch._selection-bar', ['withTransfer' => true])
    @include('livewire.closure.dispatch._control-batch-modal')
    @include('livewire.closure.dispatch._control-modals')

    @livewire('closure.internal-returns.modal', [], key('closure-ri-modal'))
    @livewire('closure.transfers.send-modal', [], key('closure-transfer-modal'))
</div>
