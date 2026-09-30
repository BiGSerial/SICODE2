@extends('layouts.padrao')

@section('breadcrumb')
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
        <li class="breadcrumb-item">Qualidade</li>
        <li class="breadcrumb-item"><a href="{{ route('quality.history') }}">Histórico</a></li>
        <li class="breadcrumb-item active" aria-current="page">Nota {{ $process->Note?->note }}</li>
    </ol>
@endsection

@section('menu')
    @include('quality._menu')
@endsection

@section('content')
    @include('quality._styles')
    @php
        use App\Enum\{QualityProcessState as S, QualityProcessStatus, QualityStageType};
        $isBudget = $process->phase === QualityStageType::BUDGET;
        $elapsed  = ($process->completed_at ?? now())->diffForHumans($process->dispatched_at, \Carbon\CarbonInterface::DIFF_ABSOLUTE, true, 2);
    @endphp
    <div class="closure-dash">
        @php
            $svc       = $activityNames[$process->phase->value] ?? null;
            $seesN2    = auth()->user()->can('quality.n2');
            $rejCount  = $process->Rejections->count();
            $initials  = fn (?string $name) => $name ? collect(preg_split('/\s+/', trim($name)))->filter(fn ($w) => mb_strlen($w) > 2)->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->take(2)->implode('') : '?';
        @endphp
        <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
            <div>
                <div class="small text-muted text-uppercase fw-bold">Processo de Qualidade</div>
                <h1 class="h2 fw-bold mb-0" style="color: var(--dash-ink)">Nota {{ $process->Note?->note }}</h1>
                <div class="text-muted">{{ \Illuminate\Support\Str::limit($process->Note?->material, 90) }}</div>
            </div>
            <div class="text-end">
                <span class="badge fs-6 text-bg-{{ $process->state->badge() }}"><i class="ri-record-circle-line"></i> {{ $process->state->labelFor($seesN2) }}</span>
                <div class="small text-muted mt-1">{{ $process->phase->label() }}{{ $svc ? ' · ' . $svc : '' }} · rodada {{ $process->round_number }}</div>
            </div>
        </div>

        @include('quality._feedback')
        @include('quality._turn')

        <div class="row g-2 mb-3">
            <div class="col-6 col-lg-4 col-xl-2"><div class="kpi"><div class="kpi-ico"><i class="ri-building-line"></i></div><div><div class="kpi-label">Empresa</div><div class="kpi-value">{{ $process->Company?->name ?? '—' }}</div></div></div></div>
            <div class="col-6 col-lg-4 col-xl-2"><div class="kpi"><div class="kpi-ico"><i class="ri-user-follow-line"></i></div><div><div class="kpi-label">N1 responsável</div><div class="kpi-value">{{ $process->N1User?->name ?? 'a definir' }}</div></div></div></div>
            <div class="col-6 col-lg-4 col-xl-2"><div class="kpi"><div class="kpi-ico"><i class="ri-user-settings-line"></i></div><div><div class="kpi-label">Usuário atual</div><div class="kpi-value">{{ $process->CurrentDesigner?->name ?? 'a definir' }}</div></div></div></div>
            <div class="col-6 col-lg-4 col-xl-2"><div class="kpi"><div class="kpi-ico"><i class="ri-timer-line"></i></div><div><div class="kpi-label">Nesta etapa</div><div class="kpi-value">@include('quality._age', ['since' => $process->state_changed_at])</div></div></div></div>
            <div class="col-6 col-lg-4 col-xl-2"><div class="kpi"><div class="kpi-ico"><i class="ri-hourglass-line"></i></div><div><div class="kpi-label">Tempo total</div><div class="kpi-value">{{ $elapsed }}</div><div class="kpi-sub">desde {{ optional($process->dispatched_at)->format('d/m/Y') }}</div></div></div></div>
            <div class="col-6 col-lg-4 col-xl-2"><div class="kpi"><div class="kpi-ico" style="background:#fff3cd;color:#8a6d00"><i class="ri-arrow-go-back-line"></i></div><div><div class="kpi-label">Devoluções</div><div class="kpi-value">{{ $rejCount }}</div><div class="kpi-sub">rodada atual: {{ $process->round_number }}</div></div></div></div>
        </div>

        <div class="table-card p-3 mb-3">
            <div class="d-flex justify-content-between align-items-center mb-2"><span class="metric-label">Jornada da obra</span><span class="small text-muted">✓ concluída · destaque = etapa atual · ↺ rodadas</span></div>
            @include('quality._journey')
        </div>

        <div class="row g-3">
            <div class="col-xl-4 order-xl-2"><div class="action-panel" id="q-action">
                {{-- ===== AÇÕES ===== --}}
                @if ($process->status === QualityProcessStatus::COMPLETED)
                    <div class="table-card p-3 mb-3 border-success">
                        <div class="chart-title text-success"><i class="ri-checkbox-circle-line"></i> Processo concluído</div>
                        <div class="small mt-1">Aprovado por {{ $process->CompletedBy?->name }} em {{ $process->completed_at?->format('d/m/Y H:i') }}. Atividade encerrada.</div>
                    </div>
                @endif

                @if ($can['retry'])
                    <div class="table-card p-3 mb-3 border-warning" id="q-retry">
                        <div class="chart-title text-warning"><i class="ri-error-warning-line"></i> Encerramento pendente</div>
                        <div class="small mt-1 mb-2">A aprovação final foi registrada, mas o encerramento não foi concluído. Conclua agora: a atividade é encerrada e a Qualidade concluída.</div>
                        <form method="post" action="{{ route('quality.process.retry-closing', $process) }}">@csrf
                            <button class="btn btn-success btn-sm btn-dash"><i class="ri-check-double-line"></i> Concluir encerramento</button>
                        </form>
                    </div>
                @endif

                @if ($can['assign'])
                    <div class="table-card p-3 mb-3">
                        <div class="chart-title">{{ $process->state === S::N2_RETURNED ? 'Encaminhar devolução do N2 ao usuário' : 'Despachar ao usuário da atividade' }}</div>
                        <div class="chart-subtitle mb-2">Somente usuários da empresa {{ $process->Company?->name }} habilitados em {{ $svc ?? 'a atividade do ciclo' }}. Só o N1 pode trocar o usuário.</div>
                        @if ($designers->isEmpty())
                            <div class="alert alert-warning small mb-0">Nenhum usuário da empresa está habilitado nesta atividade.</div>
                        @else
                            <form method="post" action="{{ route('quality.process.assign', $process) }}">@csrf
                                <label class="form-label small mb-1">Usuário</label>
                                <select name="designer_id" class="form-select form-select-sm mb-2" required>
                                    <option value="">Selecione</option>
                                    @foreach ($designers as $designer)
                                        <option value="{{ $designer->id }}" @selected($designer->id === $process->current_designer_id)>{{ $designer->name }}{{ $designer->id === $process->current_designer_id ? ' (último usuário)' : '' }}</option>
                                    @endforeach
                                </select>
                                <label class="form-label small mb-1">Orientações ao usuário</label>
                                <textarea name="observation" rows="3" class="form-control form-control-sm mb-2" placeholder="Opcional. Ex.: pontos a corrigir apontados pelo N2."></textarea>
                                <button class="btn btn-primary btn-sm btn-dash"><i class="ri-send-plane-line"></i> Despachar</button>
                            </form>
                        @endif
                    </div>
                @endif

                @if ($can['contest'])
                    <div class="table-card p-3 mb-3">
                        <div class="chart-title">Questionar o N2</div>
                        <div class="chart-subtitle mb-2">Discorda da devolução? Justifique e devolva a decisão ao N2, sem acionar o usuário.</div>
                        <form method="post" action="{{ route('quality.process.contest', $process) }}">@csrf
                            <textarea name="justification" rows="3" class="form-control form-control-sm mb-2" required placeholder="Justificativa para o N2"></textarea>
                            <button class="btn btn-outline-warning btn-sm btn-dash w-100" onclick="return confirm('Devolver a decisão ao N2 com esta justificativa?')"><i class="ri-question-answer-line"></i> Questionar e devolver ao N2</button>
                        </form>
                    </div>
                @endif

                @if ($can['reassign'])
                    <div class="table-card p-3 mb-3">
                        <div class="chart-title"><i class="ri-exchange-line"></i> Trocar o usuário</div>
                        <div class="chart-subtitle mb-2">Só o N1 pode trocar. A atividade sai da pilha de {{ $process->CurrentDesigner?->name }} e vai para o novo usuário (habilitado em {{ $svc ?? 'a atividade' }}).</div>
                        <form method="post" action="{{ route('quality.n1.reassign') }}">@csrf
                            <input type="hidden" name="process_ids[]" value="{{ $process->id }}">
                            <select name="designer_id" class="form-select form-select-sm mb-2" required>
                                <option value="">Escolha o novo usuário</option>
                                @foreach ($designers->where('id', '!=', $process->current_designer_id) as $designer)<option value="{{ $designer->id }}">{{ $designer->name }}</option>@endforeach
                            </select>
                            <input name="reason" class="form-control form-control-sm mb-2" maxlength="500" placeholder="Motivo (opcional)">
                            <button class="btn btn-warning btn-sm btn-dash w-100" onclick="return confirm('Trocar o usuário desta atividade?')"><i class="ri-exchange-line"></i> Reatribuir</button>
                        </form>
                    </div>
                @endif

                @if ($can['review1'] || $can['review2'])
                    @php $isN2Review = $can['review2']; @endphp
                    <div class="table-card p-3 mb-3" id="q-decision">
                        <div class="chart-title">{{ $isN2Review ? 'Sua decisão (N2)' : 'Sua análise (N1)' }}</div>
                        <div class="chart-subtitle mb-3">{{ $process->phase->label() }} · rodada {{ $process->round_number }}. Escolha o que fazer:</div>

                        <div class="row g-2 mb-3">
                            <div class="col-6"><button type="button" class="decision-btn decision-approve" data-pane="#q-approve"><i class="ri-checkbox-circle-line"></i><b>Aprovar</b><small>{{ !$isN2Review ? 'Segue para a decisão do N2.' : ($isBudget ? 'Conclui a Qualidade, sem volta.' : 'Encerra a atividade e abre a do 2º ciclo.') }}</small></button></div>
                            <div class="col-6"><button type="button" class="decision-btn decision-reject" data-pane="#q-reject"><i class="ri-arrow-go-back-line"></i><b>{{ $isN2Review ? 'Devolver' : 'Rejeitar' }}</b><small>{{ $isN2Review ? 'Volta ao N1, com o motivo.' : 'Volta ao usuário, com o motivo.' }}</small></button></div>
                        </div>

                        <div id="q-approve" class="decision-pane d-none">
                            <form method="post" action="{{ route('quality.process.approve', $process) }}">@csrf
                                <label class="form-label small fw-bold mb-1">Comentário da aprovação (opcional)</label>
                                <textarea name="observation" rows="2" class="form-control form-control-sm mb-2"></textarea>
                                <button class="btn btn-success w-100 btn-dash" onclick="return confirm('{{ $isN2Review && $isBudget ? 'Aprovar e encerrar? A atividade será encerrada e a Qualidade concluída, sem volta.' : 'Confirmar a aprovação?' }}')"><i class="ri-check-double-line"></i> {{ !$isN2Review ? 'Aprovar e encaminhar ao N2' : ($isBudget ? 'Aprovar e encerrar (conclui a Qualidade)' : 'Aprovar o 1º ciclo e abrir o 2º ciclo') }}</button>
                            </form>
                        </div>

                        <div id="q-reject" class="decision-pane d-none">
                            <form method="post" action="{{ route('quality.process.reject', $process) }}" id="quality-reject-form">@csrf
                                <div class="fw-bold small mb-1">Motivo (categoria e subcategoria)</div>
                                <div id="quality-reasons" class="mb-2"></div>
                                <button type="button" class="btn btn-outline-secondary btn-sm mb-2" id="quality-add-reason"><i class="ri-add-line"></i> Adicionar outro motivo</button>
                                <label class="form-label small fw-bold mb-1">{{ $isN2Review ? 'Comentário para o N1 (opcional)' : 'Observação geral da rejeição (opcional)' }}</label>
                                <textarea name="observation" rows="3" class="form-control form-control-sm mb-2"></textarea>
                                <button class="btn btn-danger w-100 btn-dash"><i class="ri-arrow-go-back-line"></i> {{ $isN2Review ? 'Devolver ao N1' : 'Rejeitar e devolver ao usuário' }}</button>
                                <div class="form-text">{{ $isN2Review ? 'Toda devolução exige categoria (e subcategoria quando houver). O N2 devolve somente ao N1, que escolhe o usuário.' : 'Toda rejeição exige categoria (e subcategoria quando houver). Pode haver vários motivos.' }}</div>
                            </form>
                        </div>
                    </div>
                @endif

            </div></div>

            <div class="col-xl-8 order-xl-1">
                @include('quality._tabs')
            </div>
        </div>
    </div>
@endsection

@push('script')
    @if ($can['review1'] || $can['review2'])
        @php
            $reasonOptions = $categories->map(function ($category) {
                return ['id' => $category->id, 'name' => $category->name, 'children' => $category->Children->map(function ($sub) {
                    return ['id' => $sub->id, 'name' => $sub->name];
                })->values()];
            })->values();
        @endphp
        <script>
            (function () {
                const categories = @json($reasonOptions);
                const holder = document.getElementById('quality-reasons');
                let index = 0;

                function addReason() {
                    const i = index++;
                    const row = document.createElement('div');
                    row.className = 'border rounded p-2 mb-2';
                    row.innerHTML = `
                        <div class="d-flex gap-1 mb-1">
                            <select class="form-select form-select-sm" name="reasons[${i}][category_id]" required>
                                <option value="">Categoria do motivo</option>${categories.map(c => `<option value="${c.id}">${c.name}</option>`).join('')}
                            </select>
                            <button type="button" class="btn btn-sm btn-outline-danger" aria-label="Remover motivo"><i class="ri-delete-bin-line"></i></button>
                        </div>
                        <select class="form-select form-select-sm mb-1" name="reasons[${i}][subcategory_id]"><option value="">Subcategoria</option></select>
                        <input class="form-control form-control-sm" name="reasons[${i}][observation]" placeholder="Observação deste motivo (opcional)">`;
                    const [category, sub] = row.querySelectorAll('select');
                    category.addEventListener('change', () => {
                        const found = categories.find(c => String(c.id) === category.value);
                        sub.innerHTML = '<option value="">Subcategoria</option>' + (found ? found.children.map(s => `<option value="${s.id}">${s.name}</option>`).join('') : '');
                        sub.required = !!(found && found.children.length);
                    });
                    row.querySelector('button').addEventListener('click', () => row.remove());
                    holder.appendChild(row);
                }

                document.getElementById('quality-add-reason').addEventListener('click', addReason);
                addReason();
                document.querySelectorAll('#q-decision .decision-btn').forEach(function (button) {
                    button.addEventListener('click', function () {
                        document.querySelectorAll('#q-decision .decision-btn').forEach(function (b) { b.classList.toggle('is-active', b === button); });
                        document.querySelectorAll('#q-decision .decision-pane').forEach(function (pane) { pane.classList.toggle('d-none', '#' + pane.id !== button.dataset.pane); });
                    });
                });
            })();
        </script>
    @endif
@endpush
