@php
    use App\Services\Closure\{ClosureEventLabels, ClosureIssueWorkflowService, ClosurePartnerExporter};

    $outcomeTone = ['Aceita' => 'green', 'Devolvida para correção' => 'red', 'Em validação' => 'blue', 'Aguardando validação' => 'amber'];
@endphp
<div class="closure-dash">
    <x-show-loading />

    <div class="dash-hero mb-3">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div>
                <h2 class="dash-title">Meu histórico de retornos</h2>
                <div class="dash-subtitle">Em quais obras você já respondeu ao Encerramento, o que respondeu, quando e o que aconteceu depois.</div>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <a href="{{ route('partner.closure.issues') }}" class="btn btn-light btn-sm btn-dash"><i class="ri-arrow-left-line"></i> Pendências</a>
                <button type="button" class="btn btn-light btn-sm btn-dash" wire:click="exportToExcel" wire:loading.attr="disabled" wire:target="exportToExcel"><i class="ri-file-excel-2-line"></i> Exportar Excel</button>
            </div>
        </div>
    </div>

    <div class="row g-2 mb-3">
        @foreach ([['Retornos enviados', $totals['responses'], 'ri-reply-line', 'blue', 'no recorte filtrado'], ['Obras atendidas', $totals['notes'], 'ri-building-line', 'teal', 'obras distintas'], ['Aceitos', $totals['accepted'], 'ri-checkbox-circle-line', 'green', 'validados pelo Encerramento'], ['Devolvidos', $totals['returned'], 'ri-arrow-go-back-line', 'red', 'voltaram para correção']] as [$label, $value, $icon, $tone, $note])
            <div class="col-6 col-xl-3" wire:key="hkpi-{{ $loop->index }}">
                <div class="kpi tone-{{ $tone }}">
                    <div class="kpi-top"><span>{{ $label }}</span><i class="{{ $icon }}"></i></div>
                    <div class="kpi-value">{{ number_format($value, 0, ',', '.') }}</div>
                    <div class="kpi-note">{{ $note }}</div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="table-card p-3 mb-3">
        <div class="row g-2 align-items-end">
            <div class="col-12 col-md-4">
                <label class="ctl-label" for="h-search">Buscar</label>
                <input id="h-search" type="search" class="form-control form-control-sm" placeholder="Nota ou Ordem" wire:model.debounce.400ms="search">
            </div>
            <div class="col-6 col-md-2">
                <label class="ctl-label" for="h-status">Situação atual</label>
                <select id="h-status" class="form-select form-select-sm" wire:model="status">
                    <option value="">Todas</option>
                    @foreach ($statuses as $statusKey)
                        <option value="{{ $statusKey }}">{{ ClosureEventLabels::issueStatusLabel($statusKey) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-md-2">
                <label class="ctl-label" for="h-from">De</label>
                <input id="h-from" type="date" class="form-control form-control-sm" wire:model="from">
            </div>
            <div class="col-6 col-md-2">
                <label class="ctl-label" for="h-to">Até</label>
                <input id="h-to" type="date" class="form-control form-control-sm" wire:model="to">
            </div>
            @if ($companies->isNotEmpty())
                <div class="col-6 col-md-2">
                    <label class="ctl-label" for="h-company">Empresa</label>
                    <select id="h-company" class="form-select form-select-sm" wire:model="partnerCompanyFilter">
                        <option value="">Todas</option>
                        @foreach ($companies as $company)
                            <option value="{{ $company->id }}">{{ $company->name }}</option>
                        @endforeach
                    </select>
                </div>
            @endif
        </div>
        <div class="mt-2">
            <x-closure.filter-chips :filters="collect($chips)->map(fn ($chip) => ['key' => $chip[0], 'label' => strtok($chip[1], ':'), 'value' => trim(substr($chip[1], strpos($chip[1], ':') + 1))])->all()" />
        </div>
    </div>

    <div class="table-card mb-3">
        <div class="od-section-head">
            <div><h3><i class="ri-history-line text-muted"></i> Retornos enviados <span class="od-count">{{ $events->total() }} registros</span></h3><div class="small text-muted mt-1">Do mais recente para o mais antigo.</div></div>
        </div>
        <div class="table-responsive dash-scroll">
            <table class="table table-sm align-middle mb-0 dash-table table-min-lg">
                <colgroup>
                    <col class="col-pct-12"><col class="col-pct-14"><col class="col-pct-17"><col class="col-pct-27"><col class="col-pct-15"><col class="col-pct-15">
                </colgroup>
                <thead>
                    <tr>
                        <th class="ps-3">Respondida em</th>
                        <th>Obra / Ordem</th>
                        <th>Pendência</th>
                        <th>O que você respondeu</th>
                        <th>Resultado desta resposta</th>
                        <th class="pe-3 text-end">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($events as $event)
                        @php
                            $issue   = $event->Issue;
                            $answers = collect($event->meta['answers'] ?? []);
                            $outcome = ClosurePartnerExporter::outcomeOf($event);
                        @endphp
                        <tr wire:key="history-{{ $event->id }}">
                            <td class="ps-3 small">{{ $event->created_at?->format('d/m/Y H:i') }}<div class="ord-sub">{{ $event->Actor?->name }}</div></td>
                            <td><div class="ord-main">{{ $issue?->Order?->Note?->note ?? '—' }}</div><div class="ord-sub">Ordem {{ $issue?->Order?->ordem ?? '—' }}</div></td>
                            <td><div class="fw-semibold small">{{ $issue?->Category?->name }}</div><div class="ord-sub">{{ $issue?->Subcategory?->name }} · Ciclo {{ $issue?->cycle }}</div></td>
                            <td class="small cell-wrap">
                                {{ \Illuminate\Support\Str::limit($event->meta['message'] ?? '', 140) }}
                                @if ($answers->isNotEmpty())
                                    <div class="mt-1">
                                        <x-closure.status-badge :label="$answers->where('status', ClosureIssueWorkflowService::ANSWER_DONE)->count() . ' atendido(s)'" tone="green" />
                                        @if ($answers->where('status', '!=', ClosureIssueWorkflowService::ANSWER_DONE)->isNotEmpty())
                                            <x-closure.status-badge :label="$answers->where('status', '!=', ClosureIssueWorkflowService::ANSWER_DONE)->count() . ' com ressalva'" tone="amber" />
                                        @endif
                                    </div>
                                @endif
                                @if (!empty($event->meta['attachments']))<span class="badge bg-light text-dark mt-1"><i class="bi bi-paperclip"></i> {{ $event->meta['attachments'] }} anexo(s)</span>@endif
                            </td>
                            <td>
                                <x-closure.status-badge :label="$outcome" :tone="$outcomeTone[$outcome] ?? 'muted'" />
                                <div class="ord-sub mt-1">Hoje: {{ ClosureEventLabels::issueStatusLabel($issue?->status) }}</div>
                            </td>
                            <td class="pe-3 text-end cell-visible">
                                @if ($issue)
                                    <a class="btn btn-sm btn-outline-secondary btn-dash" href="{{ route('partner.closure.issue.show', $issue->id) }}"><i class="ri-eye-line"></i> Ver pedido</a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6"><x-closure.empty-state icon="ri-history-line" title="Nenhum retorno encontrado" text="Quando você responder uma pendência, ela aparece aqui com a data e o resultado." /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($events->hasPages())
            <div class="p-3 border-top">{{ $events->links() }}</div>
        @endif
    </div>
</div>
