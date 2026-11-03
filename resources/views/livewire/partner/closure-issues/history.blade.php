@php
    use App\Services\Closure\{ClosureEventLabels, ClosureIssueWorkflowService, ClosurePartnerExporter};

    $outcomeTone = ['Aceita' => 'green', 'Devolvida para correção' => 'red', 'Em validação' => 'blue', 'Aguardando validação' => 'amber'];
    $tabs        = ['' => 'Todas'];
    foreach ($statuses as $statusKey) {
        $tabs[$statusKey] = ClosureEventLabels::issueStatusLabel($statusKey);
    }
@endphp

{{-- HISTÓRICO DE RETORNOS DA PARCEIRA (lista de controle + padrão do Encerramento: docs/encerramento/PADRAO_UI_ENCERRAMENTO.md). --}}
<div class="closure-dash my-panel ops-list ops-control">

    <x-ops.hero class="ops-hero--control" eyebrow="Portal da parceira · Encerramento" title="Meu histórico de retornos"
        subtitle="Em quais obras você já respondeu ao Encerramento, o que respondeu, quando e o que aconteceu depois.">
        <div class="d-flex flex-wrap gap-2 justify-content-end align-items-center">
            <a href="{{ route('partner.closure.issues') }}" class="btn btn-light btn-sm btn-dash"><i class="ri-arrow-left-line" aria-hidden="true"></i> Pendências</a>
            <button type="button" class="btn btn-light btn-sm btn-dash" wire:click="exportToExcel" wire:loading.attr="disabled" wire:target="exportToExcel"><i class="ri-file-excel-2-line" aria-hidden="true"></i> Exportar Excel</button>
        </div>
    </x-ops.hero>

    <section class="ops-stats" aria-label="Resumo dos retornos">
        <x-ops.stat tone="blue" label="Retornos enviados" :value="number_format($totals['responses'], 0, ',', '.')" icon="ri-reply-line" title="No recorte filtrado" />
        <x-ops.stat tone="teal" label="Obras atendidas" :value="number_format($totals['notes'], 0, ',', '.')" icon="ri-building-line" title="Obras distintas" />
        <x-ops.stat tone="green" label="Aceitos" :value="number_format($totals['accepted'], 0, ',', '.')" icon="ri-checkbox-circle-line" title="Validados pelo Encerramento" />
        <x-ops.stat tone="red" label="Devolvidos" :value="number_format($totals['returned'], 0, ',', '.')" icon="ri-arrow-go-back-line" title="Voltaram para correção" />
    </section>

    <section class="ops-toolbar" aria-label="Filtros do histórico">
        <div class="ops-toolbar-row">
            <x-ops.search wire:model.debounce.500ms="search" placeholder="Buscar por nota ou ordem…" label="Buscar retorno" />

            <x-ops.segmented name="partnerHistoryStatus" label="Situação atual da pendência" wire:model="status" :options="$tabs" />

            <span class="ops-toolbar-spacer"></span>

            @if ($hasCriteria)
                <button type="button" class="ops-chips-clear" wire:click="clearFilters"><i class="ri-filter-off-line" aria-hidden="true"></i> Limpar filtros</button>
            @endif
        </div>

        <div class="ops-toolbar-row">
            <span class="ops-toolbar-label">Período</span>
            <input type="date" class="form-control form-control-sm w-auto" wire:model="from" aria-label="Respondida a partir de">
            <input type="date" class="form-control form-control-sm w-auto" wire:model="to" aria-label="Respondida até">
            @if (count($this->listFilterPayload()['defs']))
                <span class="ops-toolbar-label">Filtros</span>
                <x-ops.filters :payload="$this->listFilterPayload()" />
            @endif
        </div>

        @if ($this->hasListFilters())
            <div class="ops-toolbar-row">
                <x-ops.chips :chips="$this->listFilterChips()" />
            </div>
        @endif
    </section>

    <section class="mp-panel" aria-label="Retornos enviados">
        <div class="mp-panel-head">
            <div>
                <h2 class="mp-panel-title">Retornos enviados</h2>
                <div class="mp-panel-sub">{{ number_format($events->total(), 0, ',', '.') }} {{ $events->total() === 1 ? 'registro' : 'registros' }} · do mais recente para o mais antigo.</div>
            </div>
        </div>

        <x-ops.skeleton wire:loading.delay.class.remove="d-none" class="d-none" :rows="8" :cols="6" />

        <div wire:loading.delay.class="d-none">
            @if ($events->isEmpty())
                <x-closure.empty-state icon="ri-history-line" title="Nenhum retorno encontrado" text="Quando você responder uma pendência, ela aparece aqui com a data e o resultado." />
            @else
                <div class="table-responsive">
                    <table class="table mp-table align-middle mb-0">
                        <colgroup>
                            <col style="width: 150px">
                            <col style="width: 14%">
                            <col style="width: 18%">
                            <col>
                            <col style="width: 200px">
                            <col style="width: 140px">
                        </colgroup>
                        <thead>
                            <tr>
                                <th>Respondida em</th>
                                <th>Nota / Ordem</th>
                                <th>Pendência</th>
                                <th>O que você respondeu</th>
                                <th>Resultado desta resposta</th>
                                <th><span class="visually-hidden">Ações</span></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($events as $event)
                                @php
                                    $issue    = $event->Issue;
                                    $answers  = collect($event->meta['answers'] ?? []);
                                    $done     = $answers->where('status', ClosureIssueWorkflowService::ANSWER_DONE)->count();
                                    $reserved = $answers->count() - $done;
                                    $outcome  = ClosurePartnerExporter::outcomeOf($event);
                                @endphp
                                <tr wire:key="history-{{ $event->id }}">
                                    <td>
                                        <x-ops.date :date="$event->created_at" :time="true" />
                                        <div class="ops-person-sub">{{ $event->Actor?->name }}</div>
                                    </td>
                                    <td><x-ops.note :note="$issue?->Order?->Note?->note ?? '—'" :sub="'Ordem ' . ($issue?->Order?->ordem ?? '—')" /></td>
                                    <td>
                                        <div class="ops-person-name">{{ $issue?->Category?->name ?? '—' }}</div>
                                        <div class="ops-person-sub">{{ $issue?->Subcategory?->name }} · Ciclo {{ $issue?->cycle }}</div>
                                    </td>
                                    <td class="cell-wrap">
                                        {{ \Illuminate\Support\Str::limit($event->meta['message'] ?? '', 140) ?: '—' }}
                                        @if ($answers->isNotEmpty() || !empty($event->meta['attachments']))
                                            <div class="mt-1 d-flex flex-wrap gap-1">
                                                @if ($answers->isNotEmpty())
                                                    <x-ops.badge sm tone="green" :label="$done . ' atendido(s)'" />
                                                    @if ($reserved > 0)
                                                        <x-ops.badge sm tone="amber" :label="$reserved . ' com ressalva'" />
                                                    @endif
                                                @endif
                                                @if (!empty($event->meta['attachments']))
                                                    <x-ops.badge sm tone="muted" icon="ri-attachment-2" :label="$event->meta['attachments'] . ' anexo(s)'" />
                                                @endif
                                            </div>
                                        @endif
                                    </td>
                                    <td>
                                        <x-ops.badge :tone="$outcomeTone[$outcome] ?? 'muted'" :label="$outcome" />
                                        <div class="ops-person-sub">Hoje: {{ ClosureEventLabels::issueStatusLabel($issue?->status) }}</div>
                                    </td>
                                    <td class="text-end">
                                        @if ($issue)
                                            <a class="btn btn-outline-secondary btn-sm ops-action" href="{{ route('partner.closure.issue.show', $issue->id) }}"><i class="ri-eye-line" aria-hidden="true"></i> Ver pedido</a>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <x-ops.pager :paginator="$events" />
            @endif
        </div>
    </section>
</div>
