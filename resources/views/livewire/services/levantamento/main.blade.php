@php
    use App\Custom\Notestatus;
@endphp

<div class="service-page">
    {{-- Loading Overlay --}}
    <x-show-loading />

    <style>
        .service-page {
            --sp-bg: #f6f7fb;
            --sp-surface: #ffffff;
            --sp-ink: #1f2933;
            --sp-muted: #6b7280;
            --sp-border: #e5e7eb;
            background: radial-gradient(circle at 10% 0%, #eef2ff, transparent 40%),
                radial-gradient(circle at 90% 10%, #ecfeff, transparent 35%), var(--sp-bg);
            padding: 1.5rem 0;
        }

        .service-header {
            background: linear-gradient(120deg, #0f172a, #0f766e 70%);
            color: #f8fafc;
            border-radius: 1rem;
            padding: 1.5rem 2rem;
            box-shadow: 0 16px 40px rgba(15, 23, 42, 0.2);
            margin-bottom: 1.5rem;
        }

        .service-header h2 {
            margin: 0;
            font-weight: 700;
            letter-spacing: 0.02em;
        }

        .service-header .meta {
            color: rgba(248, 250, 252, 0.75);
            font-size: 0.95rem;
        }

        .filters-grid .filter-card {
            background: var(--sp-surface);
            border: 1px solid var(--sp-border);
            border-radius: 0.9rem;
            padding: 1rem 1.25rem;
            box-shadow: 0 12px 24px rgba(15, 23, 42, 0.06);
            height: 100%;
        }

        .filters-grid .filter-card h6 {
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            font-weight: 600;
            color: var(--sp-muted);
        }

        .quality-search-modal { border-radius: 1rem; box-shadow: 0 24px 64px rgba(15, 23, 42, .28); }
        .quality-search-modal__header { padding: 1.5rem; color: #fff; background: linear-gradient(115deg, #0f172a, #0f766e); }
        .quality-search-modal__header p { color: rgba(255, 255, 255, .78); font-size: .86rem; }
        .quality-search-modal__icon { display: grid; place-items: center; flex: 0 0 auto; width: 2.75rem; height: 2.75rem; border-radius: .75rem; background: rgba(255, 255, 255, .16); font-size: 1.4rem; }
        .quality-search-modal__notice { display: flex; gap: .65rem; align-items: flex-start; padding: .8rem 1rem; border: 1px solid #bfdbfe; border-radius: .65rem; color: #1e3a8a; background: #eff6ff; font-size: .83rem; }
        .quality-search-modal__notice i { margin-top: .1rem; font-size: 1rem; }
        .quality-search-modal__search { padding: 1rem; border: 1px solid #e2e8f0; border-radius: .75rem; background: #f8fafc; }
        .quality-search-modal__search textarea { min-height: 7rem; resize: vertical; border-color: #cbd5e1; }
        .quality-search-modal .modal-body { overflow-y: auto; }
        .quality-search-modal__results { max-height: 20rem; overflow: auto; border: 1px solid #e2e8f0; border-radius: .7rem; }
        .quality-search-modal__table { margin: 0; font-size: .8rem; }
        .quality-search-modal__table thead { position: sticky; top: 0; z-index: 1; background: #f1f5f9; }
        .quality-search-modal__table th { padding: .55rem .65rem; color: #475569; font-size: .68rem; letter-spacing: .05em; text-transform: uppercase; white-space: nowrap; }
        .quality-search-modal__table td { padding: .52rem .65rem; vertical-align: middle; }
        .quality-search-modal__row--available { cursor: pointer; }
        .quality-search-modal__row--available:hover { background: #f0fdfa; }
        .quality-search-modal__row--available:has(input:checked) { background: #ccfbf1; }
        .quality-search-modal__row--blocked { color: #64748b; background: #f8fafc; }
        .quality-search-modal__reason { display: block; max-width: 19rem; font-size: .72rem; line-height: 1.25; }
        .quality-search-modal__footer { flex: 0 0 auto; background: #f8fafc; box-shadow: 0 -5px 18px rgba(15, 23, 42, .06); }
    </style>

    <div class="container-fluid">
        <div class="service-header d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3">
            <div>
                <h2>{{ mb_strtoupper($service->service) }}</h2>
                <div class="meta">Gestao de producao em levantamento</div>
            </div>
        </div>

    {{-- ==========================
         🔍 Filtros e ações
    =========================== --}}
    <div class="row g-3 filters-grid mb-3">
        <div class="col-12 col-lg-7">
            <div class="filter-card">
                <h6>Pesquisa</h6>
                <label for="search" class="form-label mb-1 fw-semibold">Buscar Nota / Material</label>
                <input wire:model.debounce.700ms="search" id="search" type="text"
                    class="form-control border-2 border-secondary" placeholder="Digite para buscar...">
            </div>
        </div>

        <div class="col-12 col-lg-5">
            <div class="filter-card d-flex flex-column justify-content-end">
                <h6>Acoes</h6>
                <button type="button" class="btn btn-success" wire:click="exportToExcel" wire:loading.attr="disabled"
                    wire:target="exportToExcel">
                    <span wire:loading.remove wire:target="exportToExcel">
                        <i class="ri-file-excel-2-line me-2"></i>Exportar
                    </span>
                    <span wire:loading wire:target="exportToExcel">
                        <i class="ri-loader-4-line me-2"></i>Enfileirando...
                    </span>
                </button>
                @if ($exportFeedback)
                    <div class="alert alert-{{ $exportFailed ? 'danger' : 'success' }} py-2 px-3 mt-2 mb-0 small" role="status">
                        {{ $exportFeedback }}
                    </div>
                @endif
                <button type="button" class="btn btn-outline-primary mt-2" data-bs-toggle="modal" data-bs-target="#quality_available_modal">
                    <i class="ri-shield-check-line me-2"></i>Buscar na Qualidade
                </button>
            </div>
        </div>
    </div>

    <div wire:ignore.self class="modal fade" id="quality_available_modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
            <div class="modal-content quality-search-modal border-0 overflow-hidden">
                <div class="modal-header quality-search-modal__header border-0 align-items-start">
                    <div class="d-flex gap-3">
                        <span class="quality-search-modal__icon"><i class="ri-shield-check-line"></i></span>
                        <div>
                            <h5 class="modal-title mb-1">Obras disponíveis na Qualidade</h5>
                            <p class="mb-0">Consulte as notas da fila de despacho N1 e assuma apenas as que deseja executar.</p>
                        </div>
                    </div>
                    <button type="button" class="btn-close btn-close-white mt-1" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="quality-search-modal__notice mb-4"><i class="ri-information-line"></i><span>Ao assumir, a obra sai da fila N1 e entra na sua produção com todos os registros de Qualidade. Itens já atribuídos ficam bloqueados.</span></div>
                    <div class="quality-search-modal__search">
                        <label for="quality_notes" class="form-label fw-semibold mb-2">Notas ou pedidos</label>
                        <textarea id="quality_notes" wire:model.defer="qualityNotes" class="form-control" rows="4" placeholder="Cole uma nota por linha, ou separe por vírgula e ponto e vírgula"></textarea>
                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mt-3"><span class="small text-muted"><i class="ri-file-list-3-line me-1"></i>Busca em massa de até 2.000 itens</span><button type="button" class="btn btn-primary px-4" wire:click="searchQualityAvailable" wire:loading.attr="disabled" wire:target="searchQualityAvailable"><span wire:loading.remove wire:target="searchQualityAvailable"><i class="ri-search-line me-1"></i> Buscar na fila</span><span wire:loading wire:target="searchQualityAvailable"><i class="ri-loader-4-line me-1"></i> Consultando...</span></button></div>
                    </div>

                    @if (count($qualityCandidates))
                        <div class="d-flex justify-content-between align-items-center mt-4 mb-2"><h6 class="mb-0 fw-bold">Resultado da consulta</h6><span class="badge rounded-pill text-bg-light">{{ collect($qualityCandidates)->where('available', true)->count() }} disponível(is)</span></div>
                        <div class="quality-search-modal__results table-responsive">
                            <table class="table table-sm align-middle quality-search-modal__table">
                                <thead><tr><th class="text-center" style="width: 42px">Selecionar</th><th>Nota</th><th>Material</th><th>Empresa</th><th>Status / motivo</th></tr></thead>
                                <tbody>
                                    @foreach ($qualityCandidates as $item)
                                        <tr class="{{ $item['available'] ? 'quality-search-modal__row--available' : 'quality-search-modal__row--blocked' }}" wire:key="quality-candidate-{{ $loop->index }}">
                                            <td class="text-center"><input class="form-check-input" type="checkbox" value="{{ $item['process_id'] }}" wire:model="selectedQualityProcessIds" @disabled(!$item['available']) aria-label="Selecionar nota {{ $item['note'] }}"></td>
                                            <td class="fw-semibold text-nowrap">{{ $item['note'] }}</td>
                                            <td>{{ $item['material'] ?: '—' }}</td>
                                            <td class="text-nowrap">{{ $item['company'] ?: '—' }}</td>
                                            <td><span class="badge {{ $item['available'] ? 'text-bg-success' : 'text-bg-secondary' }}">{{ $item['available'] ? 'Disponível' : 'Bloqueada' }}</span>@if (!$item['available'])<span class="quality-search-modal__reason text-danger mt-1"><i class="ri-lock-line me-1"></i>{{ $item['reason'] }}</span>@endif</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
                <div class="modal-footer quality-search-modal__footer border-0 px-4 py-3"><button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancelar</button><button type="button" class="btn btn-success px-4" wire:click="claimQualityAvailable" wire:loading.attr="disabled" wire:target="claimQualityAvailable"><span wire:loading.remove wire:target="claimQualityAvailable"><i class="ri-user-add-line me-1"></i> Importar selecionadas</span><span wire:loading wire:target="claimQualityAvailable"><i class="ri-loader-4-line me-1"></i> Importando...</span></button></div>
            </div>
        </div>
    </div>

    {{-- ==========================
         📋 Tabs de conteúdo
    =========================== --}}
    <ul class="nav nav-tabs mb-3" id="tab-levantamento" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="tab-prod-tab" data-bs-toggle="tab" data-bs-target="#tab-prod"
                type="button" role="tab" aria-controls="tab-prod" aria-selected="true"
                wire:click.prevent="$emit('refresh_accomany')">
                Produção
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="tab-transf-tab" data-bs-toggle="tab" data-bs-target="#tab-transf"
                type="button" role="tab" aria-controls="tab-transf" aria-selected="false"
                wire:click.prevent="$emit('refresh_translist')">
                Transferências
                @livewire('components.transprod.count', ['service_id' => $service->uuid], key('transfer_count'))
            </button>
        </li>
    </ul>

    {{-- ==========================
         🧾 Conteúdo principal
    =========================== --}}
    <div class="tab-content" id="tab-content-levantamento">
        {{-- PRODUÇÃO --}}
        <div class="tab-pane fade show active" id="tab-prod" role="tabpanel" aria-labelledby="tab-prod-tab">
            @if ($lists->count())
                <div class="d-flex justify-content-between align-items-center mb-2 small text-muted">
                    {{ $lists->links() }}
                    <span>Exibindo {{ $lists->firstItem() }}–{{ $lists->lastItem() }} de {{ $lists->total() }}</span>
                </div>

                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-danger text-white d-flex justify-content-between align-items-center py-3">
                        <div>
                            <h5 class="fw-semibold fs-5 my-0">Acompanhamento</h5>
                            <div class="small text-white-50">{{ mb_strtoupper($service->service) }}</div>
                        </div>
                        <span class="small">
                            @foreach ($service->Status->where('exclusion', false)->unique('value') as $sts)
                                ({{ $sts->value }})
                            @endforeach
                        </span>
                    </div>

                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-sm table-striped align-middle mb-0">
                                <thead class="table-dark text-center">
                                    <tr>
                                        <th>Nota</th>
                                        <th>DD</th>
                                        <th>Rubrica</th>
                                        <th>Município</th>
                                        <th>Grupo2</th>
                                        <th>Descrição</th>
                                        <th>Em Atribuição</th>
                                        <th>PzReal</th>
                                        <th>Status</th>
                                        <th>Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($lists as $item)
                                        @php
                                            $prazo = Carbon\Carbon::parse($item->dt_created);

                                            $prazoFormatado = $prazo->addDays(30)->format('d/m/Y');

                                            $columnColor = '';

                                            $diasRestantes = Carbon\Carbon::parse($item->dt_created)
                                                ->startOfDay()
                                                ->diffInDays(Carbon\Carbon::now()->startOfDay(), false);

                                            if ($diasRestantes > 30) {
                                                // Prazo vencido
                                                $columnColor = 'text-bg-danger';
                                            } elseif ($diasRestantes <= 20) {
                                                // Mais de 30 dias restantes
                                                $columnColor = 'text-bg-success';
                                            } else {
                                                // Entre 0 e 30 dias
                                                $columnColor = 'text-bg-warning';
                                            }

                                            $daysAtt = $item->att_at->diffInDays(Carbon\Carbon::now(), false);

                                            $columnAttColor = '';

                                            if ($daysAtt > 8) {
                                                $columnAttColor = 'text-bg-danger';
                                            } elseif ($daysAtt <= 5) {
                                                $columnAttColor = 'text-bg-success';
                                            } else {
                                                $columnAttColor = 'text-bg-warning';
                                            }

                                        @endphp
                                        <tr class="@if ($item->priority) table-danger @endif text-center">
                                            {{-- Nota --}}
                                            <td class="fw-semibold">
                                                {{ $item->Note->note }}
                                                <i class="ri-file-copy-line ms-1 text-muted copy-text"
                                                    data-value="{{ $item->Note->note }}" style="cursor:pointer"
                                                    title="Copiar número"></i>
                                                @if ($item->priority)
                                                    <i class="ri-alert-fill text-danger ms-1"
                                                        wire:click="$emit('infoPriority', '{{ $item->id }}')"
                                                        title="Nota com prioridade"></i>
                                                @endif
                                            </td>

                                            {{-- DD --}}
                                            <td>
                                                @if ($item->Wpas->isNotEmpty())
                                                    @php $dd = $item->Wpas->sortByDesc('created_at')->first()->dd; @endphp
                                                    <a href="https://edp-wpa-po.azurewebsites.net/Search?q={{ $dd }}"
                                                        class="link-primary fw-bold"
                                                        target="_blank">{{ $dd }}</a>
                                                @else
                                                    <span class="text-muted">---</span>
                                                @endif
                                            </td>

                                            {{-- Rubrica / Local / Descrição --}}
                                            <td>{{ $item->Note->rubrica ?? '—' }}</td>
                                            <td>{{ $item->Note->lexp ?? '—' }}</td>
                                            <td>{{ $item->Note->group2 ?? '—' }}</td>
                                            <td class="text-truncate" style="max-width: 220px;">
                                                {{ $item->Note->material ?? '—' }}
                                            </td>

                                            {{-- Em Atribuição --}}
                                            <td>
                                                <span
                                                    class="badge
                                                        {{ $columnAttColor }}">
                                                    {{ $daysAtt }} d
                                                </span>
                                            </td>

                                            <td class="{{ $columnColor }}">
                                                <span class="badge text-bg-light">{{ $diasRestantes }}</span>
                                                <p class="py-0 my-0">{{ $prazoFormatado }}</p>

                                            </td>

                                            {{-- Status --}}
                                            <td>
                                                @if ($item->transferred && $item->block_wpa)
                                                    <span class="badge bg-warning">Aguardando Despacho</span>
                                                @else
                                                    <span
                                                        class="badge {{ Notestatus::status($item->status)->colorbg }}"
                                                        wire:click="$emitTo('components.status.show-status', 'showStatus', {{ $item }}, {{ $item->status }})"
                                                        style="cursor:pointer;">
                                                        {{ Notestatus::status($item->status)->status }}
                                                    </span>
                                                @endif
                                                @php $qualityProcess = $item->activeQualityProcess; @endphp
                                                @if ($qualityProcess)
                                                    <div class="mt-1"><span class="badge text-bg-dark" title="Atividade originada pela Qualidade: finalize pelo formulário da Qualidade."><i class="ri-shield-check-line"></i> QUALIDADE · {{ $qualityProcess->phase->short() }} · rodada {{ $qualityProcess->round_number }}</span></div>
                                                @endif
                                            </td>

                                            {{-- Ações --}}
                                            <td class="fw-bold fs-5">
                                                @if (!$item->block && !$item->block_wpa && !$item->completed)
                                                    @if ($item->activeQualityProcess)
                                                        <i class="ri-eye-line text-primary mx-1" wire:click.prevent="getQualityDetails({{ $item->id }})" title="Ver detalhes (somente leitura)" style="cursor: pointer;"></i>
                                                    @endif
                                                    <i class="ri-play-circle-line text-success mx-1"
                                                        wire:click.prevent="getAnalise({{ $item->id }}, {{ $item->Note->id }})"
                                                        title="Iniciar" style="cursor: pointer;"></i>
                                                    @if (!$item->activeQualityProcess)
                                                        <i class="ri-exchange-fill text-primary mx-1"
                                                            wire:click.prevent="goTransferProd({{ $item->id }})"
                                                            title="Transferir" style="cursor: pointer;"></i>
                                                    @endif
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-between align-items-center mt-2 small text-muted">
                    {{ $lists->links() }}
                    <span>Exibindo {{ $lists->firstItem() }}–{{ $lists->lastItem() }} de {{ $lists->total() }}</span>
                </div>
            @else
                <div class="p-4 text-center text-muted">
                    Nenhuma tarefa atribuída em <strong>{{ mb_strtoupper($service->service) }}</strong>.
                </div>
            @endif
        </div>

        {{-- TRANSFERÊNCIAS --}}
        <div class="tab-pane fade" id="tab-transf" role="tabpanel" aria-labelledby="tab-transf-tab">
            @livewire('components.transprod.translist', ['service' => $service->id])
        </div>
    </div>

    {{-- ==========================
         ⚙️ Modais
    =========================== --}}
    <div wire:ignore.self class="modal fade" id="analise_form" data-bs-backdrop="static" data-bs-keyboard="false"
        tabindex="-1" aria-labelledby="analise_form_label" aria-hidden="true">
        <div class="modal-dialog modal-fullscreen modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header text-bg-success">
                    <h5 class="modal-title">{{ mb_strtoupper($service->service) }}</h5>
                </div>
                <div class="modal-body">
                    @livewire('services.levantamento.forms.analise', key('levantamento-form'))
                </div>
            </div>
        </div>
    </div>

    <div wire:ignore.self class="modal fade" id="quality_closing_form" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="qualityClosingLabel" aria-hidden="true">
        <div class="modal-dialog modal-fullscreen modal-dialog-scrollable">
            <div class="modal-content h-100">
                <div class="modal-header text-bg-primary">
                    <h1 class="modal-title fs-5" id="qualityClosingLabel">ATIVIDADE DE QUALIDADE</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body">
                    @livewire('services.desenho.forms.quality-closing', key('quality-closing-form'))
                </div>
            </div>
        </div>
    </div>

    {{-- Componentes auxiliares --}}
    @livewire('components.transprod.transprodlev', key('Transfer_production'))
    @livewire('components.status.show-status', key('show_status_note'))
    @livewire('services.desenho.actions.responserinfo', key('responser_info_return'))

    {{-- Inicialização automática --}}
    <div wire:init="checkOpen"></div>
    </div>
</div>

@push('script')
    <script>
        // Copiar número da nota
        document.addEventListener('click', e => {
            if (e.target.classList.contains('copy-text')) {
                const value = e.target.getAttribute('data-value');
                navigator.clipboard.writeText(value);
                Livewire.emit('getCopy', `Número "${value}" copiado.`);
            }
        });
    </script>
@endpush
