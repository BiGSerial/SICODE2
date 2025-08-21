@php
    use App\Custom\Viabilitiesstatus;
    use App\Custom\Notestatus;
    use Carbon\Carbon;
    use App\Helpers\DaysLeft;
@endphp

@push('css')
    <style>
        /* ===================== HISTÓRICO (escopo isolado) ===================== */
        .hx-card {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 16px;
            box-shadow: 0 8px 30px rgba(17, 24, 39, .06)
        }

        .hx-card-header {
            border-bottom: 1px solid #eef2f7;
            padding: .9rem 1rem;
            border-top-left-radius: 16px;
            border-top-right-radius: 16px;
            background: #fff
        }

        .hx-card-body {
            padding: 1rem
        }

        .hx-pill {
            border-radius: 999px;
            padding: .35rem .7rem;
            font-weight: 600;
            font-size: .8rem
        }

        .hx-muted {
            color: #6b7280
        }

        .hx-strong {
            font-weight: 700
        }

        .hx-kbd {
            background: #111827;
            color: #fff;
            border-radius: 6px;
            padding: 2px 6px;
            font-size: .75rem
        }

        .hx-input {
            border: 1px solid #cbd5e1;
            border-radius: 12px;
            padding: .65rem .9rem
        }

        .hx-input:focus {
            outline: none;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, .15);
            border-color: #93c5fd
        }

        .hx-floating .form-floating>label {
            color: #6b7280
        }

        .hx-toolbar {
            display: flex;
            flex-wrap: wrap;
            gap: .6rem;
            align-items: center
        }

        .hx-split {
            display: flex;
            gap: .6rem;
            align-items: center
        }

        .hx-soft {
            background: #f8fafc;
            border: 1px solid #e5e7eb;
            border-radius: 12px
        }

        .hx-compact th,
        .hx-compact td {
            vertical-align: middle
        }

        .hx-sticky-head thead th {
            position: sticky;
            top: 0;
            background: #f8fafc;
            z-index: 2
        }

        .hx-rowbar {
            border-left: 8px solid transparent
        }

        .hx-status-deadline-danger {
            background: #fee2e2;
            color: #991b1b
        }

        .hx-status-deadline-warn {
            background: #fef3c7;
            color: #92400e
        }

        .hx-status-deadline-ok {
            background: #dcfce7;
            color: #065f46
        }

        /* animações existentes */
        .item {
            animation: slideIn .5s forwards;
            opacity: 0
        }

        .item.hidden {
            animation: slideOut .5s forwards
        }

        .detail-item {
            opacity: 0;
            animation: growDown .5s forwards;
            transform-origin: top
        }

        @keyframes growDown {
            from {
                transform: scaleY(0)
            }

            to {
                transform: scaleY(1)
            }
        }

        @keyframes slideIn {
            0% {
                opacity: 0;
                transform: translateX(100%)
            }

            100% {
                opacity: 1;
                transform: translateX(0)
            }
        }

        @keyframes blink {
            0% {
                opacity: 1
            }

            50% {
                opacity: 0
            }

            100% {
                opacity: 1
            }
        }

        .blink {
            animation: blink 2s infinite
        }

        /* ===================== MODAL Busca Multi-notas ===================== */
        .hx-ms .modal-content {
            background: linear-gradient(145deg, #1f2937, #0f172a);
            color: #e5e7eb;
            border: 0;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, .5);
            overflow: hidden
        }

        .hx-ms .modal-header {
            background: rgba(31, 41, 55, .95);
            border: 0;
            border-bottom: 1px solid rgba(255, 255, 255, .08)
        }

        .hx-ms .modal-title {
            color: #f9fafb;
            font-weight: 700;
            letter-spacing: .2px
        }

        .hx-ms .btn-close {
            filter: invert(1);
            opacity: .9
        }

        .hx-ms .ms-textarea {
            width: 100%;
            min-height: 15lh;
            resize: vertical;
            border: 0;
            padding: 18px 20px;
            background: rgba(255, 255, 255, .04);
            color: #f3f4f6;
            outline: none;
            font-family: ui-monospace, Menlo, Consolas, monospace;
            font-size: .975rem;
            line-height: 1.6;
            border-top: 1px solid rgba(255, 255, 255, .08);
            border-bottom: 1px solid rgba(255, 255, 255, .08)
        }

        .hx-ms .ms-textarea::placeholder {
            color: #9ca3af
        }

        .hx-ms .ms-textarea:focus {
            background: rgba(255, 255, 255, .06);
            box-shadow: inset 0 0 0 1px rgba(59, 130, 246, .35)
        }

        .hx-ms .modal-footer {
            background: rgba(31, 41, 55, .95);
            border: 0;
            border-top: 1px solid rgba(255, 255, 255, .08)
        }

        .hx-ms .ms-btn {
            background: #2563eb;
            color: #fff;
            border: 0;
            border-radius: 10px;
            padding: 8px 16px;
            font-weight: 700;
            box-shadow: 0 6px 18px rgba(37, 99, 235, .35)
        }

        .hx-ms .ms-btn:hover {
            background: #1d4ed8
        }

        .hx-ms .ms-btn:active {
            transform: translateY(1px)
        }

        @media (max-width: 576px) {
            .hx-toolbar {
                gap: .5rem
            }

            .hx-ms .modal-dialog {
                margin: .75rem
            }
        }
    </style>
@endpush

<div>
    <x-show-loading />

    {{-- ===================== Barra de Controles / Filtros ===================== --}}
    <div class="hx-card mb-3">
        <div class="hx-card-body">
            <div class="hx-toolbar">

                {{-- Registros por página --}}
                <div class="hx-floating">
                    <div class="form-floating">
                        <select class="form-select hx-input" wire:model="perPage" id="perPageSelect">
                            <option value="25">25</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                            <option value="250">250</option>
                            <option value="500">500</option>
                        </select>
                        <label for="perPageSelect">Registros por página</label>
                    </div>
                </div>

                {{-- Busca + Multi-busca --}}
                <div class="hx-split flex-grow-1">
                    <div class="hx-floating flex-grow-1 position-relative">
                        <div class="form-floating">
                            <input type="text" class="form-control hx-input" id="searchInput"
                                wire:model.debounce.2s="search" placeholder="Buscar">
                            <label for="searchInput">Buscar</label>
                        </div>
                        {{-- Botão para abrir modal multi-notas --}}
                        <button type="button"
                            class="btn btn-outline-secondary position-absolute end-0 top-50 translate-middle-y me-2"
                            data-bs-toggle="modal" data-bs-target="#multiSearchModal" title="Busca múltipla">
                            <i class="ri-checkbox-multiple-blank-line"></i>
                        </button>
                    </div>
                </div>

                {{-- Período --}}
                <div class="hx-floating">
                    <div class="form-floating">
                        <input type="date" id="date_in" class="form-control hx-input" wire:model="date_in"
                            placeholder="Data inicial">
                        <label for="date_in">Data inicial</label>
                    </div>
                </div>

                <div class="hx-floating">
                    <div class="form-floating">
                        <input type="date" id="date_out" class="form-control hx-input" wire:model="date_out"
                            placeholder="Data final">
                        <label for="date_out">Data final</label>
                    </div>
                </div>

                {{-- Tipo de data --}}
                <div class="hx-floating">
                    <div class="form-floating">
                        <select class="form-select hx-input" wire:model="dateBy" id="dateBySelect">
                            <option value="sended_at">Recebido</option>
                            <option value="returned_at">Viabilizado</option>
                            <option value="completed_at">Completado</option>
                        </select>
                        <label for="dateBySelect">Tipo de Data</label>
                    </div>
                </div>

                {{-- Limpar --}}
                <div>
                    <button class="btn btn-danger" wire:click.prevent='cleanAll()' title="Limpar filtros">
                        <i class="ri-find-replace-line fs-5"></i>
                    </button>
                </div>
            </div>

            {{-- Filtros Dinâmicos (mantidos) --}}
            <div class="mt-3 d-flex flex-wrap gap-2 justify-content-end">
                @livewire(
                    'components.filter.filter',
                    [
                        'myKey' => 'rubrica',
                        'sendFilter' => '',
                        'model' => 'App\Models\Note',
                        'column' => 'rubrica',
                        'filter' => 'Rubrica',
                        'group_filter' => 'hiring_hist',
                        'values' => 'rubrica',
                        'direction' => 'ASC',
                        'query' => '',
                    ],
                    key('rubrica')
                )

                @livewire(
                    'components.filter.filter',
                    [
                        'myKey' => 'region',
                        'sendFilter' => 'city',
                        'model' => 'App\Models\Edp_depc\City',
                        'column' => 'regiao',
                        'filter' => 'Região',
                        'group_filter' => 'hiring_hist',
                        'values' => 'regiao',
                        'direction' => 'ASC',
                        'query' => '',
                    ],
                    key('region')
                )

                @livewire(
                    'components.filter.filter',
                    [
                        'myKey' => 'city',
                        'sendFilter' => '',
                        'model' => 'App\Models\Edp_depc\City',
                        'column' => 'cidade',
                        'filter' => 'Município',
                        'group_filter' => 'hiring_hist',
                        'values' => 'municipio',
                        'direction' => 'ASC',
                        'query' => '',
                    ],
                    key('city')
                )

                {{-- Botão Não Contratadas --}}
                <div>
                    <button type="button" 
                            class="btn {{ $hasNoHired ? 'btn-warning' : 'btn-outline-secondary' }}"
                            wire:click="$toggle('hasNoHired')"
                            title="Mostrar apenas não contratadas">
                        <i class="ri-checkbox-blank-circle-line me-1"></i>
                        Não Contratadas
                        @if($hasNoHired)
                            <i class="ri-toggle-fill text-warning ms-1"></i>
                        @else
                            <i class="ri-toggle-line ms-1"></i>
                        @endif
                    </button>
                </div>

                @livewire('components.filter.remove-all', ['group_filter' => 'hiring_hist'], key('removeAll'))
            </div>
        </div>
    </div>

    {{-- ===================== LISTA ===================== --}}
    @if ($lists->isEmpty())
        <div class="text-center my-5 py-3">
            <h3 class="hx-muted">Nenhuma atividade encontrada</h3>
        </div>
    @endif

    @if ($lists->isNotEmpty())
        {{-- Paginador Topo --}}
        <div class="row mt-2 mb-2">
            <div class="col-6">
                {{ $lists->links() }}
            </div>
            <div class="col-6 d-flex justify-content-end align-middle">
                <span class="align-middle hx-muted">
                    Exibindo {{ $lists->firstItem() }}–{{ $lists->lastItem() }} de {{ $lists->total() }} registros.
                </span>
            </div>
        </div>

        <div class="hx-card mb-2">
            <div class="hx-card-header">
                <h5 class="m-0 d-flex align-items-center gap-2">
                    <i class="ri-history-line text-primary"></i> Histórico de Viabilidade
                </h5>
            </div>
            <div class="hx-card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm table-striped table-hover mb-0 hx-compact hx-sticky-head">
                        <thead class="hx-soft">
                            <tr>
                                <th class="text-center" style="width:34px;"></th>
                                <th class="text-center">Nota/OV</th>
                                <th class="text-center">Arquivos</th>
                                <th class="text-center">Ordem</th>
                                <th class="text-center">Enviado</th>
                                <th class="text-center">Contratado</th>
                                <th class="text-center">Empreiteira</th>
                                <th class="text-center">Responsável</th>
                                <th class="text-center">Rubrica</th>
                                <th class="text-center">Região</th>
                                <th class="text-center">Município</th>
                                <th class="text-center">Status</th>
                                <th class="text-center" style="width:46px;"></th>
                            </tr>
                        </thead>
                        <tbody class="table-group-divider">
                            @foreach ($lists as $index => $list)
                                @php
                                    $status = null;
                                    $dueDate = Carbon::parse($list->sended_at)->addDays($list->getDays() + 7);
                                    $today = Carbon::now();
                                    $daysDifference = 0;

                                    if ($dueDate) {
                                        $daysDifference = $today->diffInDays($dueDate);
                                        if ($dueDate->isBefore($today)) {
                                            $daysDifference *= -1;
                                        }

                                        if ($daysDifference < 1) {
                                            $status = ['class' => 'hx-status-deadline-danger', 'info' => 'VENCIDO'];
                                        } elseif ($daysDifference < 3) {
                                            $status = ['class' => 'hx-status-deadline-warn', 'info' => 'VENCENDO'];
                                        } else {
                                            $status = ['class' => 'hx-status-deadline-ok', 'info' => 'NO PRAZO'];
                                        }
                                    }

                                    $color = match (true) {
                                        $list->approved && !$list->rejected && !$list->tacit => 'green',
                                        !$list->approved && $list->rejected && !$list->tacit => 'red',
                                        $list->tacit => 'yellow',
                                        default => '',
                                    };

                                    if (($list->rejected || $list->approved) && !$list->completed) {
                                        $status = ['class' => 'bg-primary text-white', 'info' => 'EM AVALIAÇÃO'];
                                    }

                                    $regiao =
                                        optional($cities->Where('rdMunicipio', $list->Note->nexp)->first())->regiao ??
                                        '';
                                @endphp

                                <tr wire:key="viability-{{ $list->id }}"
                                    wire:dblclick="$emitTo('partner.actions.responserviab','getInfoResponse', {{ $list }})"
                                    class="hx-rowbar" style="cursor:pointer; border-left-color: {{ $color }};">
                                    <td></td>
                                    <td class="text-center hx-strong">{{ $list->Note->note }}</td>
                                    <td class="text-center">
                                        <x-files.select-download-list :files='$list->Note->Files' />
                                    </td>
                                    <td class="text-center">
                                        @if ($list->Orders->isNotEmpty())
                                            @foreach ($list->Orders as $order)
                                                <div>{{ $order->ordem }}</div>
                                            @endforeach
                                        @else
                                            @if ($list->Note->Orders->isNotEmpty())
                                                @foreach ($list->Note->Orders->filter(fn($o) => !(strpos($o->statusSist, 'ENT') === 0 || strpos($o->statusSist, 'ENC') === 0)) as $order)
                                                    <div>{{ $order->ordem }}</div>
                                                @endforeach
                                            @endif
                                        @endif
                                    </td>
                                    <td class="text-center hx-strong">
                                        {{ Carbon::parse($list->sended_at)->format('d/m/Y') }}
                                    </td>
                                    <td class="text-center text-success hx-strong">
                                        {{ isset($list->hired_at) ? Carbon::parse($list->hired_at)->format('d/m/Y') : '---' }}
                                    </td>
                                    <td class="text-center">
                                        {{ $list->Company->name ?? '---' }}
                                    </td>
                                    <td class="text-center">
                                        {{ $list->Engineer->name ?? '---' }}
                                    </td>
                                    <td class="text-center">{{ $list->Note->rubrica }}</td>
                                    <td class="text-center">{{ $regiao }}</td>
                                    <td class="text-center">{{ $list->Note->lexp }}</td>
                                    <td class="text-center">
                                        @php $v = Viabilitiesstatus::status($list->status); @endphp
                                        <span class="hx-pill {{ $v->colorbg }}">{{ $v->status }}</span>
                                        {{-- @if ($status)
                                            <div class="mt-1 hx-pill {{ $status['class'] }}">{{ $status['info'] }}
                                            </div>
                                        @endif --}}
                                    </td>
                                    <td class="text-center">
                                        <i class="ri-pencil-fill text-primary fs-5" style="cursor:pointer"
                                            wire:click.prevent="$emitTo('construction.hiring.actions.edit','edit_hiring', {{ $list->id }})"></i>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Paginador Rodapé --}}
        <div class="row mt-3">
            <div class="col-6">
                {{ $lists->links() }}
            </div>
            <div class="col-6 d-flex justify-content-end align-middle">
                <span class="align-middle hx-muted">
                    Exibindo {{ $lists->firstItem() }}–{{ $lists->lastItem() }} de {{ $lists->total() }} registros.
                </span>
            </div>
        </div>
    @endif

    {{-- ===================== Modais Livewire Existentes ===================== --}}
    @livewire('partner.actions.responserviab', key('reesponser_modal_viab'))
    @livewire('construction.hiring.actions.edit', key('hiring-edit'))

    {{-- ===================== Modal: Busca Multi-notas ===================== --}}
    <div wire:ignore.self class="modal fade hx-ms" id="multiSearchModal" tabindex="-1"
        aria-labelledby="multiSearchModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="multiSearchModalLabel">Busca Multi-notas</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <textarea class="ms-textarea" rows="15" wire:model.defer="advancedSearch" wire:keydown.ctrl.enter="buscarMulti"
                    placeholder="Cole aqui várias notas, uma por linha.&#10;Exemplo:&#10;123456&#10;987654&#10;ABC-2024-001"></textarea>
                <div class="modal-footer">
                    <div class="hx-muted small me-auto">
                        Dica: <span class="hx-kbd">Ctrl</span> + <span class="hx-kbd">Enter</span> para buscar.
                    </div>
                    <button type="button" class="ms-btn" wire:click="buscarMulti">
                        <i class="ri-search-line me-1"></i> Buscar
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- ===================== Scripts ===================== --}}
    <script>
        document.addEventListener('livewire:load', function() {
            const dateIn = document.getElementById('date_in');
            const dateOut = document.getElementById('date_out');

            function setMin() {
                if (dateIn && dateOut && dateIn.value) dateOut.min = dateIn.value;
            }
            if (dateIn) {
                dateIn.addEventListener('change', setMin);
                setMin();
                dateIn.addEventListener('keydown', e => e.preventDefault());
            }
            if (dateOut) {
                dateOut.addEventListener('keydown', e => e.preventDefault());
            }
        });

        // (Opcional) handler para fechar modal via evento
        window.addEventListener('hide-bs-modal', (e) => {
            const id = e.detail?.id;
            if (!id) return;
            const el = document.getElementById(id);
            if (!el) return;
            const m = bootstrap.Modal.getOrCreateInstance(el);
            m.hide();
        });
    </script>
</div>
