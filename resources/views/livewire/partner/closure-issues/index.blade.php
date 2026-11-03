@php
    use App\Services\Closure\ClosureEventLabels;

    $statusLabels = [
        'OPEN'       => 'Aberta',
        'REQUESTED'  => 'Aguardando resposta',
        'RETURNED'   => 'Devolvida p/ correção',
        'RESPONDED'  => 'Respondida',
        'VALIDATING' => 'Em validação',
        'RESOLVED'   => 'Resolvida',
    ];
    $tabLabels = ['REQUESTED' => 'Aguardando resposta', 'RETURNED' => 'Devolvidas', 'RESPONDED' => 'Respondidas', 'VALIDATING' => 'Em validação', 'RESOLVED' => 'Resolvidas'];
    $tabs      = ['' => 'Todas (' . number_format((int) $counts->sum(), 0, ',', '.') . ')'];
    foreach ($tabLabels as $key => $label) {
        $tabs[$key] = $label . ' (' . number_format((int) ($counts[$key] ?? 0), 0, ',', '.') . ')';
    }
    $waiting = (int) ($counts['REQUESTED'] ?? 0);
    $returnedCount = (int) ($counts['RETURNED'] ?? 0);
    $inReview = (int) ($counts['RESPONDED'] ?? 0) + (int) ($counts['VALIDATING'] ?? 0);
@endphp

{{-- PENDÊNCIAS DE ENCERRAMENTO DA PARCEIRA (lista de controle + padrão do Encerramento: docs/encerramento/PADRAO_UI_ENCERRAMENTO.md). --}}
<div class="closure-dash my-panel ops-list ops-control">

    <x-ops.hero class="ops-hero--control" eyebrow="Portal da parceira · Encerramento" title="Pendências de Encerramento"
        subtitle="Demandas destinadas à sua empresa: responda as pendências e acompanhe os depósitos bloqueados que afetam as suas obras.">
        <div class="d-flex flex-wrap gap-2 justify-content-end align-items-center">
            <a href="{{ route('partner.closure.history') }}" class="btn btn-light btn-sm btn-dash"><i class="ri-history-line" aria-hidden="true"></i> Meu histórico</a>
            <button type="button" class="btn btn-light btn-sm btn-dash" wire:click="exportToExcel" wire:loading.attr="disabled" wire:target="exportToExcel"><i class="ri-file-excel-2-line" aria-hidden="true"></i> Exportar Excel</button>
        </div>
    </x-ops.hero>

    @if (session()->has('success'))
        <div class="alert alert-success d-flex align-items-center gap-2"><i class="ri-checkbox-circle-line" aria-hidden="true"></i> {{ session('success') }}</div>
    @endif

    <section class="ops-stats" aria-label="Resumo das pendências">
        <x-ops.stat tone="amber" label="Aguardando resposta" :value="number_format($waiting, 0, ',', '.')" icon="ri-time-line" :active="$status === 'REQUESTED'"
            wire:click.prevent="setStatus('REQUESTED')" title="Pendências que dependem de você" />
        <x-ops.stat tone="red" label="Devolvidas p/ correção" :value="number_format($returnedCount, 0, ',', '.')" icon="ri-arrow-go-back-line" :active="$status === 'RETURNED'"
            wire:click.prevent="setStatus('RETURNED')" title="Revisar e reenviar" />
        <x-ops.stat tone="blue" label="Respondidas / em validação" :value="number_format($inReview, 0, ',', '.')" icon="ri-hourglass-line" :active="$status === 'RESPONDED'"
            wire:click.prevent="setStatus('RESPONDED')" title="Com a equipe de Encerramento" />
        <x-ops.stat tone="teal" label="Depósitos bloqueados" :value="number_format($signals->count(), 0, ',', '.')" icon="ri-lock-line"
            title="{{ $signals->count() ? $signals->sum('affected_count') . ' Ordem(ns) afetadas' : 'Nenhum depósito bloqueado' }}" />
    </section>

    @if ($signals->isNotEmpty())
        <section class="mp-panel" aria-label="Depósitos bloqueados ativos">
            <div class="mp-panel-head">
                <div>
                    <h2 class="mp-panel-title"><i class="ri-lock-line" aria-hidden="true"></i> Depósitos bloqueados ativos</h2>
                    <div class="mp-panel-sub">{{ $signals->count() }} {{ $signals->count() === 1 ? 'depósito' : 'depósitos' }} com Ordens bloqueadas.</div>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table mp-table align-middle mb-0">
                    <colgroup>
                        <col style="width: 20%">
                        <col style="width: 18%">
                        <col style="width: 130px">
                        <col style="width: 20%">
                        <col>
                        <col style="width: 170px">
                    </colgroup>
                    <thead>
                        <tr>
                            <th>Depósito</th>
                            <th>Centros de trabalho</th>
                            <th>Ordens</th>
                            <th>Desde</th>
                            <th>Motivo</th>
                            <th><span class="visually-hidden">Ações</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($signals as $signal)
                            <tr wire:key="signal-{{ $signal->id }}">
                                <td>
                                    <div class="ops-person-name">Depósito {{ $signal->deposit_code }}</div>
                                    @if ($signal->deposit_label)
                                        <div class="ops-person-sub" title="{{ $signal->deposit_label }}">{{ $signal->deposit_label }}</div>
                                    @endif
                                </td>
                                <td>
                                    @forelse ($signal->work_centers ?? [] as $code)
                                        <x-ops.badge sm tone="muted" mono :label="$code" />
                                    @empty
                                        <span class="ops-empty">—</span>
                                    @endforelse
                                </td>
                                <td><x-ops.badge tone="red" :label="$signal->affected_count . ' bloqueada(s)'" hint="Ordens bloqueadas por este depósito" /></td>
                                <td>
                                    <x-ops.date :date="$signal->created_at" :time="true" />
                                    <div class="ops-person-sub">{{ $signal->origin === 'CALENDAR' ? 'inventário da gestão de ativos' : 'sinalização manual' }} · {{ $signal->Author?->name ?? '—' }}</div>
                                </td>
                                <td>{{ $signal->reason ?: '—' }}</td>
                                <td class="text-end">
                                    @if ($canRelease)
                                        <button type="button" class="btn btn-primary btn-sm ops-action" wire:click="startRelease({{ $signal->id }})"><i class="ri-lock-unlock-line" aria-hidden="true"></i> Liberar depósito</button>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    @endif

    <section class="ops-toolbar" aria-label="Filtros das pendências">
        <div class="ops-toolbar-row">
            <x-ops.search wire:model.debounce.500ms="search" placeholder="Buscar por nota ou ordem…" label="Buscar pendência" />

            <x-ops.segmented name="partnerIssuesTab" label="Situação da pendência" wire:model="status" :options="$tabs" />

            <span class="ops-toolbar-spacer"></span>

            @if ($hasCriteria)
                <button type="button" class="ops-chips-clear" wire:click="clearFilters"><i class="ri-filter-off-line" aria-hidden="true"></i> Limpar filtros</button>
            @endif
        </div>

        <div class="ops-toolbar-row">
            <span class="ops-toolbar-label">Filtros</span>
            <x-ops.filters :payload="$this->listFilterPayload()" />
        </div>

        @if ($this->hasListFilters())
            <div class="ops-toolbar-row">
                <x-ops.chips :chips="$this->listFilterChips()" />
            </div>
        @endif
    </section>

    <section class="mp-panel" aria-label="Pendências de Encerramento">
        <div class="mp-panel-head">
            <div>
                <h2 class="mp-panel-title">Pendências de Encerramento</h2>
                <div class="mp-panel-sub">{{ number_format($issues->total(), 0, ',', '.') }} {{ $issues->total() === 1 ? 'registro' : 'registros' }} · apenas demandas destinadas à sua empresa.</div>
            </div>
        </div>

        <x-ops.skeleton wire:loading.delay.class.remove="d-none" class="d-none" :rows="8" :cols="6" />

        <div wire:loading.delay.class="d-none">
            @if ($issues->isEmpty())
                <x-closure.empty-state icon="ri-checkbox-circle-line"
                    :title="$hasCriteria ? 'Nenhuma pendência com esses filtros' : 'Nenhuma pendência encontrada'"
                    :text="$hasCriteria ? 'Ajuste ou limpe os filtros para ver mais pendências.' : 'Quando o Encerramento pedir algo à sua empresa, a pendência aparece aqui.'" />
            @else
                <div class="table-responsive">
                    <table class="table mp-table align-middle mb-0">
                        <colgroup>
                            <col style="width: 16%">
                            <col style="width: 22%">
                            <col style="width: 180px">
                            <col style="width: 150px">
                            <col style="width: 150px">
                            <col>
                        </colgroup>
                        <thead>
                            <tr>
                                <th>Ordem / Nota</th>
                                <th>Classificação</th>
                                <th>Situação</th>
                                <th>Solicitada em</th>
                                <th>Último retorno</th>
                                <th><span class="visually-hidden">Ações</span></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($issues as $issue)
                                @php
                                    $waitingReply = in_array($issue->status, ['REQUESTED', 'RETURNED'], true);
                                    $canReply     = $waitingReply && $canRespond;
                                    $since        = $issue->requested_at ?? $issue->opened_at;
                                    $sinceDays    = $since ? (int) $since->copy()->startOfDay()->diffInDays(now()->startOfDay()) : null;
                                @endphp
                                <tr wire:key="closure-issue-{{ $issue->id }}">
                                    <td><x-ops.note :note="$issue->Order?->ordem ?? '—'" :sub="'Nota ' . ($issue->Order?->Note?->note ?? '—')" /></td>
                                    <td>
                                        <div class="ops-person-name">{{ $issue->Category?->name ?? '—' }}</div>
                                        <div class="ops-person-sub">{{ $issue->Subcategory?->name ?? '—' }}</div>
                                    </td>
                                    <td>
                                        <x-ops.badge :tone="ClosureEventLabels::issueStatusTone($issue->status)" :label="$statusLabels[$issue->status] ?? $issue->status" />
                                        <div class="ops-person-sub">Ciclo {{ $issue->cycle }}</div>
                                    </td>
                                    <td>
                                        <x-ops.date :date="$since" :time="!$waitingReply || $sinceDays === null"
                                            :sub="$waitingReply && $sinceDays !== null ? ($sinceDays === 0 ? 'hoje' : 'há ' . $sinceDays . ' ' . ($sinceDays === 1 ? 'dia' : 'dias')) : null"
                                            hint="Dias corridos desde a solicitação (informativo)" />
                                    </td>
                                    <td><x-ops.date :date="$issue->last_event_at" :time="true" /></td>
                                    <td class="text-end">
                                        <div class="d-inline-flex align-items-center gap-1">
                                            <a class="btn {{ $canReply ? 'btn-primary' : 'btn-outline-secondary' }} btn-sm ops-action" href="{{ route('partner.closure.issue.show', $issue->id) }}">
                                                <i class="{{ $canReply ? 'ri-reply-line' : 'ri-eye-line' }}" aria-hidden="true"></i> {{ $canReply ? 'Ver pedido e responder' : 'Ver pedido' }}
                                            </a>
                                            @if ($issue->Order?->note_id && $canRespond)
                                                <a class="btn btn-outline-secondary btn-sm ops-action" href="{{ route('partner.closure.work.show', $issue->Order->note_id) }}"><i class="ri-building-line" aria-hidden="true"></i> Abrir obra</a>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <x-ops.pager :paginator="$issues" />
            @endif
        </div>
    </section>

    {{-- Liberar depósito --}}
    @if ($releasingSignalId && ($releasing = $signals->firstWhere('id', $releasingSignalId)))
        <div class="modal fade show d-block ops-modal" tabindex="-1" role="dialog" aria-modal="true" aria-labelledby="releaseDepositTitle" x-data @keydown.escape.window="$wire.cancelRelease()">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content closure-dash ops-list">
                    <div class="modal-header">
                        <div>
                            <span class="modal-eyebrow">Liberar depósito</span>
                            <h5 class="modal-title" id="releaseDepositTitle">Depósito {{ $releasing->deposit_code }}@if ($releasing->deposit_label) · {{ $releasing->deposit_label }}@endif</h5>
                        </div>
                        <button type="button" class="btn-close btn-close-white ms-auto" wire:click="cancelRelease" aria-label="Fechar"></button>
                    </div>
                    <div class="modal-body">
                        <p class="ops-modal-note">Confirme que o depósito foi desbloqueado. As <strong>{{ $releasing->affected_count }}</strong> Ordem(ns) bloqueadas voltam, de uma vez, ao status que tinham antes do bloqueio.</p>
                        <label for="release-note" class="form-label fw-semibold text-uppercase small">Observação (opcional)</label>
                        <textarea id="release-note" class="form-control" rows="3" wire:model.defer="releaseNote" maxlength="1000" placeholder="Ex.: depósito regularizado em 21/10."></textarea>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary btn-dash" wire:click="cancelRelease">Cancelar</button>
                        <button type="button" class="btn btn-primary btn-dash" wire:click="submitRelease" wire:loading.attr="disabled" wire:target="submitRelease"><i class="ri-lock-unlock-line" aria-hidden="true"></i> Liberar todas</button>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-backdrop fade show"></div>
    @endif
</div>
