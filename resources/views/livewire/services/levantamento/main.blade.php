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
                <button class="btn btn-success" wire:click.prevent="exportToExcel" wire:loading.attr="disabled"
                    wire:target="exportToExcel">
                    <span wire:loading.remove wire:target="exportToExcel">
                        <i class="ri-file-excel-2-line me-2"></i>Exportar
                    </span>
                    <span wire:loading wire:target="exportToExcel">
                        <i class="ri-loader-4-line me-2"></i>Enfileirando...
                    </span>
                </button>
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
                    <div class="card-header bg-danger text-white fw-bold d-flex justify-content-between">
                        <span>ACOMPANHAMENTO — {{ mb_strtoupper($service->service) }}</span>
                        <span>
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
                                            </td>

                                            {{-- Ações --}}
                                            <td class="fw-bold fs-5">
                                                @if (!$item->block && !$item->block_wpa && !$item->completed)
                                                    <i class="ri-play-circle-line text-success mx-1"
                                                        wire:click.prevent="getAnalise({{ $item->id }}, {{ $item->Note->id }})"
                                                        title="Iniciar" style="cursor: pointer;"></i>
                                                    <i class="ri-exchange-fill text-primary mx-1"
                                                        wire:click.prevent="goTransferProd({{ $item->id }})"
                                                        title="Transferir" style="cursor: pointer;"></i>
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
