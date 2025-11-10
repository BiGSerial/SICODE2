@push('css')
    <style>
        .item {
            animation: slideIn 0.5s forwards;
            opacity: 0;
        }

        .item.hidden {
            animation: slideOut 0.5s forwards;
        }

        .detail-item {
            opacity: 0;
            animation: growDown 0.5s forwards;
            transform-origin: top;
        }

        @keyframes growDown {
            from {
                transform: scaleY(0);
            }

            to {
                transform: scaleY(1);
            }
        }

        @keyframes slideIn {
            0% {
                opacity: 0;
                transform: translateX(100%);
            }

            100% {
                opacity: 1;
                transform: translateX(0);
            }
        }

        @keyframes blink {
            0% {
                opacity: 1;
            }

            50% {
                opacity: 0;
            }

            100% {
                opacity: 1;
            }
        }

        .blink {
            animation: blink 2s infinite;
        }
    </style>
@endpush

<div>
    <x-show-loading />

    {{-- Filtros / Busca --}}
    <div class="card mb-3">
        <div class="card-body">
            <div class="row g-2">
                <div class="col-1">
                    <select class="form-select border border-secondary" wire:model="perPage">
                        <option value="25">25</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                        <option value="250">250</option>
                        <option value="500">500</option>
                    </select>
                </div>
                <div class="col-2">
                    <input type="text" class="form-control border border-secondary" placeholder="Buscar"
                        wire:model.debounce.600ms="search">
                </div>
                <div class="col-3"></div>
                <div class="col-6 d-flex justify-content-end">
                    @livewire('components.filter.filter', ['myKey' => 'txpriority', 'sendFilter' => '', 'model' => 'App\Models\Note', 'column' => 'txpriority', 'filter' => 'Criticidade', 'group_filter' => 'partner', 'values' => 'txpriority', 'direction' => 'ASC', 'query' => ''], key('criticidade'))
                    @livewire('components.filter.filter', ['myKey' => 'rubrica', 'sendFilter' => '', 'model' => 'App\Models\Note', 'column' => 'rubrica', 'filter' => 'Rubrica', 'group_filter' => 'partner', 'values' => 'rubrica', 'direction' => 'ASC', 'query' => ''], key('rubrica'))
                    @livewire('components.filter.filter', ['myKey' => 'region', 'sendFilter' => 'city', 'model' => 'App\Models\Edp_depc\City', 'column' => 'regiao', 'filter' => 'Regiao', 'group_filter' => 'partner', 'values' => 'regiao', 'direction' => 'ASC', 'query' => ''], key('region'))
                    @livewire('components.filter.filter', ['myKey' => 'city', 'sendFilter' => '', 'model' => 'App\Models\Edp_depc\City', 'column' => 'cidade', 'filter' => 'Municipio', 'group_filter' => 'partner', 'values' => 'municipio', 'direction' => 'ASC', 'query' => ''], key('city'))
                    @livewire('components.filter.remove-all', ['group_filter' => 'partner'], key('removeAll'))
                </div>
            </div>
        </div>
    </div>

    {{-- Lista --}}
    @if (!$lists->count())
        <div class="text-center my-5 py-3">
            <h3>NENHUMA ATIVIDADE ENCONTRADA</h3>
        </div>
    @else
        <div class="row mt-3">
            <div class="col-6">{{ $lists->links() }}</div>
            <div class="col-6 d-flex justify-content-end align-middle">
                <span class="align-middle">
                    Exibindo {{ $lists->firstItem() }} até {{ $lists->lastItem() }} de {{ $lists->total() }} registros.
                </span>
            </div>
        </div>

        <div class="card mb-2 edp-bg-gray">
            <div class="card-header edp-bg-seoweedgreen-100 text-white">
                <div class="row">
                    <div class="col">
                        <h4 class="my-0">VIABILIDADE A EXECUTAR</h4>
                    </div>
                    <div class="col-3 d-flex justify-content-end">
                        <a href="{{ route('pdf.checklist', ['id' => 0]) }}" target="_BLANK"
                            class="btn btn-sm btn-primary">
                            <i class="bx bx-printer fs-4 ms-2 align-middle" data-bs-toggle="popover"
                                data-bs-trigger="hover focus" data-bs-placement="right"
                                data-bs-title="Imprimir Check-List FTVEO (Genérica)"
                                data-bs-content="<p>Abre para impressão a Ficha Técnica de Viabilidade e Execução de Obras (Obrigatório Anexar no Sicode ao Retornar a Viabilidade).</p>"></i>
                        </a>
                        <button class="btn btn-sm btn-primary ms-2" wire:click.prevent="export_excel">
                            <i class="ri-file-excel-2-line align-middle"></i> Exportar
                        </button>
                    </div>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-sm table-condensed table-striped table-hover">
                    <thead>
                        <tr>
                            <th class="text-center align-middle" style="width: 10px;"></th>
                            <th class="text-center align-middle">Nota/OV</th>
                            <th class="text-center align-middle">Criticidade</th>
                            <th class="text-center align-middle">Arquivos</th>
                            <th class="text-center align-middle">Ordem</th>
                            <th class="text-center align-middle">Contratado</th>
                            <th class="text-center align-middle">Recebido</th>
                            <th class="text-center align-middle">Prazo Viab</th>
                            <th class="text-center align-middle">Prazo Obra</th>
                            <th class="text-center align-middle">Rubrica</th>
                            <th class="text-center align-middle">Material</th>
                            <th class="text-center align-middle">Regiao</th>
                            <th class="text-center align-middle">Municipio</th>
                            <th class="text-center align-middle">Status</th>
                            <th class="text-center align-middle">Empreiteira</th>
                            <th class="text-center align-middle"></th>
                            <th class="text-center align-middle"></th>
                        </tr>
                    </thead>

                    <tbody class="table-group-divider">
                        @foreach ($lists as $viability)
                            @php
                                $orders = $viability->Note->orders->reject(function ($order) {
                                    return str_starts_with($order->statusSist, 'ENT') ||
                                        str_starts_with($order->statusSist, 'ENC');
                                });

                                $dueDate = $viability->sended_at
                                    ? $viability->sended_at->addDays($viability->getDays() + 7)
                                    : null;

                                $lastDate = new \App\Helpers\DaysLeft($viability->Note);

                                if ($viability->tacit) {
                                    $color = 'yelow';
                                } elseif ($viability->rejected) {
                                    $color = 'red';
                                } elseif ($viability->approved) {
                                    $color = 'green';
                                } elseif (!$viability->approved && !$viability->rejected && !$viability->completed) {
                                    $color = 'blue';
                                } else {
                                    $color = 'gray';
                                }
                            @endphp
                            <tr wire:key="viability-{{ $viability->id }}"
                                wire:dblclick="$emitTo('partner.actions.responserviab','getInfoResponse', {{ $viability->id }})"
                                style="cursor:pointer; border-left: 8px solid {{ $color }};">
                                <td></td>
                                <td
                                    class="text-center align-middle @if ($viability->Note->is45) text-bg-warning @endif">
                                    {{ $viability->Note->note }}
                                    @if ($viability->Note->is45)
                                        <span tabindex="0" data-bs-toggle="popover" data-bs-trigger="hover focus"
                                            data-bs-placement="top" data-bs-title="NOTA EXPRESSA"
                                            data-bs-content="Nota com prazo de execução de 45 dias"
                                            style="z-index:9999;">
                                            <i class="ri-fire-line text-danger fw-bold"
                                                style="display:inline-block; animation:flame 1s steps(1) infinite;"></i>
                                        </span>
                                    @endif
                                </td>
                                <td class="text-center align-middle fw-bold">
                                    {{ $viability->Note->txpriority ?? 'Normal' }}
                                </td>
                                <td class="text-center align-middle">
                                    @if ($viability?->Note?->Files?->isNotEmpty())
                                        <x-files.select-download-list :files="$viability?->Note?->Files" />
                                    @endif

                                </td>


                                <td class="text-center align-middle">
                                    @forelse($orders as $order)
                                        <p class="my-0 py-0">{{ $order->ordem }}</p>
                                    @empty
                                        ---
                                    @endforelse
                                </td>
                                <td class="text-center align-middle">
                                    {{ $viability->hired ? 'SIM' : 'NÃO' }}
                                </td>
                                <td class="text-center align-middle fw-bold">
                                    {{ $viability->sended_at?->format('d/m/Y') }}
                                </td>
                                <td class="text-center align-middle text-danger fw-bold">
                                    {{ $dueDate?->format('d/m/Y') }}
                                </td>
                                <td class="text-center align-middle fw-bold text-primary">
                                    {{ $lastDate->getLastDate() }}
                                </td>
                                <td class="text-center align-middle">
                                    {{ $viability->note->rubrica }}
                                </td>
                                <td class="text-center align-middle">
                                    {{ $viability->note->material }}
                                </td>
                                <td class="text-center align-middle">
                                    {{ $viability->note->City?->regiao }}
                                </td>
                                <td class="text-center align-middle">
                                    {{ $viability->note->lexp }}
                                </td>
                                <td class="text-center align-middle">
                                    <span
                                        class="badge {{ \App\Custom\Viabilitiesstatus::status($viability->status)->colorbg }}">{{ \App\Custom\Viabilitiesstatus::status($viability->status)->status }}</span>
                                </td>
                                <td class="text-center align-middle">
                                    {{ $viability->company?->name }}
                                </td>

                                <td class="text-center align-middle">
                                    <a href="{{ route('pdf.checklist', ['id' => $viability->id]) }}" target="_BLANK"
                                        class="text-primary">
                                        <i class="bx bx-printer text-primary fs-4 me-2" data-bs-toggle="popover"
                                            data-bs-trigger="hover focus" data-bs-placement="right"
                                            data-bs-title="Imprimir Check-List FTVEO"
                                            data-bs-content="<p>Abre para impressão a Ficha Técnica de Viabilidade e Execução de Obras (Obrigatório Anexar no Sicode ao Retornar a Viabilidade).</p>"></i>
                                    </a>

                                    @php
                                        $blockCmd = $viability->approved || $viability->rejected;
                                    @endphp

                                    @unless ($blockCmd)
                                        <i class="bx bxs-badge-check text-success fs-4 me-2" style="cursor:pointer;"
                                            wire:click.prevent="$emitTo('partner.forms.return-viability', 'openViability', '{{ $viability->id }}')"
                                            data-bs-toggle="popover" data-bs-trigger="hover focus"
                                            data-bs-placement="right" data-bs-title="Encerrar Atividade"
                                            data-bs-content="<p>Entrega os informes da Obra.</p>"></i>
                                    @endunless
                                </td>

                                <td class="align-middle">
                                    <input class="form-check-input border border-secondary" type="checkbox"
                                        wire:click.prevent="putInActivity({{ $viability->id }})"
                                        @checked($this->checkInActivity($viability))>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="row mt-3">
            <div class="col-6">{{ $lists->links() }}</div>
            <div class="col-6 d-flex justify-content-end align-middle">
                <span class="align-middle">
                    Exibindo {{ $lists->firstItem() }} até {{ $lists->lastItem() }} de {{ $lists->total() }}
                    registros.
                </span>
            </div>
        </div>
    @endif

    {{-- Modais Livewire --}}
    @livewire('partner.actions.responserviab', key('responser_modal_viab'))
    @livewire('partner.forms.return-viability', key('responser_viab_form'))
</div>
