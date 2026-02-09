@php
    use App\Custom\Viabilitiesstatus;
    use App\Custom\Notestatus;
    use Carbon\Carbon;
@endphp
<div class="oexterno-page">
    <x-show-loading />
    <style>
        .oexterno-page {
            --oe-bg: #f6f7fb;
            --oe-surface: #ffffff;
            --oe-ink: #1f2933;
            --oe-muted: #6b7280;
            --oe-border: #e5e7eb;
            background: radial-gradient(circle at 10% 0%, #eef2ff, transparent 40%),
                radial-gradient(circle at 90% 10%, #ecfeff, transparent 35%),
                var(--oe-bg);
            padding: 1.5rem 0;
        }

        .oexterno-header {
            background: linear-gradient(120deg, #0f172a, #0f766e 70%);
            color: #f8fafc;
            border-radius: 0.45rem;
            padding: 1.25rem 1.5rem;
            box-shadow: 0 16px 40px rgba(15, 23, 42, 0.2);
            margin-bottom: 1rem;
        }

        .oexterno-header h2 {
            font-weight: 700;
            letter-spacing: 0.02em;
            margin: 0;
        }

        .oexterno-header .meta {
            color: rgba(248, 250, 252, 0.78);
            font-size: 0.95rem;
        }

        .filters-grid .filter-card {
            background-color: var(--oe-surface);
            border: 1px solid var(--oe-border);
            border-radius: 0.45rem;
            padding: 1rem 1.25rem;
            height: 100%;
            box-shadow: 0 12px 24px rgba(15, 23, 42, 0.06);
        }

        .summary-bar {
            background: var(--oe-surface);
            border: 1px solid var(--oe-border);
            border-radius: 0.45rem;
            padding: 0.75rem 1.25rem;
            box-shadow: 0 10px 24px rgba(15, 23, 42, 0.05);
        }

        .summary-bar .summary-item {
            font-size: 0.92rem;
            color: var(--oe-muted);
        }

        .summary-bar .summary-item strong {
            color: var(--oe-ink);
        }

        .table-card {
            background: var(--oe-surface);
            border: 1px solid var(--oe-border);
            border-radius: 0.45rem;
            box-shadow: 0 16px 32px rgba(15, 23, 42, 0.08);
            overflow: hidden;
        }

        .table-card .table thead th {
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            white-space: nowrap;
        }

        .table-card .table tbody td {
            font-size: 0.92rem;
        }

        .table-card .badge {
            border-radius: 0.2rem;
        }
    </style>

    <div class="container-fluid">
        <div class="oexterno-header d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3">
            <div>
                <h2>RETORNO INTERNO (RI) {{ mb_strtoupper($service->service) }}</h2>
                <div class="meta">Gestao de retornos internos e atribuicao de atividade</div>
            </div>
            <div class="text-lg-end">
                <div class="meta">Registros em tela</div>
                <div><strong>{{ $lists->total() }}</strong></div>
            </div>
        </div>

        <div class="card mb-3 border-0 bg-transparent">
            <div class="card-body px-0">
                <div class="row g-3 filters-grid">
                    <div class="col-12">
                        <div class="filter-card">
                            <div class="row align-items-center">
                                <div class="col-md-6 d-flex flex-wrap align-items-center gap-3">
                    <div class="d-flex align-items-center">
                        <label for="perPage" class="me-2 mb-0 fw-bold">Páginas:</label>
                        <select id="perPage" wire:model="perPage" class="form-select form-select-sm"
                            style="width: auto;">
                            <option value="10">10</option>
                            <option value="25">25</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                            <option value="250">250</option>
                            <option value="500">500</option>
                        </select>
                    </div>
                    <div class="d-flex align-items-center">
                        <label for="search" class="me-2 mb-0 fw-bold">Busca:</label>
                        <input id="search" type="text" wire:model="search" class="form-control form-control-sm"
                            placeholder="Buscar...">
                    </div>
                    <button class="btn {{ $notAtt ? 'btn-primary' : 'btn-outline-primary' }} btn-sm"
                        wire:click.prevent="setNotAtt" data-bs-toggle="tooltip" data-bs-placement="top"
                        title="{{ $notAtt ? 'Filtro ativado' : 'Filtro desativado' }}">
                        <i class="ri-filter-line me-1"></i> Sem Atribuição
                    </button>
                </div>
                <div
                    class="col-md-6 d-flex justify-content-md-end justify-content-start align-items-center gap-2 mt-2 mt-md-0">

                    @livewire('components.filter.filter', ['myKey' => 'rubrica', 'sendFilter' => '', 'model' => 'App\Models\Note', 'column' => 'rubrica', 'filter' => 'Rubrica', 'group_filter' => 'd5controls', 'values' => 'rubrica', 'direction' => 'ASC', 'query' => ''], key('rubrica'))
                    @livewire('components.filter.filter', ['myKey' => 'region', 'sendFilter' => 'regional', 'model' => 'App\Models\Edp_depc\City', 'column' => 'regiao', 'filter' => 'Regiao', 'group_filter' => 'd5controls', 'values' => 'regiao', 'direction' => 'ASC', 'query' => ''], key('region'))
                    @livewire('components.filter.filter', ['myKey' => 'regional', 'sendFilter' => 'city', 'model' => 'App\Models\Edp_depc\City', 'column' => 'regional', 'filter' => 'Regional', 'group_filter' => 'd5controls', 'values' => 'regional', 'direction' => 'ASC', 'query' => ''], key('regional'))
                    @livewire('components.filter.filter', ['myKey' => 'city', 'sendFilter' => '', 'model' => 'App\Models\Edp_depc\City', 'column' => 'cidade', 'filter' => 'Municipio', 'group_filter' => 'd5controls', 'values' => 'cidade', 'direction' => 'ASC', 'query' => ''], key('city'))
                    @livewire('components.filter.remove-all', ['group_filter' => 'd5controls'], key('removeAll'))
                    {{-- <button class="btn btn-sm btn-danger" wire:click.prevent="cleanUser" wire:target="cleanUser"
                        @disabled(!$filterUser) wire:loading.attr="disabled" data-bs-toggle="tooltip"
                        data-bs-placement="top" data-bs-title="Limpar Filtro Usuario">
                        <span wire:target="cleanUser" wire:loading.remove>
                            <i class="ri-filter-off-line fs-5"></i>
                        </span>
                        <span wire:target="cleanUser" wire:loading>
                            <div class="spinner-border spinner-border-sm" role="status">
                                <span class="visually-hidden">Carregando...</span>
                            </div>
                        </span>
                    </button> --}}
                    <button class="btn btn-sm btn-primary" wire:click.prevent="exportToExcel"
                        wire:target="exportToExcel" wire:loading.attr="disabled" data-bs-toggle="tooltip"
                        data-bs-placement="top" data-bs-title="Exportar para Excel">
                        <span wire:target="exportToExcel" wire:loading.remove>
                            <i class="ri-file-excel-2-line fs-5"></i>
                        </span>
                        <span wire:target="exportToExcel" wire:loading>
                            <div class="spinner-border spinner-border-sm" role="status">
                                <span class="visually-hidden">Carregando...</span>
                            </div>
                        </span>
                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="summary-bar mb-3">
            <div class="row align-items-center">
                <div class="col-12 col-lg-6">
                    {{ $lists->links() }}
                </div>
                <div class="col-12 col-lg-6 text-lg-end">
                    <div class="summary-item">
                        Exibindo <strong>{{ $lists->firstItem() }}</strong> ate
                        <strong>{{ $lists->lastItem() }}</strong> de
                        <strong>{{ $lists->total() }}</strong> registros.
                    </div>
                </div>
            </div>
        </div>

    <div class="table-card">
        <div
            class="card-header fw-bold text-bg-secondary d-flex justify-content-between align-items-center">
            <h4 class="my-1 py-0">LISTA EM RETORNO INTERNO</h4>
            <button class="btn btn-sm btn-primary" wire:click.prevent="massAssign" wire:target="massAssign"
                data-bs-toggle="tooltip" data-bs-placement="left" title="Atribuição em Massa">
                <i class="ri-user-shared-line me-1"></i> Atribuir em Massa
            </button>
        </div>
        <div class="card-body py-2 border-bottom">
            <div class="d-flex flex-wrap align-items-center gap-2">
                <span class="fw-bold text-secondary me-1">Legenda de Origem:</span>
                <span class="badge text-bg-secondary"><i class="ri-checkbox-blank-circle-fill me-1"></i>Contratação</span>
                <span class="badge text-bg-warning text-dark"><i class="ri-checkbox-blank-circle-fill me-1"></i>Análise de Projeto</span>
                <span class="badge text-bg-info text-dark"><i class="ri-checkbox-blank-circle-fill me-1"></i>Viabilidade</span>
                <span class="badge text-bg-primary"><i class="ri-checkbox-blank-circle-fill me-1"></i>Órgão Externo</span>
            </div>
        </div>
        <div class="table-responsive">
        <table class="table table-sm table-condensed table-striped-columns table-hover mb-0">
            <thead>
                <th class="text-center"><input type="checkbox" class="form-checkbox" wire:model="selectAll"></th>
                <th scope="col" class="text-center">
                    <span href="#" wire:click.prevent="sortBy('note')" style="cursor: pointer;">Nota</span>
                    @if ($sortField == 'note')
                        <i class="bx bx-sort-{{ $sortDirection == 'asc' ? 'up' : 'down' }}"></i>
                    @endif
                </th>
                <th scope="col" class="text-center">Files</th>
                <th scope="col" class="text-center">
                    <span href="#" wire:click.prevent="sortBy('rubrica')" style="cursor: pointer;">Rubrica</span>
                    @if ($sortField == 'rubrica')
                        <i class="bx bx-sort-{{ $sortDirection == 'asc' ? 'up' : 'down' }}"></i>
                    @endif
                </th>
                <th scope="col" class="text-center">
                    <span href="#" wire:click.prevent="sortBy('lexp')" style="cursor: pointer;">Municipio</span>
                    @if ($sortField == 'lexp')
                        <i class="bx bx-sort-{{ $sortDirection == 'asc' ? 'up' : 'down' }}"></i>
                    @endif
                </th>
                <th scope="col" class="text-center">Grp5
                    <span href="#" wire:click.prevent="sortBy('group5')" style="cursor: pointer;">Grp5</span>
                    @if ($sortField == 'group5')
                        <i class="bx bx-sort-{{ $sortDirection == 'asc' ? 'up' : 'down' }}"></i>
                    @endif
                </th>
                <th scope="col" class="text-center">
                    <span href="#" wire:click.prevent="sortBy('material')"
                        style="cursor: pointer;">Material</span>
                    @if ($sortField == 'material')
                        <i class="bx bx-sort-{{ $sortDirection == 'asc' ? 'up' : 'down' }}"></i>
                    @endif
                </th>
                <th scope="col" class="text-center">
                    <span href="#" wire:click.prevent="sortBy('category')"
                        style="cursor: pointer;">Categoria</span>
                    @if ($sortField == 'category')
                        <i class="bx bx-sort-{{ $sortDirection == 'asc' ? 'up' : 'down' }}"></i>
                    @endif
                </th>
                <th scope="col" class="text-center">
                    <span href="#" wire:click.prevent="sortBy('created_at')" style="cursor: pointer;">Data
                        Envio</span>
                    @if ($sortField == 'created_at')
                        <i class="bx bx-sort-{{ $sortDirection == 'asc' ? 'up' : 'down' }}"></i>
                    @endif
                </th>
                <th scope="col" class="text-center">Em Atividade</th>
                <th scope="col" class="text-center">Status</th>
                <th scope="col" class="text-center">Responsável</th>
                <th scope="col" class="text-center">Empresa</th>
                <th scope="col" class="text-center"></th>
            </thead>
            <tbody class="table-group-divider">
                @if ($lists)
                    @foreach ($lists as $list)
                        @php
                            $vencido = false;
                            $vencimento = Carbon::now()->subHours(24)->toDateTimeString();
                            if ($list->updated_at < $vencimento) {
                                $vencido = true;
                            }

                            $color = 'text-bg-secondary';
                            $origin = 'Contratação';

                            if ($list->Approvals->isNotEmpty()) {
                                $color = 'text-bg-warning';
                                $origin = 'Análise de Projeto';
                            }

                            if ($list->Waiting) {
                                $color = 'text-bg-secondary';
                                $origin = 'Contratação';
                            }

                            if ($list->Viabilities->isNotEmpty()) {
                                $color = 'text-bg-info';
                                $origin = 'Viabilidade';
                            }

                            if ($list->Externals->isNotEmpty()) {
                                $color = 'text-bg-primary';
                                $origin = 'Órgão Externo';
                            }

                        @endphp

                        <tr wire:key="row-{{ $list->id }}">
                            <td class="text-center align-middle">
                                <input type="checkbox" class="form-checkbox" wire:model.defer="selected"
                                    value="{{ $list->id }}">
                            </td>
                            <td class="{{ $color }} text-center align-middle fw-bold">
                                <div class="d-flex flex-column align-items-center">
                                    <span>{{ $list->Note->note }}</span>
                                    <small class="badge bg-light text-dark mt-1" title="Origem da atividade">
                                        {{ $origin }}
                                    </small>
                                </div>
                                @if ($list->Note->pze == '25')
                                    <span tabindex="0" data-bs-toggle="popover" data-bs-trigger="hover focus"
                                        data-bs-placement="top" data-bs-title="NOTA EXPRESSA"
                                        data-bs-content="Nota com prazo de execução de {{ $list->Note->pze }} dias"
                                        style="z-index: 9999;" data-bs-toggle="tooltip" data-bs-placement="top">
                                        <i class="ri-fire-line text-danger fw-bold"
                                            style="display: inline-block; animation: flame 1s steps(1) infinite;"></i>
                                        @once
                                            @push('css')
                                                <style>
                                                    @keyframes flame {
                                                        0% {
                                                            transform: scaleX(1) scaleY(1);
                                                        }
                                                        25% {
                                                            transform: scaleX(1) scaleY(0.8);
                                                        }
                                                        50% {
                                                            transform: scaleX(-1) scaleY(0.8); 
                                                        }
                                                        75% {
                                                            transform: scaleX(-1) scaleY(1);
                                                        }
                                                    }
                                                </style>
                                            @endpush
                                        @endonce
                                    </span>
                                @endif
                            </td>
                            <td class="text-center align-middle">
                                {{-- Componente para gerar a lista de arquivos, precisa do array de Arquivos --}}
                                <x-files.select-download-list :files='$list->Note->Files' />

                            </td>
                            <td class="text-center align-middle">{{ $list->Note->rubrica }}</td>
                            <td class="text-center align-middle">{{ $list->Note->lexp }}</td>
                            <td class="text-center align-middle">{{ $list->Note->group5 }}</td>
                            <td class="text-center align-middle">{{ $list->Note->material }}</td>
                            <td class="text-center align-middle" style="cursor: pointer; color: inherit;"
                                wire:dblclick="$emitTo('dispatchs.common.reclaim-info', 'getInfoResponse', '{{ $list->id }}')"
                                onmouseover="this.style.color='blue';" onmouseout="this.style.color='inherit';"
                                data-bs-toggle="tooltip" data-bs-placement="top" title="Duplo clique para detalhes">
                                {{ $list->category }}
                            </td>
                            <td class="text-center align-middle">
                                {{ Carbon::parse($list->created_at)->format('d/m/Y H:i') }}
                            </td>
                            <td
                                class="text-center align-middle
                                @if ($vencido) text-bg-danger @endif
                                ">
                                {{ Carbon::parse($list->created_at)->diffForHumans(Carbon::now(), ['locale' => 'pt_br', 'syntax' => \Carbon\CarbonInterface::DIFF_ABSOLUTE]) }}
                            </td>
                            <td class="text-center align-middle">
                                @if ($list->Production)
                                    <span class="badge {{ Notestatus::status($list->Production->status)->colorbg }}">
                                        {{ Notestatus::status($list->Production->status)->status }}</span>
                                @else
                                    <span class="badge text-bg-secondary">
                                        Aguardando Atribuição</span>
                                @endif

                            </td>
                            <td class="text-center align-middle">
                                {{ $list->Production ? ($list->Production->User ? $list->Production->User->name : 'Desconhecido') : '' }}
                            </td>
                            <td class="text-center align-middle">
                                {{ $list->Production ? ($list->Production->Company ? $list->Production->Company->name : 'Desconhecido') : '' }}
                            </td>
                            <td class="text-center align-middle">
                                @if ($list->Production)
                                    <i class="ri-arrow-left-right-fill text-danger fs-5"
                                        wire:click.prevent="$emitTo('dispatchs.users.richange-user','goChangeUser' , {{ $list->id }})"
                                        style='cursor: pointer;'></i>
                                @else
                                    <i class="ri-user-add-line text-primary fs-5"
                                        wire:click.prevent="$emitTo('dispatchs.users.riatt-user','goAttUser' , {{ $list->id }})"
                                        style='cursor: pointer;'></i>
                                @endif

                            </td>
                        </tr>
                    @endforeach
                @endif

            </tbody>
        </table>
        </div>
    </div>
        <div class="summary-bar mt-3">
            <div class="row align-items-center">
                <div class="col-12 col-lg-6">
                    {{ $lists->links() }}
                </div>
                <div class="col-12 col-lg-6 text-lg-end">
                    <div class="summary-item">
                        Exibindo <strong>{{ $lists->firstItem() }}</strong> ate
                        <strong>{{ $lists->lastItem() }}</strong> de
                        <strong>{{ $lists->total() }}</strong> registros.
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>


{{-- Livewires Components Functions --}}
@livewire('dispatchs.users.richange-user', key('change-users-intern-return'))
@livewire('dispatchs.users.riatt-user', ['service' => $service], key('att-users-intern-return'))
@livewire('dispatchs.common.reclaim-info', key('reclaim-info-intern-return'))
@livewire('dispatchs.common.return-in-mass', ['service' => $service], key('return-in-mass-table'))

<!-- Exibir os dados do clipboard com formatação para Excel -->
<textarea id="clipboard-data" style="display: none;">
            @if (count($clipboardData))
@foreach ($clipboardData as $row)
{{ implode("\t", $row) }}
@endforeach
@else
SEM DADOS
@endif
        </textarea>
</div>
