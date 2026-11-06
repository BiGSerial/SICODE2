@php
    use App\Custom\Notestatus;
    use App\Support\Ops\Tone;

    $statusCodes = $service->Status->count()
        ? $service->Status->where('exclusion', false)->unique('value')->pluck('value')->implode(' · ')
        : null;
    $hasCriteria = $this->hasListFilters() || filled($search) || filled($note_type) || $statusFilter !== '';
@endphp

{{-- Serviços → Desenho: a pilha de quem está logado. Padrão em docs/PADRAO_LISTAS_OPERACIONAIS.md. Hero → situação → ferramentas → painel com a tabela. --}}
<div class="closure-dash my-panel ops-list">

    <x-ops.hero
        :eyebrow="'Serviços · ' . $service->service"
        :title="'Minhas atividades de ' . mb_strtolower($service->service)"
        subtitle="Acompanhamento de produção. Ordenação: prioridade, D5, tipo, prazo real e identificação."
        :updated-at="null">
    </x-ops.hero>

    {{-- Situação da pilha: os cartões de status são filtros. --}}
    @if (count($statusFilterOptions))
        <section class="ops-stats" aria-label="Status da produção">
            @foreach ($statusFilterOptions as $statusOption)
                <x-ops.stat :tone="$statusOption['tone']" :label="$statusOption['label']" :value="number_format($statusOption['count'], 0, ',', '.')"
                    :active="(string) $statusFilter === (string) $statusOption['value']" wire:click.prevent="setStatusFilter('{{ $statusOption['value'] }}')"
                    wire:key="desenho-status-{{ $statusOption['value'] === '' ? 'todos' : $statusOption['value'] }}"
                    :title="'Filtrar por ' . $statusOption['label']" />
            @endforeach
        </section>
    @endif

    <section class="ops-toolbar" aria-label="Filtros da lista">
        <div class="ops-toolbar-row">
            <x-ops.search wire:model.debounce.500ms="search" placeholder="Buscar nota, pedido ou rubrica" label="Buscar nota, pedido ou rubrica" />

            <x-ops.segmented name="servicoDesenhoNoteType" label="Tipo de nota" wire:model="note_type" :options="['1' => 'Nota', '2' => 'OV', '' => 'Ambos']" />

            <x-ops.toggle :label="$showProjectReviewInProgress ? 'Ocultar em análise' : 'Mostrar em análise'" :icon="$showProjectReviewInProgress ? 'ri-eye-off-line' : 'ri-eye-line'"
                :on="$showProjectReviewInProgress" :count="$this->projectReviewInProgressCount" wire:click.prevent="toggleProjectReviewInProgress"
                title="Mostra ou oculta as atividades que estão em análise de projeto" />

            <span class="ops-toolbar-spacer"></span>

            @if ($hasCriteria)
                <button type="button" class="ops-chips-clear" wire:click="clearAllCriteria"><i class="ri-filter-off-line" aria-hidden="true"></i> Limpar tudo</button>
            @endif
        </div>

        <div class="ops-toolbar-row">
            <span class="ops-toolbar-label">Filtros da nota</span>
            <x-ops.filters :payload="$this->listFilterPayload()" />
        </div>

        @if ($this->hasListFilters())
            <div class="ops-toolbar-row">
                <x-ops.chips :chips="$this->listFilterChips()" />
            </div>
        @endif
    </section>

    <div class="mp-presets mb-3" role="tablist" aria-label="Acompanhamento">
        <button type="button" role="tab" class="mp-preset {{ $tab === 'producao' ? 'is-active' : '' }}" wire:click="setTab('producao')" aria-selected="{{ $tab === 'producao' ? 'true' : 'false' }}">Produção</button>
        <button type="button" role="tab" class="mp-preset {{ $tab === 'transferencias' ? 'is-active' : '' }}" wire:click="setTab('transferencias')" aria-selected="{{ $tab === 'transferencias' ? 'true' : 'false' }}">
            Transferências @livewire('components.transprod.count', ['service_id' => $service->uuid], key('transfer_count'))
        </button>
    </div>

    @if ($tab === 'transferencias')
        @livewire('components.transprod.translist', ['service' => $service->id], key('acc-translist-' . $service->id))
    @else
            <section class="mp-panel" aria-label="Acompanhamento de {{ $service->service }}">
                <div class="mp-panel-head">
                    <div>
                        <h2 class="mp-panel-title">Acompanhamento</h2>
                        <div class="mp-panel-sub">
                            {{ number_format($lists->total(), 0, ',', '.') }} {{ $lists->total() === 1 ? 'registro' : 'registros' }}@if ($statusCodes) · status {{ $statusCodes }}@endif
                        </div>
                    </div>
                    <span class="mp-pill" wire:loading.delay>Atualizando…</span>
                </div>

                <x-ops.skeleton wire:loading.delay.class.remove="d-none" class="d-none" :rows="min(10, max(4, $rows->count()))" :cols="8" />

                @if ($rows->isEmpty())
                    <div class="mp-panel-body">
                        <x-closure.empty-state
                            :title="$hasCriteria ? 'Nenhuma atividade para os filtros escolhidos' : 'Nenhuma atividade atribuída'"
                            :text="$hasCriteria ? 'Ajuste ou limpe os filtros para ver mais atividades.' : 'Quando houver tarefas de ' . mb_strtolower($service->service) . ' para você, elas aparecem aqui.'"
                            icon="ri-inbox-line" />
                        @if ($hasCriteria)
                            <div class="text-center mt-2"><button type="button" class="btn btn-outline-primary btn-sm btn-dash" wire:click="clearAllCriteria">Limpar filtros</button></div>
                        @endif
                    </div>
                @else
                    <div class="ops-table-wrap" wire:loading.delay.class="d-none">
                        <table class="table ops-table">
                            <colgroup>
                                @foreach ([138, 70, 52, 100, 130, 190, 70, 96, 72, 104, 76, 150, 190] as $w)
                                    <col style="width: {{ $w }}px">
                                @endforeach
                            </colgroup>
                            <thead>
                                <tr>
                                    <th>Nota</th>
                                    <th>Arquivos</th>
                                    <th>DOE</th>
                                    <th class="is-left">Rubrica</th>
                                    <th class="is-left">Localização</th>
                                    <th class="is-left">Descrição</th>
                                    <th>Postes L</th>
                                    <th>Status da nota</th>
                                    <th>DStatus</th>
                                    <th>Tempos</th>
                                    <th>Prazo real</th>
                                    <th>Status</th>
                                    <th><span class="visually-hidden">Ações</span></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($rows as $row)
                                    <tr wire:key="mine-{{ $row['id'] }}" @class(['ops-row-priority' => $row['priority']])>
                                        <td>
                                            @if ($row['reviewTracked'] && in_array($row['status'], [30, 31, 32], true))
                                                <div class="ops-cell-stack is-center">
                                                    <x-ops.badge class="ops-note-badge ops-clickable" :tone="$row['reviewBadge']['tone']" :hint="$row['reviewBadge']['hint']"
                                                        wire:click="openProjectReviewReadonly({{ $row['id'] }}, {{ $row['noteId'] }})">{{ $row['note'] }}</x-ops.badge>
                                                    @include('livewire.services.desenho.partials.note-extras', ['row' => $row])
                                                </div>
                                            @elseif ($row['ri'])
                                                <x-ops.note :note="$row['note']" kind="ri" tone="blue" hint="Retorno Interno – clique para ver"
                                                    wire:click="$emitTo('services.desenho.actions.responserinfo', 'getInfoResponse', {{ $row['id'] }})">
                                                    @include('livewire.services.desenho.partials.note-extras', ['row' => $row])
                                                </x-ops.note>
                                            @else
                                                <x-ops.note :note="$row['note']">
                                                    @include('livewire.services.desenho.partials.note-extras', ['row' => $row])
                                                </x-ops.note>
                                            @endif
                                        </td>

                                        <td><x-files.select-download-list :files="$row['files']" /></td>

                                        <td>
                                            @if ($row['doe'])
                                                <x-ops.badge sm tone="green" icon="ri-checkbox-circle-line" label="DOE" hint="Nota com DOE" />
                                            @else
                                                <span class="ops-empty">—</span>
                                            @endif
                                        </td>

                                        <td class="is-left"><span class="d-block text-truncate" title="{{ $row['rubrica'] }}">{{ $row['rubrica'] ?: '—' }}</span></td>

                                        <td class="is-left">
                                            <span class="fw-semibold d-block text-truncate" title="{{ $row['city'] }}">{{ $row['city'] ?: '—' }}</span>
                                            <span class="ops-sub text-truncate">{{ $row['zone'] ?: 'Sem zona' }}</span>
                                        </td>

                                        <td class="is-left"><span class="d-block text-truncate" title="{{ $row['material'] }}">{{ $row['material'] ?: '—' }}</span></td>

                                        <td>{{ $row['postes'] ?? '—' }}</td>

                                        <td>
                                            <div class="ops-cell-stack is-center" title="Status atual da nota e o status recebido no despacho">
                                                <span class="fw-semibold">{{ $row['noteStatus'] ?: '—' }}</span>
                                                <span class="ops-sub">{{ $row['receivedStatus'] ?: '—' }}</span>
                                            </div>
                                        </td>

                                        <td><x-ops.badge :tone="$row['daysTone']" :label="$row['days'] ?? '—'" :hint="$row['daysHint']" /></td>

                                        <td>
                                            <div class="ops-cell-stack is-center">
                                                <span class="ops-sub">Despachado</span>
                                                <span>{{ $row['dispatchDays'] !== null ? $row['dispatchDays'] . ' d' : '—' }}</span>
                                                <span class="ops-sub">Atribuído</span>
                                                <span>{{ $row['attDays'] !== null ? $row['attDays'] . ' d' : '—' }}</span>
                                            </div>
                                        </td>

                                        <td>
                                            @if ($row['deadline']['value'] !== null)
                                                <x-ops.badge :tone="$row['deadline']['tone']" :label="$row['deadline']['value']" :hint="$row['deadline']['hint']" />
                                            @else
                                                <span class="ops-empty">—</span>
                                            @endif
                                        </td>

                                        <td>
                                            <div class="ops-cell-stack is-center">
                                                <x-ops.badge class="ops-clickable" :tone="Tone::forProductionStatus($row['status'])" :label="Notestatus::status($row['status'])->status" hint="Clique para ver o histórico de status"
                                                    wire:click="$emitTo('components.status.show-status', 'showStatus', {{ $row['id'] }}, {{ $row['status'] }})" />
                                                @if ($row['quality'])
                                                    <x-ops.badge sm tone="teal" icon="ri-shield-check-line" :label="'Qualidade · ' . $row['quality']['phase'] . ' · rodada ' . $row['quality']['round']"
                                                        hint="Atividade originada pela Qualidade: use o formulário da Qualidade para enviar ao N1." />
                                                @endif
                                            </div>
                                        </td>

                                        <td>
                                            @if (!$row['block'] && (!$row['completed'] || $row['reviewTracked']) && !($row['reviewTracked'] && $row['status'] === 30))
                                                <div class="d-inline-flex flex-wrap gap-1 justify-content-center">
                                                    @if ($row['quality'])
                                                        <button type="button" class="btn btn-outline-secondary btn-sm ops-action" title="Ver detalhes (somente leitura)" wire:click.prevent="getQualityDetails({{ $row['id'] }})"><i class="ri-eye-line" aria-hidden="true"></i> Detalhes</button>
                                                    @endif
                                                    <button type="button" class="btn btn-primary btn-sm ops-action" title="Iniciar a atividade" wire:click.prevent="getAnalise({{ $row['id'] }}, {{ $row['noteId'] }})"><i class="ri-play-circle-line" aria-hidden="true"></i> Iniciar</button>
                                                    @if (!$row['reviewTracked'] && !$row['quality'])
                                                        <button type="button" class="btn btn-outline-primary btn-sm ops-action" title="Transferir para outro usuário" wire:click.prevent="goTransferProd({{ $row['id'] }})"><i class="ri-exchange-fill" aria-hidden="true"></i> Transferir</button>
                                                    @endif
                                                </div>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif

                <x-ops.pager :paginator="$lists" :per-page="$perPage" />
            </section>
    @endif

    {{-- MODAIS --}}
    <div wire:ignore.self class="modal fade ops-modal" id="quality_closing_form" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="qualityClosingLabel" aria-hidden="true">
        <div class="modal-dialog modal-fullscreen modal-dialog-scrollable">
            <div class="modal-content h-100">
                <div class="modal-header">
                    <div>
                        <span class="modal-eyebrow">Qualidade</span>
                        <h5 class="modal-title" id="qualityClosingLabel">Atividade de qualidade</h5>
                    </div>
                    <button type="button" class="btn-close btn-close-white ms-auto" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body">
                    @livewire('services.desenho.forms.quality-closing', key('quality-closing-form'))
                </div>
            </div>
        </div>
    </div>

    <div wire:ignore.self class="modal fade ops-modal" id="analise_form" data-bs-backdrop="static" data-bs-keyboard="false"
        tabindex="-1" aria-labelledby="analiseFormLabel" aria-hidden="true">
        <div class="modal-dialog modal-fullscreen modal-dialog-scrollable">
            <div class="modal-content h-100">
                <div class="modal-header">
                    <div>
                        <span class="modal-eyebrow">Encerramento</span>
                        <h5 class="modal-title" id="analiseFormLabel">{{ mb_strtoupper($service->service) }}</h5>
                    </div>
                </div>
                <div class="modal-body">
                    @livewire('services.desenho.forms.analise', ['modalContext' => 'finish'], key('desenho-form-finish'))
                </div>
            </div>
        </div>
    </div>

    <div wire:ignore.self class="modal fade ops-modal" id="analise_review_form" data-bs-backdrop="static" data-bs-keyboard="false"
        tabindex="-1" aria-labelledby="reviewStaticBackdropLabel" aria-hidden="true">
        <div class="modal-dialog modal-fullscreen modal-dialog-scrollable">
            <div class="modal-content h-100">
                <div class="modal-header">
                    <div>
                        <span class="modal-eyebrow">Análise de projeto</span>
                        <h5 class="modal-title" id="reviewStaticBackdropLabel">{{ mb_strtoupper($service->service) }}</h5>
                    </div>
                    <button type="button" class="btn-close btn-close-white ms-auto" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body">
                    @livewire('services.desenho.forms.analise', ['modalContext' => 'review'], key('desenho-form-review'))
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary btn-dash" data-bs-dismiss="modal">Fechar</button>
                    @if ($reviewCanFinish)
                        <button type="button" class="btn btn-primary btn-dash" id="go-finish-from-review-btn">
                            <i class="ri-arrow-right-line" aria-hidden="true"></i> Ir para encerramento
                        </button>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div wire:ignore.self class="modal fade ops-modal" id="pause_note" data-bs-backdrop="static" data-bs-keyboard="false"
        tabindex="-1" aria-labelledby="pauseNoteLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content h-100">
                <div class="modal-header">
                    <div>
                        <span class="modal-eyebrow">Pausar atividade</span>
                        <h5 class="modal-title" id="pauseNoteLabel">Parar {{ mb_strtoupper($service->service) }}</h5>
                    </div>
                    <button type="button" class="btn-close btn-close-white ms-auto" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body">
                    @livewire('components.pausenote.pausenote')
                </div>
            </div>
        </div>
    </div>

    {{-- LIVEWIRE COMPONENTS --}}
    @livewire('components.transprod.transprod', key('Transfer_production'))
    @livewire('services.desenho.actions.responserinfo', key('responser_info_return'))
    @livewire('components.status.show-status', key('show_status_note'))

    <div wire:init="checkOpen"></div>

</div>


@push('script')
    <script>
        window.__skipAnaliseClean = false;

        window.addEventListener("hideModal2", function(e) {
            const myModal = new bootstrap.Modal(document.getElementById(e.detail.id))
            myModal.hide();
        })

        function bindAnaliseModalClean(modalId) {
            const modalEl = document.getElementById(modalId);
            if (!modalEl) return;
            modalEl.addEventListener('hidden.bs.modal', function() {
                if (window.__skipAnaliseClean) {
                    window.__skipAnaliseClean = false;
                    return;
                }

                Livewire.emitTo('services.desenho.forms.analise', 'analise_clean');
                cleanupModalArtifacts();
                clearProjectReviewQueryParams();
            });
        }

        function cleanupModalArtifacts() {
            const hasVisibleModal = !!document.querySelector('.modal.show');
            if (hasVisibleModal) return;

            document.body.classList.remove('modal-open');
            document.body.style.removeProperty('overflow');
            document.body.style.removeProperty('padding-right');
            document.querySelectorAll('.modal-backdrop').forEach(el => el.remove());
        }

        function clearProjectReviewQueryParams() {
            const params = new URLSearchParams(window.location.search);
            let changed = false;

            ['open_project_review', 'production', 'note', 'focus'].forEach(function(key) {
                if (params.has(key)) {
                    params.delete(key);
                    changed = true;
                }
            });

            if (!changed) return;

            const nextQuery = params.toString();
            const nextUrl = `${window.location.pathname}${nextQuery ? '?' + nextQuery : ''}${window.location.hash || ''}`;
            window.history.replaceState({}, document.title, nextUrl);
        }

        bindAnaliseModalClean('analise_form');
        bindAnaliseModalClean('analise_review_form');

        document.addEventListener('livewire:load', function() {
            // Reforço de retomada após refresh completo da página.
            setTimeout(function() {
                Livewire.emitTo('services.desenho.main', 'force_check_open');
            }, 350);

            const params = new URLSearchParams(window.location.search);
            const shouldOpenReview = params.get('open_project_review');
            const productionId = parseInt(params.get('production') || '', 10);
            const noteId = parseInt(params.get('note') || '', 10);

            if (shouldOpenReview && Number.isInteger(productionId) && productionId > 0) {
                const safeNoteId = Number.isInteger(noteId) && noteId > 0 ? noteId : 0;
                const openFromNotification = function() {
                    Livewire.emitTo('services.desenho.main', 'openProjectReviewFromNotification', productionId, safeNoteId);
                };

                openFromNotification();
                setTimeout(openFromNotification, 350);
            }

            clearProjectReviewQueryParams();
        });

        window.addEventListener('openProjectReviewModalFromServer', function(e) {
            const payload = e.detail?.payload;
            if (!payload || !payload.productionId) return;

            const openReviewModal = function() {
                Livewire.emitTo('services.desenho.forms.analise', 'open_analise_draw', payload);
                const modalEl = document.getElementById('analise_review_form');
                if (!modalEl) return;
                const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
                modal.show();
            };

            openReviewModal();
            setTimeout(openReviewModal, 250);
            setTimeout(openReviewModal, 700);
        });

        document.addEventListener('click', function(e) {
            const trigger = e.target.closest('#go-finish-from-review-btn');
            if (!trigger) return;

            const reviewModalEl = document.getElementById('analise_review_form');
            const finishModalEl = document.getElementById('analise_form');
            if (!reviewModalEl || !finishModalEl) return;

            // Fecha o modal atual e reabre no modo de encerramento.
            window.__skipAnaliseClean = true;

            const onHidden = function() {
                reviewModalEl.removeEventListener('hidden.bs.modal', onHidden);

                Livewire.emitTo('services.desenho.forms.analise', 'goToFinishFlow');

                setTimeout(function() {
                    const reopen = bootstrap.Modal.getOrCreateInstance(finishModalEl);
                    reopen.show();
                }, 180);
            };

            reviewModalEl.addEventListener('hidden.bs.modal', onHidden);
            const modal = bootstrap.Modal.getOrCreateInstance(reviewModalEl);
            modal.hide();
        });

        window.addEventListener('hideModal', function() {
            cleanupModalArtifacts();
            clearProjectReviewQueryParams();
        });
    </script>
@endpush
