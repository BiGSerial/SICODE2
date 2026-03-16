@php
    use App\Custom\Notestatus;
@endphp

<div class="service-page">
    <x-show-loading />

    <style>
        .service-page {
            --sp-bg: #f6f7fb;
            --sp-surface: #ffffff;
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
                <div class="meta">Gestao de acompanhamento de supervisao</div>
            </div>
        </div>

        <div class="row g-3 filters-grid mb-3">
            <div class="col-12 col-lg-5">
                <div class="filter-card">
                    <h6>Pesquisa</h6>
                    <label for="search" class="form-label">Buscar</label>
                    <input wire:model.debounce.500ms="search" type="text"
                        class="form-control border border-2 border-secondary" id="search"
                        placeholder="Buscar por Nota ou Material">
                </div>
            </div>

            <div class="col-12 col-lg-4">
                <div class="filter-card">
                    <h6>Tipo de nota</h6>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="note_type" wire:model="note_type"
                            value="1" id="nt1">
                        <label class="form-check-label" for="nt1">Nota</label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="note_type" wire:model="note_type"
                            value="2" id="nt2">
                        <label class="form-check-label" for="nt2">OV</label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="note_type" wire:model="note_type"
                            value="" id="nt3">
                        <label class="form-check-label" for="nt3">Ambos</label>
                    </div>
                </div>
            </div>

            <div class="col-12 col-lg-3">
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

    <nav>
        <div class="nav nav-tabs" id="nav-tab" role="tablist">
            <button class="nav-link active" id="nav-production-tab" data-bs-toggle="tab" data-bs-target="#my_production"
                type="button" role="tab" aria-controls="nav-home" aria-selected="true"
                wire:click.prevent="$emit('refresh_accomany')">
                Produção
            </button>

            <button class="nav-link" id="nav-transfer-tab" data-bs-toggle="tab" data-bs-target="#transfer"
                type="button" role="tab" aria-controls="nav-profile" aria-selected="false"
                wire:click.prevent="$emit('refresh_translist')">
                Transferências
                @livewire('components.transprod.count', ['service_id' => $service->uuid], key('transfer_count'))
            </button>
        </div>
    </nav>

    @php
        $serviceTitle = mb_strtoupper($service->service);
        $statusBadges = $service->Status->unique('value')->pluck('value')->implode(') (');
    @endphp

    <div class="tab-content" id="nav-tabContent">
        <div class="tab-pane fade show active" id="my_production" role="tabpanel" aria-labelledby="nav-home-tab"
            tabindex="0">

            @if ($lists->count())
                <div class="row">
                    <div class="col-6">
                        {{ $lists->links() }}
                    </div>
                    <div class="col-6 d-flex justify-content-end align-middle">
                        <span class="align-middle">
                            Exibindo {{ $lists->firstItem() }} até {{ $lists->lastItem() }} de {{ $lists->total() }}
                            registros.
                        </span>
                    </div>
                </div>
            @endif

            <div class="card">
                @if (!$lists->count())
                    <div class="card-body">
                        <h4 class="text-center">
                            VOCÊ NÃO TEM TAREFA ATRIBUÍDA <strong>{{ $serviceTitle }}</strong>
                            @if ($statusBadges)
                                ({{ $statusBadges }})
                            @endif
                        </h4>
                    </div>
                @else
                    <div class="card-header text-bg-danger">
                        <div class="row">
                            <div class="col">
                                <h4 class="my-0">
                                    ACOMPANHAMENTO - {{ $serviceTitle }}
                                    @if ($statusBadges)
                                        ({{ $statusBadges }})
                                    @endif
                                </h4>
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-sm table-striped table-condensed">
                            <thead class="table-dark">
                                <tr class="text-center align-middle">
                                    <th>Tipo</th>
                                    <th>Note</th>
                                    <th>Ordens</th>
                                    <th>DD</th>
                                    <th>Files</th>
                                    <th>MMGD</th>
                                    <th>ADS</th>
                                    <th>Postes</th>
                                    <th>Rubrica</th>
                                    <th>Municipio</th>
                                    <th>Descrição</th>
                                    <th>Dias Atribuido</th>
                                    <th>Dias Informe</th>
                                    <th>Status</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody id="table-body" class="align-middle text-center">
                                @foreach ($lists as $list)
                                    @php
                                        $note = $list->Note;
                                        $workForm = $note->WorkForm;
                                        $formBlock = $workForm ? (bool) $workForm->rejected : false;

                                        $dfive = $list->dfive ? optional($note->FiveNote) : null;
                                        $colorCell = $list->partial ? 'table-warning' : 'table-success';
                                        $rowWarn = $list->priority
                                            ? 'table-danger'
                                            : ($formBlock
                                                ? 'table-warning text-danger'
                                                : '');
                                        $daysLeft = (int) $list->days_left; // vindo do SQL
                                        $daysAss = (int) $list->days_assigned; // vindo do SQL

                                        $lastWpa = $list->Wpas->last(); // 1 item (latest()->limit(1))
                                        $hasOld = $note->OldAds && $note->OldAds->isNotEmpty();
                                        $hasNew = (bool) $note->Adsform;
                                        $adsForm = $note->Adsform ?? $workForm?->Adsform;
                                        $isTacitAds = (bool) ($adsForm?->tacit ?? false);
                                        $tacitDelivered = (bool) ($adsForm?->tacit_delivered_at ?? false);
                                    @endphp

                                    <tr wire:key='prod-{{ $list->id }}'
                                        @if ($workForm) wire:dblclick="$emitTo('partner.show.show-work-form', 'show_form', {{ $workForm->id }})"
                                        @elseif ($list->partial && $note->Partials && $note->Partials->isNotEmpty())
                                            wire:dblclick="$emitTo('partner.show.show-partial-info', 'show_form', {{ $note->Partials->last() }})" @endif
                                        class="{{ $rowWarn }}">

                                        <td class="{{ $colorCell }} fw-bold">
                                            {{ $list->partial ? 'Parcial' : 'Final' }}
                                        </td>

                                        <td class="@if ($list->priority) text-danger fw-bold @endif">
                                            @if ($dfive?->is_completed && !$dfive?->is_supervisioned)
                                                <span class="badge text-bg-success fs-6 copy-text"
                                                    data-value="{{ $note->note }}" style="cursor:pointer;"
                                                    wire:click.prevent="$emitTo('components.d5.d5details', 'openD5Details', {{ $note->id }})">
                                                    D5 {{ $note->note }}
                                                </span>
                                            @else
                                                <span class="copy-text" data-value="{{ $note->note }}"
                                                    style="cursor:pointer;">
                                                    {{ $note->note }}
                                                </span>
                                            @endif
                                            <i class="ri-file-copy-line ms-1 copy-text"
                                                data-value="{{ $note->note }}" style="cursor:pointer;"></i>

                                            @if ($list->priority)
                                                <i class="ri-alert-fill align-middle ms-2"
                                                    wire:click.prevent="$emit('infoPriority', '{{ $list->id }}')"
                                                    style="cursor:pointer;">
                                                </i>
                                            @endif
                                        </td>

                                        <td class="@if ($list->priority) text-danger fw-bold @endif">
                                            @if ($workForm && $workForm->Orders && $workForm->Orders->isNotEmpty())
                                                @foreach ($workForm->Orders as $order)
                                                    <p class="py-0 my-0">{{ $order->ordem }}</p>
                                                @endforeach
                                            @endif
                                        </td>

                                        <td class="@if ($list->priority) text-danger fw-bold @endif">
                                            @if ($lastWpa)
                                                <a class="link-primary fw-bold"
                                                    href="https://edp-wpa-po.azurewebsites.net/Search?q={{ $lastWpa->dd }}"
                                                    target="_blank" rel="noopener">
                                                    {{ $lastWpa->dd }}
                                                </a>
                                            @else
                                                -----
                                            @endif
                                        </td>

                                        <td class="align-middle">
                                            <x-files.select-download-list :files="$note->Files" />
                                        </td>

                                        <td class="fw-light">
                                            <span class="text-danger">{{ $note->mmgd ? 'MMGD' : '' }}</span>
                                        </td>

                                        <td class="fw-light">
                                            @if ($hasOld)
                                                <span class="text-warning fw-bold">OLD</span>
                                            @elseif ($hasNew)
                                                <span class="text-success fw-bold">ATUAL</span>
                                            @else
                                                <span class="text-danger fw-bold">NÃO</span>
                                            @endif
                                            @if ($isTacitAds)
                                                <div class="mt-1">
                                                    <span class="badge text-bg-dark">TÁCITA</span>
                                                    <span class="badge {{ $tacitDelivered ? 'text-bg-success' : 'text-bg-danger' }}">
                                                        {{ $tacitDelivered ? 'ENTREGUE' : 'NÃO ENTREGUE' }}
                                                    </span>
                                                </div>
                                            @endif
                                        </td>

                                        <td class="fw-light">
                                            <span class="text-primary fw-bold">{{ $note->postes ?? '---' }}</span>
                                        </td>

                                        <td class="fw-light">{{ $note->rubrica }}</td>
                                        <td class="fw-light">{{ $note->lexp }}</td>
                                        <td class="fw-light">{{ $note->material }}</td>

                                        <td class="fw-light">{{ $daysAss }}</td>

                                        <td
                                            class="text-center
                                            @if ($daysLeft <= 20) text-bg-success
                                            @elseif ($daysLeft >= 28) text-bg-danger
                                            @else text-bg-warning @endif">
                                            {{ $daysLeft }}
                                        </td>

                                        <td class="fw-light">
                                            @if ($list->transferred && $list->block_wpa)
                                                <span class="badge bg-warning">Aguardando Despacho</span>
                                            @elseif ($workForm && $workForm->rejected)
                                                <span class="badge text-bg-warning text-wrap p-1">INFORME EM
                                                    REVISÃO</span>
                                            @else
                                                @php $ns = Notestatus::status($list->status); @endphp
                                                <span class="badge {{ $ns->colorbg }}"
                                                    wire:click="$emitTo('components.status.show-status','showStatus', {{ $list }}, {{ $list->status }})"
                                                    style="cursor:pointer;">
                                                    {{ $ns->status }}
                                                </span>
                                            @endif
                                        </td>

                                        <td class="fw-bold fs-5">
                                            @if (!$list->block && !$list->block_wpa && !$this->blockWaiting($list->status))
                                                @if (!$list->completed)
                                                    <i class="ri-play-circle-line m-0 align-middle text-success"
                                                        style="cursor:pointer;"
                                                        wire:click="$emitTo('services.supervision.forms.jobform', 'showProduction', {{ $list }})"></i>

                                                    <i class="ri-exchange-fill m-0 align-middle text-primary ms-2"
                                                        style="cursor:pointer;"
                                                        wire:click.prevent="goTransferProd({{ $list->id }})"></i>
                                                @endif

                                                @if ($workForm && !$workForm->rejected && !$list->dfive)
                                                    <i class="ri-delete-back-2-fill m-0 align-middle text-danger ms-2"
                                                        style="cursor:pointer;"
                                                        wire:click.prevent="$emitTo('production.return.return-work', 'toReturn', {{ $list }})"></i>
                                                @endif
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

            @if ($lists->count())
                <div class="row">
                    <div class="col-6">
                        {{ $lists->links() }}
                    </div>
                    <div class="col-6 d-flex justify-content-end align-middle">
                        <span class="align-middle">
                            Exibindo {{ $lists->firstItem() }} até {{ $lists->lastItem() }} de {{ $lists->total() }}
                            registros.
                        </span>
                    </div>
                </div>
            @endif
        </div>

        <div class="tab-pane fade" id="transfer" role="tabpanel" aria-labelledby="nav-profile-tab" tabindex="0">
            @livewire('components.transprod.translist', ['service' => $service->id])
        </div>
    </div>

    {{-- Modais/Componentes auxiliares --}}
    @livewire('components.transprod.transprodlev', key('Transfer_production'))
    @livewire('services.supervision.forms.jobform', key('JobForm'))
    @livewire('components.status.show-status', key('show_status_note'))
    @livewire('partner.show.show-work-form', key('WorkFormCompany'))
    @livewire('partner.show.show-partial-info', key('PartialInfo'))
    @livewire('components.d5.d5details', key('ViewD5Details'))
    @livewire('production.return.return-work', key('returnWorkfomr'))
    </div>
</div>

@push('script')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            Livewire.emitTo('services.supervision.main', 'checkOpen');

            // Delegation: copiar texto
            const tableBody = document.getElementById('table-body');
            tableBody?.addEventListener('click', async (ev) => {
                const el = ev.target.closest('.copy-text');
                if (!el) return;
                const value = el.getAttribute('data-value');
                try {
                    if (navigator.clipboard?.writeText) {
                        await navigator.clipboard.writeText(value);
                    } else {
                        const ta = document.createElement('textarea');
                        ta.value = value;
                        document.body.appendChild(ta);
                        ta.select();
                        document.execCommand('copy');
                        document.body.removeChild(ta);
                    }
                    livewire.emit('getCopy', `Valor "${value}" copiado para a área de transferência.`);
                } catch (_) {
                    livewire.emit('getCopy', `Falha ao copiar "${value}".`);
                }
            });

            // Abrir modal por evento (mantém)
            window.addEventListener("showModal2", function(e) {
                const myModal = new bootstrap.Modal(document.getElementById(e.detail.id));
                myModal.show();
            });
        });
    </script>
@endpush
