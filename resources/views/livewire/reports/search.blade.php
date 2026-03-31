<div class="report-search-page">
    {{-- Loading --}}
    <x-show-loading />

    <style>
        .report-search-page {
            --rs-bg: #f6f7fb;
            --rs-surface: #ffffff;
            --rs-ink: #1f2933;
            --rs-muted: #6b7280;
            --rs-border: #e5e7eb;
            --rs-accent: #0f766e;
            --rs-accent-dark: #0f172a;
            background: transparent;
            padding: 1.5rem 0;
        }

        .report-search-page .rs-shell {
            max-width: 100%;
            background: transparent !important;
        }

        .report-search-page .rs-hero {
            background: linear-gradient(120deg, var(--rs-accent-dark), var(--rs-accent) 70%);
            color: #f8fafc;
            border-radius: 1rem;
            padding: 1.4rem 1.5rem;
            box-shadow: 0 16px 32px rgba(15, 23, 42, 0.2);
            margin-bottom: 1rem;
        }

        .report-search-page .rs-hero .eyebrow {
            text-transform: uppercase;
            letter-spacing: .08em;
            font-size: .72rem;
            margin-bottom: .35rem;
            opacity: .75;
            font-weight: 600;
        }

        .report-search-page .rs-hero h2 {
            margin: 0;
            font-size: 1.55rem;
            line-height: 1.2;
            font-weight: 700;
        }

        .report-search-page .rs-hero p {
            margin: .45rem 0 0;
            opacity: .86;
        }

        .report-search-page .rs-hero-chip {
            background: rgba(248, 250, 252, .12);
            border: 1px solid rgba(248, 250, 252, .3);
            border-radius: .65rem;
            padding: .5rem .75rem;
            font-size: .8rem;
            min-width: 170px;
        }

        .report-search-page .rs-hero-chip strong {
            display: block;
            font-size: .94rem;
            color: #fff;
        }

        .report-search-page .card {
            background: rgba(255, 255, 255, .82);
            border: 1px solid var(--rs-border) !important;
            border-radius: .7rem !important;
            box-shadow: 0 12px 24px rgba(15, 23, 42, 0.06);
        }

        .report-search-page .card-header {
            padding: .75rem 1rem;
            background: transparent;
            border-bottom: 1px solid #e5e7eb;
        }

        .report-search-page .card-body {
            padding: 1.1rem 1.15rem;
        }

        .report-search-page .row+.card {
            margin-top: 1rem;
        }

        .report-search-page .table {
            margin-bottom: 0;
        }

        .report-search-page .table thead th {
            font-size: .73rem;
            letter-spacing: .06em;
            text-transform: uppercase;
            white-space: nowrap;
        }

        .report-search-page .table tbody td {
            font-size: .88rem;
            vertical-align: middle;
        }

        .report-search-page .table-responsive {
            border-top: 1px solid #f1f5f9;
            border-radius: 0 !important;
        }

        .report-search-page .rs-section-title {
            margin: 0;
            color: #f8fafc;
            font-size: 1rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .04em;
        }

        .report-search-page .rs-search-card .card-body {
            padding-top: .95rem;
        }

        .report-search-page .rs-note-card.edp-bg-sprucegreen-70 {
            background: transparent !important;
        }

        .report-search-page .rs-head-unified {
            background: linear-gradient(120deg, #0f172a, #0f766e 80%) !important;
            border-bottom: 0 !important;
        }

        .report-search-page .table,
        .report-search-page .table thead,
        .report-search-page .table tbody,
        .report-search-page .table tr,
        .report-search-page .table th,
        .report-search-page .table td {
            border-radius: 0 !important;
        }

        .report-search-page .rs-order-header {
            background: linear-gradient(120deg, #0f172a, #0f766e 80%) !important;
            color: #f8fafc !important;
            border-bottom: 0 !important;
        }

        .report-search-page .rs-order-header .order-label {
            color: #86efac;
            font-weight: 700;
        }

        .report-search-page .rs-note-header h4,
        .report-search-page .rs-note-header h4 strong {
            color: #f8fafc !important;
            font-weight: 700;
        }

        .report-search-page .rs-note-card dt.edp-bg-sprucegreen-100 {
            background: #e7f5f2 !important;
            color: #0f766e !important;
            border-radius: .45rem;
            padding: .28rem .55rem;
            font-size: .78rem;
            text-transform: uppercase;
            letter-spacing: .03em;
        }

        .report-search-page .rs-note-card dd.text-white {
            color: #0f172a !important;
            margin-bottom: .55rem;
            font-weight: 500;
        }

        .report-search-page .rs-note-card .text-warning {
            color: #ca8a04 !important;
        }

        .report-search-page .rs-note-card .btn-outline-light {
            border-color: #0f766e;
            color: #0f766e;
        }

        .report-search-page .rs-note-card .btn-outline-light:hover {
            background: #0f766e;
            color: #fff;
        }

        .report-search-page .rs-cancel-note-btn {
            font-weight: 700;
            letter-spacing: .02em;
            border-width: 2px;
            box-shadow: 0 8px 18px rgba(15, 23, 42, 0.25);
            transform: translateY(0);
            transition: transform .15s ease, box-shadow .15s ease, filter .15s ease;
        }

        .report-search-page .rs-cancel-note-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 12px 24px rgba(15, 23, 42, 0.3);
            filter: brightness(1.03);
        }

        .report-search-page .rs-cancel-note-btn:focus-visible {
            outline: 3px solid rgba(255, 255, 255, 0.45);
            outline-offset: 2px;
        }

        .report-search-page .rs-cancel-note-btn.is-inconsistent {
            border-color: #dc2626 !important;
            box-shadow: 0 0 0 2px rgba(220, 38, 38, 0.25), 0 12px 24px rgba(15, 23, 42, 0.3);
        }

        .report-search-page .rs-cancel-note-btn.is-submitted,
        .report-search-page .rs-cancel-order-btn.is-submitted {
            background: #0ea5e9 !important;
            border-color: #0369a1 !important;
            color: #fff !important;
        }

        .report-search-page .rs-cancel-note-btn.is-in-progress,
        .report-search-page .rs-cancel-order-btn.is-in-progress {
            background: #f59e0b !important;
            border-color: #b45309 !important;
            color: #111827 !important;
        }

        .report-search-page .rs-cancel-note-btn.is-done,
        .report-search-page .rs-cancel-order-btn.is-done {
            background: #dc2626 !important;
            border-color: #991b1b !important;
            color: #fff !important;
            box-shadow: 0 0 0 2px rgba(220, 38, 38, 0.25), 0 12px 24px rgba(15, 23, 42, 0.32);
        }

        .report-search-page .rs-cancel-order-btn {
            font-weight: 700;
            letter-spacing: .02em;
            border-width: 2px;
        }

        .report-search-page .rs-viab-view-btn {
            font-weight: 700;
            border-width: 1px;
            letter-spacing: .02em;
        }

        .report-search-page .rs-workform-canceled-row {
            background: #fef2f2 !important;
        }

        .report-search-page .rs-viab-modal .modal-content {
            border: 1px solid #dbe5ef;
            border-radius: .95rem;
            box-shadow: 0 20px 35px rgba(15, 23, 42, 0.2);
            background: #dfe7f1;
        }

        .report-search-page .rs-viab-modal .modal-header {
            background: linear-gradient(120deg, #0f172a, #0f766e 78%);
            color: #f8fafc;
            border-bottom: 0;
        }

        .report-search-page .rs-viab-modal .modal-body {
            background: radial-gradient(circle at 8% 0%, #cfd8e5 0%, transparent 38%),
                        radial-gradient(circle at 95% 10%, #d6e1ee 0%, transparent 34%),
                        #dbe4ef;
        }

        .report-search-page .rs-viab-modal .modal-footer {
            background: #dbe4ef;
            border-top: 1px solid #c7d2e3;
        }

        .report-search-page .rs-viab-modal .modal-title {
            font-weight: 700;
            letter-spacing: .02em;
        }

        .report-search-page .rs-viab-hero {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: .75rem;
            margin-bottom: .9rem;
        }

        .report-search-page .rs-viab-stat {
            background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
            border: 1px solid #dbe5ef;
            border-radius: .75rem;
            padding: .7rem .8rem;
            min-height: 84px;
            box-shadow: 0 12px 24px rgba(15, 23, 42, 0.18);
        }

        .report-search-page .rs-viab-stat .label {
            display: block;
            font-size: .72rem;
            text-transform: uppercase;
            letter-spacing: .05em;
            color: #64748b;
            font-weight: 700;
            margin-bottom: .3rem;
        }

        .report-search-page .rs-viab-stat .value {
            color: #0f172a;
            font-weight: 700;
            line-height: 1.2;
        }

        .report-search-page .rs-viab-stat.is-result {
            background: linear-gradient(120deg, #0f172a, #0f766e 80%);
            border-color: #0f766e;
            box-shadow: 0 10px 20px rgba(15, 118, 110, 0.25);
        }

        .report-search-page .rs-viab-stat.is-result .label,
        .report-search-page .rs-viab-stat.is-result .value {
            color: #f8fafc;
        }

        .report-search-page .rs-result-pill {
            font-size: .95rem;
            padding: .42rem .68rem;
            border: 2px solid rgba(248, 250, 252, 0.4);
            box-shadow: 0 8px 18px rgba(15, 23, 42, 0.24);
        }

        .report-search-page .rs-viab-block {
            border: 1px solid #e5e7eb;
            border-radius: .75rem;
            background: linear-gradient(180deg, #f8fafc 0%, #f1f5f9 100%);
            padding: .85rem;
            height: 100%;
            box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.55);
            box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.55), 0 12px 24px rgba(15, 23, 42, 0.16);
        }

        .report-search-page .rs-viab-block h6 {
            text-transform: uppercase;
            letter-spacing: .04em;
            font-size: .78rem;
            color: #0f172a;
            margin-bottom: .7rem;
            font-weight: 700;
        }

        .report-search-page .rs-kv {
            display: grid;
            grid-template-columns: 150px 1fr;
            gap: .35rem .6rem;
            font-size: .86rem;
        }

        .report-search-page .rs-kv .k {
            color: #64748b;
            font-weight: 600;
        }

        .report-search-page .rs-kv .v {
            color: #0f172a;
            font-weight: 600;
            word-break: break-word;
        }

        .report-search-page .rs-viab-text {
            border: 1px solid #e2e8f0;
            border-radius: .65rem;
            background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
            min-height: 88px;
            padding: .7rem;
            white-space: pre-wrap;
            font-size: .87rem;
        }

        .report-search-page .rs-viab-block.is-reason {
            background: linear-gradient(180deg, #fff7ed 0%, #ffedd5 100%);
            border-color: #fdba74;
            box-shadow: 0 10px 22px rgba(249, 115, 22, 0.14);
        }

        .report-search-page .rs-viab-block.is-description {
            background: linear-gradient(180deg, #ecfeff 0%, #cffafe 100%);
            border-color: #67e8f9;
            box-shadow: 0 10px 22px rgba(6, 182, 212, 0.16);
        }

        .report-search-page .rs-viab-block.is-reason .rs-viab-text,
        .report-search-page .rs-viab-block.is-description .rs-viab-text {
            border-width: 2px;
            font-weight: 500;
        }

        .report-search-page .rs-viab-block.is-reason .rs-viab-text {
            border-color: #fdba74;
        }

        .report-search-page .rs-viab-block.is-description .rs-viab-text {
            border-color: #22d3ee;
        }

        @media (max-width: 991px) {
            .report-search-page .rs-hero {
                padding: 1.2rem;
            }

            .report-search-page .rs-kv {
                grid-template-columns: 120px 1fr;
            }

            .report-search-page .rs-viab-hero {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }
    </style>

    <div class="container-fluid rs-shell">
        <div class="rs-hero d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3">
            <div>
                <div class="eyebrow">Central de Consulta</div>
                <h2>Buscar Nota / OV</h2>
                <p>Visão consolidada de progresso, informes, ADS e documentos por serviço.</p>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <div class="rs-hero-chip">
                    Referência
                    <strong>{{ $lists?->note ?? 'Sem Nota Selecionada' }}</strong>
                </div>
                @if ($lists)
                    <div class="rs-hero-chip">
                        Data de Criação
                        <strong>{{ optional($lists->created_at)->format('d/m/Y H:i') }}</strong>
                    </div>
                    <div class="rs-hero-chip">
                        Última Atualização
                        <strong>{{ optional($lists->updated_at)->format('d/m/Y H:i') }}</strong>
                    </div>
                @endif
            </div>
        </div>

    {{-- BUSCAR NOTA/OV --}}
    <div class="card border-0 shadow rs-search-card">
        <div class="card-header rs-head-unified">
            <h4 class="rs-section-title">BUSCAR NOTA/OV</h4>
        </div>
        <div class="card-body">
            <div class="row align-items-end g-2">
                <div class="col-md-3">
                    <label for="searchInput" class="form-label">Buscar</label>
                    <input id="searchInput" class="form-control" type="text" placeholder="Informe a Nota/OV"
                        wire:model.defer="search" wire:keydown.enter="findNote">
                </div>
                <div class="col-md-2">
                    <button class="btn btn-primary w-100" wire:click.prevent="findNote">Buscar</button>
                </div>
            </div>
        </div>
    </div>

    @if ($lists)
        {{-- DADOS DA NOTA --}}
        @php
            $cancellationRequests = $lists->CancellationRequests ?? collect();
            $validStatuses = [
                \App\Enum\CancellationRequestStatus::SUBMITTED->value,
                \App\Enum\CancellationRequestStatus::ASSIGNED->value,
                \App\Enum\CancellationRequestStatus::PAUSED->value,
                \App\Enum\CancellationRequestStatus::DONE->value,
            ];

            $noteCancel = $cancellationRequests
                ->filter(function ($req) use ($validStatuses) {
                    $status = $req->status?->value ?? $req->status;
                    $isValid = in_array($status, $validStatuses, true);
                    $isNoteScope = ($req->scope?->value ?? $req->scope) === \App\Enum\CancellationRequestScope::NOTE_FULL->value;
                    $noOrders = $req->Orders->isEmpty();
                    return $isValid && ($isNoteScope || $noOrders);
                })
                ->sortByDesc('created_at')
                ->first();

            $noteCancelStatus = $noteCancel?->status?->value ?? $noteCancel?->status;
            $noteCancelLabel = null;
            $noteCancelClass = '';

            if ($noteCancelStatus === \App\Enum\CancellationRequestStatus::SUBMITTED->value) {
                $noteCancelLabel = 'Solicitado Cancelamento';
                $noteCancelClass = 'is-submitted';
            } elseif (in_array($noteCancelStatus, [
                \App\Enum\CancellationRequestStatus::ASSIGNED->value,
                \App\Enum\CancellationRequestStatus::PAUSED->value,
            ], true)) {
                $noteCancelLabel = 'Em Cancelamento';
                $noteCancelClass = 'is-in-progress';
            } elseif ($noteCancelStatus === \App\Enum\CancellationRequestStatus::DONE->value) {
                $noteCancelLabel = 'Cancelado';
                $noteCancelClass = 'is-done';
            }

            $hasSapCancel = $lists->Orders->contains(function ($order) {
                return \Illuminate\Support\Str::startsWith($order->statusSist ?? '', ['CANC', 'ENTE', 'ENCE']);
            });

            $noteCancelDoneAt = $noteCancel?->closed_at;
            $noteCancelInconsistent = false;
            if ($noteCancelStatus === \App\Enum\CancellationRequestStatus::DONE->value
                && $noteCancelDoneAt
                && $noteCancelDoneAt->diffInHours(now()) >= 24
                && !$hasSapCancel) {
                $noteCancelInconsistent = true;
            }
        @endphp

        <div class="card border-0 mt-4 shadow edp-bg-sprucegreen-70 edp-text-verde-dark rs-note-card">
            <div class="card-header rs-head-unified rs-note-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <h4 class="mb-0">
                    NOTA/OV: <strong class="text-uppercase">{{ $lists->note }}</strong>
                </h4>
                <div class="d-flex flex-wrap gap-2">
                    @if ($noteCancel && $noteCancelLabel)
                        <a href="{{ route('cancellations.show', ['request' => $noteCancel->id]) }}"
                            class="btn btn-sm rs-cancel-note-btn {{ $noteCancelClass }} {{ $noteCancelInconsistent ? 'is-inconsistent' : '' }}"
                            target="_blank" rel="noopener">
                            <i class="ri-alarm-warning-line me-1"></i>
                            {{ $noteCancelLabel }}
                            @if ($noteCancelInconsistent)
                                <span class="ms-1">• Inconsistente</span>
                            @endif
                        </a>
                    @endif
                    @if ($hasProtestOverview)
                        <a href="{{ route('protests.common.note', ['note' => $lists->id]) }}" target="_blank" class="btn btn-outline-light btn-sm">
                            <i class="ri-external-link-line me-1"></i>
                            Detalhes do Protesto
                        </a>
                    @endif
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    {{-- COLUNA ESQUERDA --}}
                    <div class="col-md-7">
                        <dl class="row ms-2">
                            <dt class="col-sm-4 edp-bg-sprucegreen-100 mb-1">RUBRICA</dt>
                            <dd class="col-sm-8 text-white text-uppercase">{{ $lists->rubrica }}</dd>

                            @if ($lists->type_note == 2)
                                <dt class="col-sm-4 edp-bg-sprucegreen-100 mb-1">GRUPO 1</dt>
                                <dd class="col-sm-8 text-white text-uppercase">{{ $lists->group1 }}</dd>
                                <dt class="col-sm-4 edp-bg-sprucegreen-100 mb-1">GRUPO 2</dt>
                                <dd class="col-sm-8 text-white text-uppercase">{{ $lists->group2 }}</dd>
                                <dt class="col-sm-4 edp-bg-sprucegreen-100 mb-1">GRUPO 4</dt>
                                <dd class="col-sm-8 text-white text-uppercase">{{ $lists->group4 }}</dd>
                                <dt class="col-sm-4 edp-bg-sprucegreen-100 mb-1">GRUPO 5</dt>
                                <dd class="col-sm-8 text-white text-uppercase">{{ $lists->group5 }}</dd>
                            @endif

                            <dt class="col-sm-4 edp-bg-sprucegreen-100 mb-1">DESCRIÇÃO</dt>
                            <dd class="col-sm-8 text-white text-uppercase">{{ $lists->numPedido }}</dd>

                            <dt class="col-sm-4 edp-bg-sprucegreen-100 mb-1">MUNICÍPIO</dt>
                            <dd class="col-sm-8 text-white text-uppercase">{{ $lists->lexp }}</dd>

                            <dt class="col-sm-4 edp-bg-sprucegreen-100 mb-1">MMGD</dt>
                            <dd class="col-sm-8 text-white text-uppercase">{{ $lists->mmgd ? 'SIM' : 'NÃO' }}</dd>

                            <dt class="col-sm-4 edp-bg-sprucegreen-100 mb-1">STATUS ATUAL</dt>
                            <dd class="col-sm-8 fw-bold text-white text-uppercase">{{ $lists->nstats }}</dd>

                            @if ($lists->type_note == 1)
                                <dt class="col-sm-4 edp-bg-sprucegreen-100 mb-1">CENTRO DE TRABALHO</dt>
                                <dd class="col-sm-8 fw-bold text-white text-uppercase">{{ $lists->centerjob }}</dd>
                            @endif

                            @php $lastDate = (new \App\Helpers\DaysLeft($lists))->getLastDate(); @endphp
                            <dt class="col-sm-4 edp-bg-sprucegreen-100 mb-1">PRAZO OBRA</dt>
                            <dd class="col-sm-8 fw-bold text-warning text-uppercase">{{ $lastDate }}</dd>

                            <dt class="col-sm-4 edp-bg-sprucegreen-100 mb-1">CRITICIDADE</dt>
                            <dd class="col-sm-8 text-white text-uppercase">{{ $lists->txpriority ?: '---' }}</dd>

                            {{-- D5 --}}
                            <dt class="col-sm-4 edp-bg-sprucegreen-100 mb-1">NOTA D5</dt>
                            <dd class="col-sm-8 text-white text-uppercase">
                                @if ($lists->FiveNote)
                                    <span class="fw-bold" style="cursor:pointer"
                                        wire:click.prevent="$emitTo('components.d5.d5details', 'openD5Details', {{ $lists->id }})">
                                        {{ $lists->FiveNote?->note_d5 ?? 'A GERAR D5' }}
                                        @if ($lists->FiveNote?->visible_partner && $lists->FiveNote?->is_completed)
                                            <small>( {{ $lists->FiveNote?->completed_at?->format('d/m/Y H:i') }}
                                                )</small>
                                        @endif
                                        <i class="ri-eye-line ms-1 text-primary"></i>
                                    </span>
                                @else
                                    ---
                                @endif
                            </dd>

                            @if ($lists->FiveNote)
                                @php
                                    $status = '';
                                    $color = '';
                                    if ($lists->FiveNote?->is_payed) {
                                        if ($lists->FiveNote?->is_archived) {
                                            $status = 'Finalizada';
                                            $color = 'text-bg-success';
                                        } elseif ($lists->FiveNote?->is_supervisioned) {
                                            $status = 'Aguardando Liberação Pagamento';
                                            $color = 'text-bg-danger';
                                        } elseif ($lists->FiveNote?->is_completed) {
                                            $status = 'Aguardando Fiscalização';
                                            $color = 'text-bg-danger';
                                        } elseif ($lists->FiveNote?->visible_partner) {
                                            $status = 'Aguardando Conclusão Parceira';
                                            $color = 'text-bg-primary';
                                        }
                                    } else {
                                        $status = 'Aguardando Despacho Pagamento';
                                        $color = 'text-bg-primary';
                                    }
                                @endphp
                                <dt class="col-sm-4 edp-bg-sprucegreen-100 mb-1">STATUS NOTA D5</dt>
                                <dd class="col-sm-8 text-white text-uppercase">
                                    <span class="badge {{ $color }}">{{ $status }}</span>
                                </dd>
                            @endif
                       
                        {{-- PROTESTOS --}}
                        @if ($lists->Protests->count())
                            <dt class="col-sm-4 edp-bg-sprucegreen-100 mb-1">RECLAMAÇÃO</dt>
                            <dd class="col-sm-8 text-white text-uppercase">
                                @foreach ($lists->Protests as $protest)
                                    <p class="mb-1">
                                        {{ $protest->nota }} - {{ $protest->tipoNota }}
                                        <a href="{{ route('protests.dispatch.view_only', ['protest' => $protest->nota]) }}"
                                            target="_blank" class="ms-2 text-primary">
                                            <i class="ri-external-link-line"></i>
                                        </a>
                                    </p>
                                @endforeach
                            </dd>
                        @endif
                         </dl>

                        {{-- ORDENS (já vêm com Operations) --}}
                        @if ($lists->Orders->count())
                            @foreach ($lists->Orders as $order)
                                @php
                                    $orderCancellation = $lists->CancellationRequests
                                        ->filter(fn($req) => $req->Orders->contains('id', $order->id))
                                        ->filter(fn($req) => in_array($req->status?->value ?? $req->status, $validStatuses, true))
                                        ->sortByDesc('created_at')
                                        ->first();

                                    $orderCancelStatus = $orderCancellation?->status?->value ?? $orderCancellation?->status;
                                    $orderCancelLabel = null;
                                    $orderCancelClass = '';

                                    if ($orderCancelStatus === \App\Enum\CancellationRequestStatus::SUBMITTED->value) {
                                        $orderCancelLabel = 'Solicitado Cancelamento';
                                        $orderCancelClass = 'is-submitted';
                                    } elseif (in_array($orderCancelStatus, [
                                        \App\Enum\CancellationRequestStatus::ASSIGNED->value,
                                        \App\Enum\CancellationRequestStatus::PAUSED->value,
                                    ], true)) {
                                        $orderCancelLabel = 'Em Cancelamento';
                                        $orderCancelClass = 'is-in-progress';
                                    } elseif ($orderCancelStatus === \App\Enum\CancellationRequestStatus::DONE->value) {
                                        $orderCancelLabel = 'Cancelado';
                                        $orderCancelClass = 'is-done';
                                    }
                                @endphp
                                <div class="card border-0 shadow mb-3">
                                    <div class="card-header rs-order-header d-flex justify-content-between align-items-center">
                                        <div>
                                            <span class="order-label">ORDEM:</span>
                                            {{ $order->ordem }}
                                            ({{ $order->statusSist ? explode(' ', $order->statusSist)[0] : '' }})
                                        </div>
                                        @if ($orderCancellation && $orderCancelLabel)
                                            <a class="btn btn-sm rs-cancel-order-btn {{ $orderCancelClass }}"
                                                href="{{ route('cancellations.show', ['request' => $orderCancellation->id]) }}"
                                                target="_blank" rel="noopener">
                                                {{ $orderCancelLabel }}
                                            </a>
                                        @endif
                                    </div>

                                    @php
                                        $closed = \Illuminate\Support\Str::startsWith($order->statusSist ?? '', [
                                            'ENTE',
                                            'ENCE',
                                        ]);
                                    @endphp

                                    @if ($closed || !$order->Operations->count())
                                        <div class="card-body text-bg-secondary text-center">
                                            @if ($closed)
                                                @if (\Illuminate\Support\Str::startsWith($order->statusSist ?? '', 'ENCE'))
                                                    <h5>ORDEM ENCERRADA</h5>
                                                @else
                                                    <h5>ORDEM ENCERRADA TÉCNICAMENTE</h5>
                                                @endif
                                            @else
                                                <h5>SEM OPERAÇÕES PARA ESSA ORDEM</h5>
                                            @endif
                                        </div>
                                    @else
                                        <div class="table-responsive">
                                            <table class="table table-sm table-striped mb-0">
                                                <thead class="table-dark">
                                                    <tr>
                                                        <th>Operação</th>
                                                        <th>Descrição</th>
                                                        <th>Status</th>
                                                        <th>CenTrab</th>
                                                        <th>IniPlan</th>
                                                        <th>FimPlan</th>
                                                        <th>IniReal</th>
                                                        <th>FimReal</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach ($order->Operations->sortBy('operacao') as $op)
                                                        <tr>
                                                            <td>{{ $op->operacao }}</td>
                                                            <td>{{ $op->descOperacao }}</td>
                                                            <td>{{ $op->status ? explode(' ', $op->status)[0] : '' }}
                                                            </td>
                                                            <td>{{ $op->cenTrab }}</td>
                                                            <td>{{ $op->inicioPlanejado ? \Carbon\Carbon::parse($op->inicioPlanejado)->format('d/m/Y') : '-' }}
                                                            </td>
                                                            <td>{{ $op->fimPlanejado ? \Carbon\Carbon::parse($op->fimPlanejado)->format('d/m/Y') : '-' }}
                                                            </td>
                                                            <td>{{ $op->inicioReal ? \Carbon\Carbon::parse($op->inicioReal)->format('d/m/Y') : '-' }}
                                                            </td>
                                                            <td>{{ $op->fimReal ? \Carbon\Carbon::parse($op->fimReal)->format('d/m/Y') : '-' }}
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        @endif
                    </div>

                    {{-- COLUNA DIREITA --}}
                    <div class="col-md-5">
                        {{-- ARQUIVOS (download/zip via HTTP) --}}
                        <div class="card border-0 mb-3 shadow">
                            <div class="card-header rs-head-unified d-flex justify-content-between align-items-center">
                                <h5 class="rs-section-title">ARQUIVOS</h5>
                                <div>
                                    @can('admin')
                                        <button class="btn btn-sm btn-primary"
                                            wire:click.prevent="$emitTo('files.manager.createfiles','createFile',{{ $lists->id }})">
                                            <i class="ri-chat-new-fill fs-5 align-middle"></i>
                                        </button>
                                        <button class="btn btn-sm btn-primary" wire:click="$emit('update_list')">
                                            <i class="ri-refresh-line fs-5"></i>
                                        </button>
                                    @endcan
                                </div>
                            </div>

                            @if ($lists->Files->count())
                                <div wire:key="attachments-note-{{ $lists?->id ?? 'empty' }}">
                                    <x-files.note-attachments :files="$lists->Files" selectionModel="selectedFiles" />
                                </div>
                                <div class="d-flex justify-content-end gap-2 p-2 border-top bg-light-subtle">
                                    <span class="small text-muted align-self-center">
                                        Selecionados: <strong>{{ count($selectedFiles) }}</strong>
                                    </span>
                                    <button class="btn btn-sm btn-outline-secondary" wire:click="$set('selectedFiles', [])">
                                        Limpar seleção
                                    </button>
                                    <button class="btn btn-sm btn-primary" wire:click.prevent="zipFiles">
                                        <i class="bx bxs-cloud-download"></i> Baixar Selecionados
                                    </button>
                                </div>
                            @else
                                <div class="card-body">
                                    <h6 class="text-center text-muted">SEM ARQUIVOS</h6>
                                </div>
                            @endif
                        </div>

                        {{-- STATUS HISTÓRICO (único sob demanda; outro banco) --}}
                        <div class="card border-0 shadow">
                            <div class="card-header rs-head-unified py-1 d-flex justify-content-between align-items-center">
                                <h5 class="rs-section-title">STATUS HISTÓRICO</h5>
                                <button class="btn btn-sm btn-primary" wire:click="loadHistorico">Carregar</button>
                            </div>
                            @if ($historico && $historico->count())
                                <div class="table-responsive">
                                    <table class="table table-sm table-striped table-hover mb-0">
                                        <thead class="table-dark">
                                            <tr>
                                                <th class="text-center">Data</th>
                                                <th class="text-center">Nstats</th>
                                                <th class="text-center">Desc</th>
                                                <th class="text-center">Usuário</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($historico as $hist)
                                                <tr class="{{ $hist->ultimoStatus ? 'table-primary' : '' }}">
                                                    <td class="text-center">
                                                        {{ date('d/m/Y H:i:s', strtotime($hist->dhStat)) }}</td>
                                                    <td class="text-center fw-bold">{{ $hist->numStat }}</td>
                                                    <td class="text-center">{{ $hist->status }}</td>
                                                    <td class="text-center">{{ $hist->transicaoUsuario }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <div class="card-body">
                                    <h6 class="text-center text-muted">SEM HISTÓRICO CARREGADO</h6>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- PROJETO --}}
        @if ($lists->Productions->count())
            <div class="card border-0 mt-3 shadow">
                <div class="card-header rs-head-unified">
                    <h5 class="rs-section-title">PROJETO</h5>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm table-striped mb-0">
                        <thead class="table-dark">
                            <tr>
                                <th>#</th>
                                <th>Serviço</th>
                                <th>Status</th>
                                <th>Usuário</th>
                                <th>Empresa</th>
                                <th>Status</th>
                                <th>Data Despacho</th>
                                <th>Data Atribuído</th>
                                <th>Data Conclusão</th>
                                <th>Parado</th>
                                <th>Conclusão</th>
                                <th>Ent Manual</th>
                                <th>Conf Prod</th>
                                <th>#</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($lists->Productions as $p)
                                <tr wire:key="prod-{{ $p->id }}">
                                    <td>
                                        @if ($p->d5)
                                            <span class="badge text-bg-info">RI</span>
                                        @endif
                                        @if ($p->dfive)
                                            <span class="badge text-bg-primary">D5</span>
                                        @endif
                                        @if ($p->partial)
                                            <span class="badge text-bg-warning">P</span>
                                        @endif
                                    </td>
                                    <td>{{ $p->Service?->service ?? 'Desconhecido' }}</td>
                                    <td>{{ $p->status_note }}</td>
                                    <td>
                                        @if ($p->User?->email)
                                            <i class="bx bxl-microsoft-teams text-primary fs-4 align-middle"
                                                style="cursor:pointer"
                                                onclick="window.open('msteams://teams.microsoft.com/l/chat/0/0?users={{ $p->User?->email }}', '_blank')"></i>
                                        @endif
                                        {{ $p->User?->name ?? 'Desconhecido' }}
                                    </td>
                                    <td>{{ $p->Company?->name ?? 'Desconhecido' }}</td>
                                    <td>
                                        <span class="badge {{ \App\Custom\Notestatus::status($p->status)->colorbg }}"
                                            style="cursor:pointer;"
                                            wire:click.prevent="$emitTo('components.status.show-status','showStatus', {{ $p->id }}, {{ $p->status }})">
                                            {{ \App\Custom\Notestatus::status($p->status)->status }}
                                        </span>
                                    </td>
                                    <td>{{ $p->dispatch_at ? date('d/m/Y H:i:s', strtotime($p->dispatch_at)) : '-' }}
                                    </td>
                                    <td>{{ $p->att_at ? date('d/m/Y H:i:s', strtotime($p->att_at)) : '-' }}
                                    </td>
                                    <td>{{ $p->completed_at ? date('d/m/Y H:i:s', strtotime($p->completed_at)) : '-' }}
                                    </td>
                                    <td>{{ \Carbon\CarbonInterval::seconds((int) $p->stopped)->cascade()->forHumans(['short' => true]) }}
                                    </td>
                                    <td>
                                        @livewire('components.historic.analises', ['production_id' => $p->id], key('hist-' . $p->id))
                                    </td>
                                    <td>{{ $p->manual ? 'SIM' : 'NÃO' }}</td>
                                    <td>{{ $p->confirmed ? 'SIM' : 'NÃO' }}</td>
                                    <td class="text-center">
                                        @livewire('production.actions.geralreattribute', ['production' => $p, 'chave' => hash('sha512', $p->id)], key('reatt-' . $p->id))
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @else
            <div class="card border-0 mt-3 shadow">
                <div class="card-body">
                    <h6 class="text-center text-muted">
                        SEM INFORMAÇÃO DE ATIVIDADES EM PROJETOS NA NOTA/OV
                    </h6>
                </div>
            </div>
        @endif

        {{-- CONTRATAÇÃO --}}
        @if ($lists->Viabilities->count())
            <div class="card border-0 mt-3 shadow">
                <div class="card-header rs-head-unified">
                    <h5 class="rs-section-title">CONTRATAÇÃO</h5>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm table-striped mb-0">
                        <thead class="table-dark">
                            <tr>
                                <th>#</th>
                                <th>Ordem</th>
                                <th>Contratante</th>
                                <th>Contratado</th>
                                <th>Tácitamente</th>
                                <th>Dt Contratação</th>
                                <th>Dt Envio</th>
                                <th>Dt Retorno</th>
                                <th>Responsável</th>
                                <th>Empreiteira</th>
                                <th>Resp Informe</th>
                                <th>Viabilidade</th>
                                <th>Ação</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($lists->Viabilities as $v)
                                @php
                                    $formApproved = (bool) ($v->Form?->approved ?? false);
                                    $formRejected = (bool) ($v->Form?->rejected ?? false);
                                    $viabilityApproved = (bool) ($v->approved ?? false);
                                    $viabilityRejected = (bool) ($v->rejected ?? false);

                                    $approved = $formApproved || $viabilityApproved;
                                    $rejected = $formRejected || $viabilityRejected;

                                    $viabilityResultLabel = 'Em Análise';
                                    $viabilityResultClass = 'bg-secondary';
                                    if ($approved) {
                                        $viabilityResultLabel = 'Aprovada';
                                        $viabilityResultClass = 'bg-success';
                                    } elseif ($rejected) {
                                        $viabilityResultLabel = 'Reprovada';
                                        $viabilityResultClass = 'bg-danger';
                                    }
                                @endphp
                                <tr>
                                    <td></td>
                                    <td class="align-middle">
                                        @foreach ($v->Orders as $o)
                                            @php $op = $o->Operations->where('operacao','0010')->first(); @endphp
                                            <p
                                                class="my-0 {{ $op && !\Illuminate\Support\Str::startsWith($op->status, 'CONF') && $v?->hired_at?->lt(now()->subHours(24)) ? 'text-danger' : '' }}">
                                                {{ $o->ordem }}
                                                @if ($op && !\Illuminate\Support\Str::startsWith($op->status, 'CONF'))
                                                    <i class="ri-alert-line"></i>
                                                @endif
                                            </p>
                                        @endforeach
                                    </td>
                                    <td class="align-middle">
                                        @if ($v->User?->email)
                                            <i class="bx bxl-microsoft-teams text-primary fs-4 align-middle"
                                                style="cursor:pointer"
                                                onclick="window.open('msteams://teams.microsoft.com/l/chat/0/0?users={{ $v->User?->email }}', '_blank')"></i>
                                        @endif
                                        {{ $v->User?->name }}
                                    </td>
                                    <td class="align-middle">{{ $v->hired ? 'SIM' : 'NÃO' }}</td>
                                    <td class="align-middle">{{ $v->tacit ? 'SIM' : 'NÃO' }}</td>
                                    <td class="align-middle">
                                        {{ $v->hired_at ? date('d/m/Y H:i:s', strtotime($v->hired_at)) : '---' }}
                                    </td>
                                    <td class="align-middle">
                                        {{ $v->sended_at ? date('d/m/Y H:i:s', strtotime($v->sended_at)) : '---' }}
                                    </td>
                                    <td class="align-middle">
                                        {{ $v->returned_at ? date('d/m/Y H:i:s', strtotime($v->returned_at)) : '---' }}
                                    </td>
                                    <td class="align-middle">{{ $v->Engineer->name ?? '---' }}</td>
                                    <td class="align-middle">{{ $v->Company->name ?? '---' }}</td>
                                    <td class="align-middle">{{ $v->Form?->responsible ?? '---' }}</td>
                                    <td class="align-middle">
                                        <span class="badge {{ $viabilityResultClass }}">{{ $viabilityResultLabel }}</span>
                                    </td>
                                    <td class="align-middle">
                                        <button type="button" class="btn btn-sm btn-outline-primary rs-viab-view-btn"
                                            data-bs-toggle="modal" data-bs-target="#viabilityDetailModal-{{ $v->id }}">
                                            <i class="ri-eye-line me-1"></i> Ver
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            @foreach ($lists->Viabilities as $v)
                @php
                    $formApproved = (bool) ($v->Form?->approved ?? false);
                    $formRejected = (bool) ($v->Form?->rejected ?? false);
                    $viabilityApproved = (bool) ($v->approved ?? false);
                    $viabilityRejected = (bool) ($v->rejected ?? false);
                    $approved = $formApproved || $viabilityApproved;
                    $rejected = $formRejected || $viabilityRejected;

                    $viabilityResultLabel = 'Em Análise';
                    $viabilityResultClass = 'bg-secondary';
                    if ($approved) {
                        $viabilityResultLabel = 'Aprovada';
                        $viabilityResultClass = 'bg-success';
                    } elseif ($rejected) {
                        $viabilityResultLabel = 'Reprovada';
                        $viabilityResultClass = 'bg-danger';
                    }

                    $statusIndex = (int) ($v->status ?? 0);
                    if ($statusIndex < 0 || $statusIndex > 16) {
                        $statusIndex = 0;
                    }
                    $statusMeta = \App\Custom\Viabilitiesstatus::status($statusIndex);
                    $mappedStatusLabel = $statusMeta->status ?? 'Sem Status';
                    $mappedStatusClass = $statusMeta->colorbg ?? 'text-bg-secondary';
                    $mappedStatusIcon = $statusMeta->icon ?? 'ri-information-line';

                    $formFiles = $v->Form?->Files ?? collect();
                    $viabilityFiles = $v->Files ?? collect();
                    $allFiles = $formFiles->merge($viabilityFiles)->unique('id')->values();
                    $formFileIds = $formFiles->pluck('id')->all();
                @endphp

                <div class="modal fade rs-viab-modal" id="viabilityDetailModal-{{ $v->id }}" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-xl modal-dialog-scrollable">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">
                                    Nota/OV {{ $lists->note }} · Resultado <span class="badge {{ $viabilityResultClass }} ms-2">{{ $viabilityResultLabel }}</span>
                                </h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fechar"></button>
                            </div>
                            <div class="modal-body">
                                <div class="rs-viab-hero">
                                    <div class="rs-viab-stat is-result">
                                        <span class="label">Resultado da Viabilidade</span>
                                        <div class="value">
                                            <span class="badge {{ $viabilityResultClass }} rs-result-pill">{{ $viabilityResultLabel }}</span>
                                            <div class="small mt-1">Registro #{{ $v->id }}</div>
                                        </div>
                                    </div>
                                    <div class="rs-viab-stat">
                                        <span class="label">Status do Fluxo</span>
                                        <div class="value">
                                            <span class="badge {{ $mappedStatusClass }}">
                                                <i class="{{ $mappedStatusIcon }} me-1"></i>{{ $mappedStatusLabel }}
                                            </span>
                                            <div class="small text-muted mt-1">Código: {{ $statusIndex }}</div>
                                        </div>
                                    </div>
                                    <div class="rs-viab-stat">
                                        <span class="label">Responsável</span>
                                        <div class="value">{{ $v->Engineer->name ?? '---' }}</div>
                                    </div>
                                    <div class="rs-viab-stat">
                                        <span class="label">Empreiteira</span>
                                        <div class="value">{{ $v->Company->name ?? '---' }}</div>
                                    </div>
                                </div>

                                <div class="row g-3 mb-3">
                                    <div class="col-md-4">
                                        <div class="rs-viab-block">
                                            <h6>Resumo Executivo</h6>
                                            <div class="rs-kv">
                                                <div class="k">Contratada</div>
                                                <div class="v">{{ $v->hired ? 'SIM' : 'NÃO' }}</div>
                                                <div class="k">Tácita</div>
                                                <div class="v">{{ $v->tacit ? 'SIM' : 'NÃO' }}</div>
                                                <div class="k">Concluída</div>
                                                <div class="v">{{ $v->completed ? 'SIM' : 'NÃO' }}</div>
                                                <div class="k">Cancelada</div>
                                                <div class="v">{{ $v->canceled ? 'SIM' : 'NÃO' }}</div>
                                                <div class="k">Valor</div>
                                                <div class="v">{{ isset($v->value) ? number_format((float) $v->value, 2, ',', '.') : '---' }}</div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="rs-viab-block">
                                            <h6>Responsáveis</h6>
                                            <div class="rs-kv">
                                                <div class="k">Contratante</div>
                                                <div class="v">{{ $v->User->name ?? '---' }}</div>
                                                <div class="k">Responsável</div>
                                                <div class="v">{{ $v->Engineer->name ?? '---' }}</div>
                                                <div class="k">Empreiteira</div>
                                                <div class="v">{{ $v->Company->name ?? '---' }}</div>
                                                <div class="k">Resp. informe</div>
                                                <div class="v">{{ $v->Form?->responsible ?? '---' }}</div>
                                                <div class="k">Usuário do form</div>
                                                <div class="v">{{ $v->Form?->User?->name ?? '---' }}</div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="rs-viab-block">
                                            <h6>Datas</h6>
                                            <div class="rs-kv">
                                                <div class="k">Início</div>
                                                <div class="v">{{ optional($v->init_at)->format('d/m/Y H:i') ?: '---' }}</div>
                                                <div class="k">Envio</div>
                                                <div class="v">{{ optional($v->sended_at)->format('d/m/Y H:i') ?: '---' }}</div>
                                                <div class="k">Retorno</div>
                                                <div class="v">{{ optional($v->returned_at)->format('d/m/Y H:i') ?: '---' }}</div>
                                                <div class="k">Decisão Eng.</div>
                                                <div class="v">{{ optional($v->engineer_at)->format('d/m/Y H:i') ?: '---' }}</div>
                                                <div class="k">Contratação</div>
                                                <div class="v">{{ optional($v->hired_at)->format('d/m/Y H:i') ?: '---' }}</div>
                                                <div class="k">Conclusão</div>
                                                <div class="v">{{ optional($v->completed_at)->format('d/m/Y H:i') ?: '---' }}</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="row g-3 mb-3">
                                    <div class="col-md-6">
                                        <div class="rs-viab-block">
                                            <h6>Ordens Relacionadas</h6>
                                            @if ($v->Orders->count())
                                                @foreach ($v->Orders as $o)
                                                    <span class="badge bg-light text-dark border me-1 mb-1">{{ $o->ordem }}</span>
                                                @endforeach
                                            @else
                                                <span class="text-muted">Sem ordens vinculadas.</span>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="rs-viab-block">
                                            <h6>Campos do Formulário</h6>
                                            <div class="rs-kv">
                                                <div class="k">Aprovado (form)</div>
                                                <div class="v">{{ $v->Form?->approved ? 'SIM' : 'NÃO' }}</div>
                                                <div class="k">Rejeitado (form)</div>
                                                <div class="v">{{ $v->Form?->rejected ? 'SIM' : 'NÃO' }}</div>
                                                <div class="k">Alterações</div>
                                                <div class="v">{{ $v->Form?->changes ? 'SIM' : 'NÃO' }}</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="row g-3 mb-3">
                                    <div class="col-md-4">
                                        <div class="rs-viab-block is-reason">
                                            <h6>Motivo</h6>
                                            <div class="rs-viab-text">{{ $v->Form?->reason ?: 'Sem motivo informado.' }}</div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="rs-viab-block is-description">
                                            <h6>Resultado / Descrição</h6>
                                            <div class="rs-viab-text">{{ $v->Form?->description ?: 'Sem descrição informada.' }}</div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="rs-viab-block">
                                            <h6>Histórico</h6>
                                            <div class="rs-viab-text">{{ $v->Form?->historic ?: 'Sem histórico informado.' }}</div>
                                        </div>
                                    </div>
                                </div>

                                <div class="rs-viab-block">
                                    <h6>Arquivos Associados à Viabilidade</h6>
                                    @if ($allFiles->isNotEmpty())
                                        <div class="table-responsive">
                                            <table class="table table-sm table-striped mb-0">
                                                <thead>
                                                    <tr>
                                                        <th>Arquivo</th>
                                                        <th>Origem</th>
                                                        <th>Ext.</th>
                                                        <th>Enviado por</th>
                                                        <th>Data</th>
                                                        <th>Ação</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach ($allFiles as $file)
                                                        <tr>
                                                            <td>{{ $file->original_name ?? $file->file_name }}</td>
                                                            <td>{{ in_array($file->id, $formFileIds, true) ? 'Formulário' : 'Viabilidade' }}</td>
                                                            <td>{{ strtoupper($file->ext ?? '-') }}</td>
                                                            <td>{{ $file->User->name ?? '---' }}</td>
                                                            <td>{{ optional($file->created_at)->format('d/m/Y H:i') ?: '---' }}</td>
                                                            <td>
                                                                <a href="{{ route('files.download', ['file' => $file->id]) }}"
                                                                    class="btn btn-sm btn-outline-primary">
                                                                    Baixar
                                                                </a>
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    @else
                                        <div class="text-muted">Nenhum arquivo associado a esta viabilidade.</div>
                                    @endif
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        @else
            <div class="card border-0 mt-3 shadow">
                <div class="card-body">
                    <h6 class="text-center text-muted">
                        SEM INFORMAÇÃO DE CONTRATAÇÃO NA NOTA/OV
                    </h6>
                </div>
            </div>
        @endif

        @php
            $workForm = $lists->WorkForm ?: $lists->WorkFormAny;
            $workFormCanceled = (bool) ($workForm?->canceled);
        @endphp

        {{-- INFORMES DE OBRA (Parciais, Ramal, Work) --}}
        @if ($workForm || $lists->RamalForm || $lists->Partials->isNotEmpty())
            <div class="card border-0 mt-3 shadow">
                <div class="card-header rs-head-unified">
                    <h5 class="rs-section-title">INFORMES DE OBRA</h5>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm table-striped mb-0">
                        <thead class="table-dark">
                            <tr>
                                <th class="text-center">Tipo</th>
                                <th class="text-center">Ordens</th>
                                <th class="text-center">Empresa</th>
                                <th class="text-center">Equipamentos</th>
                                <th class="text-center">Alteração</th>
                                <th class="text-center">Equipe WPA</th>
                                <th class="text-center">Responsável</th>
                                <th class="text-center">Conclusão Informada</th>
                                <th class="text-center">Primeira Entrega</th>
                                <th class="text-center">Rejeições</th>
                                <th class="text-center">Última Devolução</th>
                                <th class="text-center">Status Atual</th>
                                <th class="text-center">ADS</th>
                                <th class="text-center">Aceite Usuario</th>
                                <th class="text-center">Entregue Em</th>
                            </tr>
                        </thead>
                        <tbody>
                            {{-- Parciais --}}
                            @if ($lists->Partials->count())
                                @foreach ($lists->Partials as $partial)
                                    <tr wire:key="partial-{{ $partial->id }}"
                                        wire:dblclick="$emitTo('partner.show.show-partial-info','show_form',{{ $partial->id }})">
                                        <td class="text-center text-bg-warning align-middle">PARCIAL</td>
                                        <td class="text-center align-middle">
                                            @foreach ($partial->Orders as $o)
                                                <p class="my-0">{{ $o->ordem }}</p>
                                            @endforeach
                                        </td>
                                        <td class="text-center align-middle">{{ $partial->Company->name }}</td>
                                        <td class="text-center align-middle">---</td>
                                        <td class="text-center align-middle">---</td>
                                        <td class="text-center align-middle">---</td>
                                        <td class="text-center align-middle">
                                            {{ $partial->responsible ?? 'Desconhecido' }}
                                        </td>
                                        <td class="text-center align-middle">---</td>
                                        <td class="text-center align-middle">
                                            {{ $partial->created_at ? date('d/m/Y', strtotime($partial->created_at)) : 'Desconhecido' }}
                                        </td>
                                        <td class="text-center align-middle">---</td>
                                        <td class="text-center align-middle">---</td>
                                        <td class="text-center align-middle">
                                            @if ($partial->deny)
                                                <span class="badge bg-warning">REJEITADO</span>
                                            @elseif($partial->allow && !$partial->supervision)
                                                <span class="badge bg-warning">EM FISCALIZAÇÃO</span>
                                            @elseif($partial->allow && $partial->supervision && !$partial->payment)
                                                <span class="badge bg-warning">EM PAGAMENTO</span>
                                            @elseif($partial->allow && $partial->complete)
                                                <span class="badge bg-warning">PAGO</span>
                                            @else
                                                <span class="badge bg-secondary text-white">DESCONHECIDO</span>
                                            @endif
                                        </td>
                                        <td class="text-center align-middle">---</td>
                                        <td class="text-center align-middle">---</td>
                                        <td class="text-center align-middle">
                                            {{ $partial->created_at ? date('d/m/Y', strtotime($partial->created_at)) : 'Desconhecido' }}
                                        </td>
                                    </tr>
                                @endforeach
                            @endif

                            {{-- RAMAL --}}
                            @if ($lists->RamalForm)
                                <tr wire:key="ramal-{{ $lists->RamalForm?->id }}"
                                    wire:dblclick="$emitTo('btzero.view.compare-form','showCompareForm',{{ $lists->id }})">
                                    <td class="text-center text-bg-success align-middle">SMC</td>
                                    <td class="text-center align-middle">
                                        @foreach ($lists->RamalForm?->Orders as $o)
                                            <p class="my-0">{{ $o->ordem }}</p>
                                        @endforeach
                                    </td>
                                    <td class="text-center align-middle">{{ $lists->RamalForm?->Company->name }}</td>
                                    <td class="text-center align-middle">
                                        {!! $lists->RamalForm?->BtzeroEquipment->count()
                                            ? "<span class='badge bg-dark text-white'>" . $lists->RamalForm?->BtzeroEquipment->count() . '</span>'
                                            : '' !!}
                                    </td>
                                    <td class="text-center align-middle">---</td>
                                    <td class="text-center align-middle">---</td>
                                    <td class="text-center align-middle">
                                        {{ $lists->RamalForm?->User->name ?? 'Desconhecido' }}
                                    </td>
                                    <td class="text-center align-middle">---</td>
                                    <td class="text-center align-middle">
                                        {{ $lists->RamalForm?->created_at ? date('d/m/Y', strtotime($lists->RamalForm?->created_at)) : 'Desconhecido' }}
                                    </td>
                                    <td class="text-center align-middle">
                                        @if ($lists->RamalForm?->ReturnRamal?->count())
                                            <span class="badge bg-warning fw-bold" style="cursor:pointer;"
                                                wire:click.prevent="$emitTo('components.workform.view-reason-return', 'workReturnViews', {{ $lists->id }})">
                                                {{ $lists->RamalForm?->ReturnRamal?->count() }}
                                            </span>
                                        @else
                                            ---
                                        @endif
                                    </td>
                                    <td class="text-center align-middle">
                                        {{ $lists->RamalForm?->ReturnRamal?->last()?->created_at
                                            ? date('d/m/Y', strtotime($lists->RamalForm?->ReturnRamal?->last()?->created_at))
                                            : '---' }}
                                    </td>
                                    <td class="text-center align-middle">
                                        <span
                                            class="badge {{ $lists->RamalForm?->rejected ? 'bg-warning text-wrap' : 'bg-primary text-wrap' }}">
                                            {{ $lists->RamalForm?->rejected ? 'Informe em Revisão' : 'Normal' }}
                                        </span>
                                    </td>
                                    <td class="text-center align-middle">---</td>
                                    <td class="text-center align-middle">---</td>
                                    <td class="text-center align-middle">
                                        {{ $lists->RamalForm?->created_at ? date('d/m/Y', strtotime($lists->RamalForm?->created_at)) : 'Desconhecido' }}
                                    </td>
                                </tr>
                            @endif

                            {{-- WORK FORM --}}
                            @if ($workForm)
                                <tr wire:key="work-{{ $workForm->id }}"
                                    @unless($workFormCanceled)
                                        wire:dblclick="$emitTo('partner.show.show-work-form','show_form',{{ $workForm->id }})"
                                    @endunless
                                    class="{{ $workFormCanceled ? 'rs-workform-canceled-row' : '' }}">
                                    <td class="text-center {{ $workFormCanceled ? 'bg-danger text-white' : 'bg-primary text-white' }} align-middle">
                                        FINAL
                                    </td>
                                    <td class="text-center align-middle">
                                        @foreach ($workForm->Orders as $o)
                                            <p class="my-0">{{ $o->ordem }}</p>
                                        @endforeach
                                    </td>
                                    <td class="text-center align-middle">{{ $workForm->Company->name ?? '---' }}</td>
                                    <td class="text-center align-middle">
                                        {!! $workForm->Equipment->count()
                                            ? "<span class='badge bg-dark text-white'>" . $workForm?->Equipment?->count() . '</span>'
                                            : '' !!}
                                    </td>
                                    <td class="text-center align-middle">
                                        {{ $workForm->changes ? 'SIM' : 'NÃO' }}
                                    </td>
                                    <td class="text-center align-middle">
                                        {{ $workForm->team ?? 'Desconhecido' }}
                                    </td>
                                    <td class="text-center align-middle">
                                        {{ $workForm->responsible ?? 'Desconhecido' }}
                                    </td>
                                    <td class="text-center align-middle">
                                        {{ $workForm->date ? date('d/m/Y', strtotime($workForm?->date)) : 'Desconhecido' }}
                                    </td>
                                    <td class="text-center align-middle">
                                        {{ $workForm->created_at ? date('d/m/Y', strtotime($workForm?->created_at)) : 'Desconhecido' }}
                                    </td>
                                    <td class="text-center align-middle">
                                        @if ($workForm->Returnwork->count())
                                            <span class="badge bg-warning fw-bold" style="cursor:pointer;"
                                                wire:click.prevent="$emitTo('components.workform.view-reason-return', 'workReturnViews', {{ $workForm->id }})">
                                                {{ $workForm?->Returnwork?->count() }}
                                            </span>
                                        @else
                                            ---
                                        @endif
                                    </td>
                                    <td class="text-center align-middle">
                                        {{ $workForm?->Returnwork?->last()?->created_at
                                            ? date('d/m/Y', strtotime($workForm?->Returnwork?->last()?->created_at))
                                            : '---' }}
                                    </td>
                                    <td class="text-center align-middle">
                                        <span
                                            class="badge {{ $workFormCanceled ? 'bg-danger text-wrap' : ($workForm?->rejected ? 'bg-warning text-wrap' : 'bg-primary text-wrap') }}">
                                            {{ $workFormCanceled ? 'Cancelado Sistemicamente' : ($workForm->rejected ? 'Informe em Revisão' : 'Normal') }}
                                        </span>
                                    </td>
                                    <td class="text-center align-middle">
                                        @php
                                            $workAds = $workForm->Adsform;
                                        @endphp
                                        @if ($workAds)
                                            @if ($workAds->tacit)
                                                <div class="d-grid gap-1">
                                                    <span class="badge text-bg-dark">ADS TÁCITA</span>
                                                    <span class="badge {{ $workAds->tacit_delivered_at ? 'bg-success' : 'bg-danger' }}">
                                                        {{ $workAds->tacit_delivered_at ? 'ENTREGUE' : 'NÃO ENTREGUE' }}
                                                    </span>
                                                </div>
                                            @else
                                                <span class="badge bg-success">ADS NORMAL</span>
                                            @endif
                                        @else
                                            <span class="badge bg-secondary">SEM ADS</span>
                                        @endif
                                    </td>
                                    <td class="text-center align-middle">
                                        @if ($workForm->acceptance_accepted)
                                            <div class="d-grid gap-1">
                                                <span class="badge text-bg-success">ACEITO</span>
                                                <button class="btn btn-outline-success btn-sm"
                                                    wire:click.prevent="$emitTo('components.workform.acceptance-info', 'openAcceptanceInfo', {{ $workForm->id }})">
                                                    Ver aceite
                                                </button>
                                            </div>
                                        @elseif($workForm->acceptance_name || $workForm->acceptance_at || $workForm->acceptance_meta)
                                            <div class="d-grid gap-1">
                                                <span class="badge text-bg-warning">PENDENTE</span>
                                                <button class="btn btn-outline-warning btn-sm"
                                                    wire:click.prevent="$emitTo('components.workform.acceptance-info', 'openAcceptanceInfo', {{ $workForm->id }})">
                                                    Ver aceite
                                                </button>
                                            </div>
                                        @else
                                            <span class="badge bg-secondary">SEM ACEITE</span>
                                        @endif
                                    </td>
                                    <td class="text-center align-middle">
                                        {{ $workForm?->informed_at ? date('d/m/Y', strtotime($workForm?->informed_at)) : 'Desconhecido' }}
                                    </td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
        @else
            <div class="card border-0 mt-3 shadow">
                <div class="card-body">
                    <h6 class="text-center text-muted">SEM INFORME DE OBRA</h6>
                </div>
            </div>
        @endif
        @else
            <div class="card border-0 mt-4 shadow">
                <div class="card-body">
                    <h6 class="text-center text-muted">NADA PARA EXIBIR</h6>
                </div>
            </div>
        @endif
    </div>

    {{-- Modals --}}
    @livewire('partner.show.show-work-form', key('FormModalShow'))
    @livewire('components.status.show-status', key('show_status_note'))
    @livewire('files.manager.fileedit', key('file-edit'))
    @livewire('files.manager.createfiles', key('create-files'))
    @livewire('btzero.view.compare-form', key('compare_form'))
    @livewire('partner.show.show-partial-info', key('partial_info'))
    @livewire('components.workform.view-reason-return', key('WorkReturnsReason'))
    @livewire('components.workform.acceptance-info', key('WorkAcceptanceInfo'))
    @livewire('components.ramalform.view-reason-return', key('RamalReturnsReason'))
    @livewire('components.d5.d5details', key('view_d5_details'))
</div>
