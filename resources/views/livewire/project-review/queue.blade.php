<div class="oexterno-page">
    <x-show-loading />

    <style>
        .oexterno-page {
            --oe-bg: #f6f7fb;
            --oe-surface: #ffffff;
            --oe-ink: #1f2933;
            --oe-muted: #6b7280;
            --oe-accent: #0f766e;
            --oe-border: #e5e7eb;
            background: radial-gradient(circle at 10% 0%, #eef2ff, transparent 40%), radial-gradient(circle at 90% 10%, #ecfeff, transparent 35%), var(--oe-bg);
            padding: 1.5rem 0;
        }

        .oexterno-header {
            background: linear-gradient(120deg, #0f172a, #0f766e 70%);
            color: #f8fafc;
            border-radius: 1rem;
            padding: 1.5rem 2rem;
            box-shadow: 0 16px 40px rgba(15, 23, 42, 0.2);
            margin-bottom: 1.5rem;
        }

        .filter-card {
            background-color: #f8fafc;
            border: 1px solid #cbd5e1;
            border-radius: 0.75rem;
            padding: 1rem 1.25rem;
            height: 100%;
            box-shadow: 0 8px 18px rgba(15, 23, 42, 0.12);
        }

        .filter-card .form-label {
            color: #0f172a;
            font-weight: 700;
            margin-bottom: .4rem;
        }

        .filter-card .form-select,
        .filter-card .form-control {
            color: #0f172a;
            border-color: #cbd5e1;
            background: #fff;
        }

        .table-card {
            background: var(--oe-surface);
            border: 1px solid var(--oe-border);
            border-radius: 0;
            box-shadow: 0 16px 32px rgba(15, 23, 42, 0.08);
            overflow: hidden;
        }

        .table-card .table thead th {
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            white-space: nowrap;
        }

        .card-soft {
            background: var(--oe-surface);
            border: 1px solid var(--oe-border);
            border-radius: .85rem;
            box-shadow: 0 12px 24px rgba(15, 23, 42, .06);
        }

        .analysis-items-scroll {
            max-height: 360px;
            overflow: auto;
        }

        .analysis-findings-scroll {
            max-height: 620px;
            overflow: auto;
            padding-right: 2px;
        }

        .step-title {
            display: flex;
            align-items: center;
            gap: .5rem;
            font-weight: 700;
            margin-bottom: .35rem;
        }

        .step-badge {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: #0f766e;
            color: #fff;
            font-size: .85rem;
        }

        .step-help {
            color: var(--oe-muted);
            font-size: .85rem;
            margin-bottom: .75rem;
        }

        .summary-pill {
            border: 1px solid var(--oe-border);
            border-radius: .75rem;
            padding: .5rem .75rem;
            background: #fff;
            font-size: .85rem;
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

        .group-head-btn {
            font-weight: 600;
            color: #0f172a;
        }

        .group-head-btn:hover {
            color: #0f766e;
        }

        .project-review-modal-body {
            padding: 1.25rem 1.25rem 1.75rem 1.25rem;
        }

        @media (min-width: 992px) {
            .project-review-modal-body {
                padding: 1.5rem 1.75rem 2rem 1.75rem;
            }
        }
    </style>

    <div class="container-fluid">
        <div class="oexterno-header d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3">
            <div>
                <h2 class="mb-0">ANÁLISE PROJETO</h2>
                <div>Lista para analisar</div>
            </div>
            <div class="col-12 col-lg-6">
                <div class="row g-2">
                    <div class="col-12 col-md-5">
                        <div class="filter-card">
                        <label class="form-label">Empresa</label>
                        <select class="form-select" wire:model="company_id">
                            <option value="">Todas</option>
                            @foreach ($companies as $company)
                                <option value="{{ $company->id }}">{{ $company->name }}</option>
                            @endforeach
                        </select>
                        </div>
                    </div>
                    <div class="col-12 col-md-7">
                        <div class="filter-card">
                        <label class="form-label">Buscar</label>
                        <input type="text" class="form-control" wire:model.debounce.500ms="search" placeholder="Nota, pedido, descrição...">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="table-card">
            <div class="card-header text-bg-dark fw-bold">Atividades > Análise de Projeto</div>
            <div class="card-body border-bottom">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="select-page" wire:model="selectPage">
                        <label class="form-check-label" for="select-page">
                            Selecionar todos da página
                        </label>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <button class="btn btn-sm btn-outline-secondary" wire:click="exportList">
                            Exportar
                        </button>
                        <span class="small text-muted">{{ count($selectedProductionIds) }} selecionada(s)</span>
                        <button class="btn btn-sm btn-success" wire:click="approveSelected"
                            @disabled(!count($selectedProductionIds))>
                            Aprovar em massa
                        </button>
                    </div>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 38px;"></th>
                            <th>Nota</th>
                            <th>Desenhista</th>
                            <th>Empresa</th>
                            <th>Ordens</th>
                            <th>Custo total</th>
                            <th>Custo empresa</th>
                            <th>Custo cliente</th>
                            <th>Status</th>
                            <th>Tipo</th>
                            <th>Quando foi enviado</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($lists as $prod)
                            @php
                                $cycle = collect($prod->ProjectReviewCycles)->sortByDesc('round_number')->first();
                                $orders = $cycle?->Orders ?? collect();
                                if ($orders->isEmpty()) {
                                    $orders = $prod->ProjectReviewCycles->first(function ($c) {
                                        return $c->Orders->count() > 0;
                                    })?->Orders ?? collect();
                                }
                            @endphp
                            <tr>
                                <td>
                                    <input type="checkbox" class="form-check-input"
                                        wire:model="selectedProductionIds" value="{{ $prod->id }}">
                                </td>
                                <td>{{ $prod->Note->note ?? '---' }}</td>
                                <td>{{ $prod->User->name ?? '---' }}</td>
                                <td>{{ $prod->Company->name ?? '---' }}</td>
                                <td class="align-top">
                                    @if ($orders->count())
                                        <div class="d-flex flex-column gap-1">
                                            @foreach ($orders as $ord)
                                                <div class="small border px-2 py-1"><strong>{{ $ord->order_number }}</strong></div>
                                            @endforeach
                                        </div>
                                    @else
                                        ---
                                    @endif
                                </td>
                                <td class="align-top">
                                    @if ($orders->count())
                                        <div class="d-flex flex-column gap-1">
                                            @foreach ($orders as $ord)
                                                <div class="small border px-2 py-1">{{ number_format((float) $ord->total_cost, 2, ',', '.') }}</div>
                                            @endforeach
                                        </div>
                                    @else
                                        ---
                                    @endif
                                </td>
                                <td class="align-top">
                                    @if ($orders->count())
                                        <div class="d-flex flex-column gap-1">
                                            @foreach ($orders as $ord)
                                                <div class="small border px-2 py-1">{{ number_format((float) $ord->company_cost, 2, ',', '.') }}</div>
                                            @endforeach
                                        </div>
                                    @else
                                        ---
                                    @endif
                                </td>
                                <td class="align-top">
                                    @if ($orders->count())
                                        <div class="d-flex flex-column gap-1">
                                            @foreach ($orders as $ord)
                                                <div class="small border px-2 py-1">{{ number_format((float) $ord->client_cost, 2, ',', '.') }}</div>
                                            @endforeach
                                        </div>
                                    @else
                                        ---
                                    @endif
                                </td>
                                <td>
                                    @php
                                        $status = \App\Custom\Notestatus::status($prod->status);
                                        $latestRound = (int) ($prod->latest_round_number ?? ($cycle?->round_number ?? 1));
                                        $rejectedCount = (int) ($prod->rejected_cycles_count ?? 0);
                                        $rejectedTimelineCount = (int) ($prod->rejected_status_timeline_count ?? 0);
                                        $isReturnToReview = ($latestRound > 1)
                                            || ($rejectedCount > 0)
                                            || ($rejectedTimelineCount > 0)
                                            || collect($prod->ProjectReviewCycles)->contains(fn ($c) => $c->decision === 'REJECTED');
                                    @endphp
                                    <span class="badge {{ $status->colorbg }}">{{ $status->status }}</span>
                                </td>
                                <td>
                                    @if ($isReturnToReview)
                                        <span class="badge text-bg-warning">Retorno</span>
                                    @else
                                        <span class="badge text-bg-secondary">Inicial</span>
                                    @endif
                                </td>
                                <td>{{ $cycle?->submitted_at ? date('d/m/Y H:i', strtotime($cycle->submitted_at)) : '---' }}</td>
                                <td><button class="btn btn-sm btn-outline-primary" wire:click="openReview({{ $prod->id }})">Abrir</button></td>
                            </tr>
                        @empty
                            <tr><td colspan="12" class="text-center text-muted py-4">Nenhum registro encontrado.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="card-body">{{ $lists->links() }}</div>
        </div>
    </div>

    <div wire:ignore.self class="modal fade" id="projectReviewModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-fullscreen">
            <div class="modal-content border-0">
                <div class="modal-header text-bg-dark">
                    <h5 class="modal-title">Análise de Projeto - {{ $selectedProduction?->Note?->note }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body oexterno-page project-review-modal-body">
                    @if ($selectedCycle)
                        <div class="row g-3">
                            <div class="col-lg-5">
                                <div class="step-title"><span class="step-badge">1</span>Contexto da Produção</div>
                                <div class="step-help">Dados de referência para validar a análise e baixar os arquivos do projeto.</div>
                                <div class="card card-soft mb-3">
                                    <div class="card-header">Informações da Nota</div>
                                    <div class="card-body small">
                                        <div><strong>Nota:</strong> {{ $selectedProduction?->Note?->note ?? '---' }}</div>
                                        <div><strong>Desenhista:</strong> {{ $selectedProduction?->User?->name ?? '---' }}</div>
                                        <div><strong>Empresa:</strong> {{ $selectedProduction?->Company?->name ?? '---' }}</div>
                                        <div><strong>Serviço:</strong> {{ $selectedProduction?->Service?->service ?? '---' }}</div>
                                        <div><strong>Rodada:</strong> {{ $selectedCycle->round_number }}</div>
                                        <div><strong>Enviado em:</strong> {{ $selectedCycle->submitted_at ? date('d/m/Y H:i', strtotime($selectedCycle->submitted_at)) : '---' }}</div>
                                    </div>
                                </div>

                                <div class="card card-soft mb-3">
                                    <div class="card-header">Ordens e Valores</div>
                                    <div class="card-body small">
                                        @php
                                            $cyclesAsc = ($selectedProduction?->ProjectReviewCycles ?? collect())
                                                ->sortBy('round_number')
                                                ->values();

                                            $orderHistory = collect();
                                            foreach ($cyclesAsc as $cycleRow) {
                                                foreach (($cycleRow->Orders ?? collect()) as $ordRow) {
                                                    $orderHistory->push([
                                                        'round' => (int) $cycleRow->round_number,
                                                        'submitted_at' => $cycleRow->submitted_at,
                                                        'order_number' => (string) $ordRow->order_number,
                                                        'total_cost' => (float) $ordRow->total_cost,
                                                        'company_cost' => (float) $ordRow->company_cost,
                                                        'client_cost' => (float) $ordRow->client_cost,
                                                    ]);
                                                }
                                            }

                                            $historyGrouped = $orderHistory
                                                ->groupBy('order_number')
                                                ->map(function ($rows) {
                                                    $rows = collect($rows)->sortBy('round')->values();
                                                    $prev = null;
                                                    return $rows->map(function ($row) use (&$prev) {
                                                        $delta = is_null($prev) ? null : round(((float) $row['total_cost']) - ((float) $prev), 2);
                                                        $row['delta_total'] = $delta;
                                                        $prev = (float) $row['total_cost'];
                                                        return $row;
                                                    });
                                                });

                                            $sumIncrease = (float) $historyGrouped
                                                ->flatten(1)
                                                ->filter(fn($r) => !is_null($r['delta_total']) && $r['delta_total'] > 0)
                                                ->sum('delta_total');

                                            $sumEconomy = (float) $historyGrouped
                                                ->flatten(1)
                                                ->filter(fn($r) => !is_null($r['delta_total']) && $r['delta_total'] < 0)
                                                ->sum(fn($r) => abs((float) $r['delta_total']));

                                            $sumNet = round($sumIncrease - $sumEconomy, 2);
                                        @endphp
                                        <div class="fw-semibold mb-1">Histórico por ordem</div>
                                        <div class="table-responsive border rounded" style="max-height: 240px;">
                                            <table class="table table-sm mb-0">
                                                <thead>
                                                    <tr>
                                                        <th>Ordem</th>
                                                        <th>Rodada</th>
                                                        <th>Enviado</th>
                                                        <th>Total</th>
                                                        <th>Empresa</th>
                                                        <th>Cliente</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @forelse($historyGrouped as $ordNumber => $rows)
                                                        @foreach($rows as $r)
                                                            <tr class="{{ (int) $r['round'] === (int) $selectedCycle->round_number ? 'table-active' : '' }}">
                                                                <td>{{ $ordNumber }}</td>
                                                                <td>{{ $r['round'] }}</td>
                                                                <td>{{ $r['submitted_at'] ? date('d/m/Y H:i', strtotime($r['submitted_at'])) : '---' }}</td>
                                                                <td>{{ number_format((float) $r['total_cost'], 2, ',', '.') }}</td>
                                                                <td>{{ number_format((float) $r['company_cost'], 2, ',', '.') }}</td>
                                                                <td>{{ number_format((float) $r['client_cost'], 2, ',', '.') }}</td>
                                                            </tr>
                                                        @endforeach
                                                    @empty
                                                        <tr>
                                                            <td colspan="6" class="text-center text-muted">Sem histórico de ordens.</td>
                                                        </tr>
                                                    @endforelse
                                                    @if($historyGrouped->isNotEmpty())
                                                        <tr class="table-light fw-semibold">
                                                            <td colspan="2">Totalizador da diferença</td>
                                                            <td colspan="2" class="text-success">Economia: {{ number_format($sumEconomy, 2, ',', '.') }}</td>
                                                            <td class="text-danger">Aumento: {{ number_format($sumIncrease, 2, ',', '.') }}</td>
                                                            <td class="{{ $sumNet < 0 ? 'text-success' : ($sumNet > 0 ? 'text-danger' : 'text-muted') }}">
                                                                Saldo: {{ number_format(abs($sumNet), 2, ',', '.') }}
                                                                @if($sumNet < 0)
                                                                    (economia)
                                                                @elseif($sumNet > 0)
                                                                    (aumento)
                                                                @else
                                                                    (mantido)
                                                                @endif
                                                            </td>
                                                        </tr>
                                                    @endif
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>

                                <div class="card card-soft mb-3">
                                    <div class="card-header">Detalhe do Encerramento</div>
                                    <div class="card-body small">
                                        <div><strong>Finalidade:</strong> {{ $selectedProduction?->Analise?->preresult ?? '---' }}</div>
                                        <div><strong>Conclusão:</strong> {{ $selectedProduction?->Analise?->conclusion ?? '---' }}</div>
                                        <div class="mt-2"><strong>Informações:</strong></div>
                                        <div class="text-muted" style="white-space: pre-line;">{{ $selectedProduction?->Analise?->info ?? '---' }}</div>
                                    </div>
                                </div>

                                <div class="card card-soft mb-3">
                                    <div class="card-header">Arquivos do Projeto</div>
                                    <div class="card-body small">
                                        @php
                                            $noteFiles = ($selectedProduction?->Note?->Files ?? collect())
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

                                        @if ($drawingProduction)
                                            <hr>
                                            <div class="mb-2 text-muted">
                                                Upload será vinculado ao serviço:
                                                <strong>{{ $drawingProduction->Service->service ?? 'Desenho' }}</strong>
                                            </div>
                                            @livewire('files.manager.create-prod-files', ['production' => $drawingProduction, 'needFiles' => false], key('project_review_analyst_files_' . $drawingProduction->id))
                                            <div class="d-flex justify-content-end mt-2">
                                                <button type="button" class="btn btn-sm btn-outline-success" wire:click="saveAnalystFiles">
                                                    Salvar uploads do analista
                                                </button>
                                            </div>
                                        @endif
                                    </div>
                                </div>

                                <div class="card card-soft">
                                    <div class="card-header">Parecer Técnico do Analista</div>
                                    <div class="card-body">
                                        <textarea class="form-control @error('analystNote') is-invalid @enderror" rows="5"
                                            wire:model.defer="analystNote"
                                            placeholder="Obrigatório apenas em 'Aprovar com ressalvas'"></textarea>
                                        @error('analystNote')
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <div class="card card-soft mt-3">
                                    <div class="card-header">Chat da Análise</div>
                                    <div class="card-body">
                                        <div class="chat-stream mb-2">
                                            @forelse ($selectedCycle->Messages->sortByDesc('created_at') as $msg)
                                                @php
                                                    $mine = $msg->user_id === auth()->id();
                                                @endphp
                                                <div class="d-flex mb-2 {{ $mine ? 'justify-content-end' : 'justify-content-start' }}">
                                                    <div class="chat-bubble {{ $mine ? 'mine' : '' }}">
                                                        <div class="small text-muted">{{ $msg->User->name ?? 'Usuário' }} - {{ date('d/m/Y H:i', strtotime($msg->created_at)) }}</div>
                                                        <div>{{ $msg->message }}</div>
                                                    </div>
                                                </div>
                                            @empty
                                                <div class="small text-muted">Sem mensagens ainda nesta rodada.</div>
                                            @endforelse
                                        </div>
                                        <textarea class="form-control mt-2" rows="2" wire:model.defer="newReply" placeholder="Escreva uma mensagem"></textarea>
                                        <button type="button" class="btn btn-sm btn-outline-primary mt-2" wire:click="addReply">Enviar mensagem</button>
                                    </div>
                                </div>
                            </div>

                            <div class="col-lg-7">
                                <div class="step-title"><span class="step-badge">2</span>Montagem da Análise</div>
                                <div class="step-help">Marque como conforme o que foi corrigido. Reprove somente com pendências restantes.</div>
                                <div class="card card-soft h-100">
                                    <div class="card-header">Montagem da Análise do Projeto</div>
                                    <div class="card-body">
                                        @php
                                            $totalRows = count($findingRows);
                                            $conformRows = collect($findingRows)->filter(fn ($r) => !empty($r['is_conform']))->count();
                                            $pendingRows = $totalRows - $conformRows;
                                        @endphp
                                        <div class="d-flex flex-wrap gap-2 mb-3">
                                            <div class="summary-pill"><strong>Total:</strong> {{ $totalRows }}</div>
                                            <div class="summary-pill"><strong>Conformes:</strong> {{ $conformRows }}</div>
                                            <div class="summary-pill"><strong>Pendentes:</strong> {{ $pendingRows }}</div>
                                        </div>

                                        <div class="row g-3">
                                            <div class="col-lg-6">
                                                <div class="card border mb-3">
                                                    <div class="card-header">Seleção</div>
                                                    <div class="card-body">
                                                        <div class="mb-2">
                                                            <label class="form-label">Categoria</label>
                                                            <select class="form-select" wire:model="selectedCategoryId">
                                                                <option value="">Selecione</option>
                                                                @foreach ($categories as $cat)
                                                                    <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                                                @endforeach
                                                            </select>
                                                        </div>
                                                        <div class="mb-2">
                                                            <label class="form-label">Subcategoria</label>
                                                            <select class="form-select" wire:model="selectedSubcategoryId">
                                                                <option value="">Selecione</option>
                                                                @foreach ($availableSubcategories as $sub)
                                                                    <option value="{{ $sub->id }}">{{ $sub->name }}</option>
                                                                @endforeach
                                                            </select>
                                                        </div>
                                                        <div class="mb-2">
                                                            <label class="form-label">Movimento</label>
                                                            <select class="form-select" wire:model="selectedActionType">
                                                                <option value="FALTA">FALTA</option>
                                                                <option value="ADICIONAR">ADICIONAR</option>
                                                                <option value="REMOVER">REMOVER</option>
                                                            </select>
                                                        </div>
                                                        <div class="mb-0">
                                                            <label class="form-label">Origem</label>
                                                            <select class="form-select" wire:model="selectedOrigin">
                                                                <option value="PROJETO">Projeto</option>
                                                                <option value="LEVANTAMENTO">Levantamento</option>
                                                                <option value="AMBOS">Ambos</option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="col-lg-6">
                                                <div class="card border">
                                                    <div class="card-header d-flex justify-content-between align-items-center">
                                                        <span>Itens da subcategoria</span>
                                                        <button type="button" class="btn btn-sm btn-outline-secondary"
                                                            wire:click="addEmptySubcategory" @disabled(!$selectedSubcategoryId)>
                                                            Adicionar subcategoria vazia
                                                        </button>
                                                    </div>
                                                    <div class="card-body analysis-items-scroll">
                                                        @if ($selectedSubcategoryId && $availableItems->count())
                                                            <div class="table-responsive">
                                                                <table class="table table-sm mb-0">
                                                                    <thead><tr><th>Item</th><th style="width: 120px;"></th></tr></thead>
                                                                    <tbody>
                                                                        @foreach ($availableItems as $item)
                                                                            <tr>
                                                                                <td>{{ $item->name }}</td>
                                                                                <td><button type="button" class="btn btn-sm btn-outline-primary w-100" wire:click="addItemToFindings({{ $item->id }})">Adicionar</button></td>
                                                                            </tr>
                                                                        @endforeach
                                                                    </tbody>
                                                                </table>
                                                            </div>
                                                        @else
                                                            <div class="text-muted small">Selecione uma subcategoria para listar os itens.</div>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="col-12">
                                                <div class="analysis-findings-scroll">
                                                    @forelse($findingsTree as $category)
                                                        @php
                                                            $catCollapsed = $collapsedCategories[$category['category_key']] ?? false;
                                                        @endphp
                                                        <div class="card border mb-2">
                                                            <div class="card-header d-flex justify-content-between align-items-center py-2 gap-2">
                                                                <button type="button" class="btn btn-link text-decoration-none p-0 group-head-btn"
                                                                    wire:click="toggleCategoryGroup('{{ $category['category_key'] }}')">
                                                                    {{ $catCollapsed ? '+' : '-' }} {{ $category['category_name'] }}
                                                                </button>
                                                                <div class="d-flex align-items-center gap-2">
                                                                    <span class="badge text-bg-light">{{ count($category['subcategories']) }} subcategoria(s)</span>
                                                                    <button type="button" class="btn btn-sm btn-outline-danger"
                                                                        wire:click="removeCategoryGroup('{{ $category['category_key'] }}')">
                                                                        Remover categoria
                                                                    </button>
                                                                </div>
                                                            </div>
                                                            @if (!$catCollapsed)
                                                                <div class="card-body py-2">
                                                                    @foreach($category['subcategories'] as $subcategory)
                                                                        @php
                                                                            $subCollapsed = $collapsedSubcategories[$subcategory['subcategory_key']] ?? false;
                                                                        @endphp
                                                                        <div class="card border mb-2">
                                                                            <div class="card-header d-flex justify-content-between align-items-center py-2 gap-2">
                                                                                <button type="button" class="btn btn-link text-decoration-none p-0 group-head-btn"
                                                                                    wire:click="toggleSubcategoryGroup('{{ $subcategory['subcategory_key'] }}')">
                                                                                    {{ $subCollapsed ? '+' : '-' }} {{ $subcategory['subcategory_name'] }}
                                                                                </button>
                                                                                <div class="d-flex align-items-center gap-2">
                                                                                    <select class="form-select form-select-sm" style="width:180px;"
                                                                                        wire:change="setGroupOrigin('{{ $subcategory['subcategory_key'] }}', $event.target.value)">
                                                                                        <option value="PROJETO" {{ $subcategory['origin'] === 'PROJETO' ? 'selected' : '' }}>Projeto</option>
                                                                                        <option value="LEVANTAMENTO" {{ $subcategory['origin'] === 'LEVANTAMENTO' ? 'selected' : '' }}>Levantamento</option>
                                                                                        <option value="AMBOS" {{ $subcategory['origin'] === 'AMBOS' ? 'selected' : '' }}>Ambos</option>
                                                                                    </select>
                                                                                    <button type="button" class="btn btn-sm btn-outline-danger"
                                                                                        wire:click="removeSubcategoryGroup('{{ $subcategory['subcategory_key'] }}')">
                                                                                        Remover subcategoria
                                                                                    </button>
                                                                                </div>
                                                                            </div>
                                                                            @if (!$subCollapsed)
                                                                                <div class="card-body pt-2">
                                                                                    <div class="table-responsive">
                                                                                        <table class="table table-sm align-middle mb-0">
                                                                                            <thead>
                                                                                        <tr>
                                                                                            <th>Ação</th>
                                                                                            <th style="width: 90px;">Qtd.</th>
                                                                                            <th>Conforme</th>
                                                                                            <th style="min-width: 420px;">Observação</th>
                                                                                            <th style="width:100px;"></th>
                                                                                        </tr>
                                                                                    </thead>
                                                                                    <tbody>
                                                                                                @foreach($subcategory['rows'] as $row)
                                                                                                    @php
                                                                                                        $idx = $row['index'];
                                                                                                    @endphp
                                                                                                    <tr>
                                                                                                        <td>
                                                                                                            @if($row['item_name'])
                                                                                                                <span class="badge text-bg-light">{{ $row['action_type'] ?? 'FALTA' }}</span>
                                                                                                                {{ $row['item_name'] }}
                                                                                                            @else
                                                                                                                ---
                                                                                                            @endif
                                                                                                        </td>
                                                                                                        <td>
                                                                                                            <input type="number" min="1" class="form-control form-control-sm"
                                                                                                                wire:model.defer="findingRows.{{ $idx }}.quantity"
                                                                                                                @disabled(!$row['item_name'])>
                                                                                                        </td>
                                                                                                        <td>
                                                                                                            <div class="form-check">
                                                                                                                <input class="form-check-input" type="checkbox"
                                                                                                                    wire:model.defer="findingRows.{{ $idx }}.is_conform"
                                                                                                                    id="row-conform-{{ $idx }}">
                                                                                                                <label class="form-check-label small" for="row-conform-{{ $idx }}">
                                                                                                                    Em conformidade
                                                                                                                </label>
                                                                                                            </div>
                                                                                                        </td>
                                                                                                        <td>
                                                                                                            <textarea class="form-control form-control-sm"
                                                                                                                rows="2"
                                                                                                                wire:model.defer="findingRows.{{ $idx }}.note">
                                                                                                            </textarea>
                                                                                                        </td>
                                                                                                        <td>
                                                                                                            <button type="button" class="btn btn-sm btn-outline-danger w-100"
                                                                                                                wire:click="removeFindingRow({{ $idx }})">Remover</button>
                                                                                                        </td>
                                                                                                    </tr>
                                                                                                @endforeach
                                                                                            </tbody>
                                                                                        </table>
                                                                                    </div>
                                                                                </div>
                                                                            @endif
                                                                        </div>
                                                                    @endforeach
                                                                </div>
                                                            @endif
                                                        </div>
                                                    @empty
                                                        <div class="alert alert-light border">Nenhum apontamento adicionado.</div>
                                                    @endforelse
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        Fechar
                    </button>
                    <button class="btn btn-success" wire:click="approve">Aprovar sem ressalvas</button>
                    <button class="btn btn-primary" wire:click="approveWithRemarks">Aprovar com ressalvas</button>
                    <button class="btn btn-danger" wire:click="reject">Reprovar</button>
                </div>
            </div>
        </div>
    </div>
</div>
