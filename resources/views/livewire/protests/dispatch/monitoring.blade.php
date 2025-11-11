@php

    if (!function_exists('reduceName')) {
        function reduceName(string $name, bool $first = false)
        {
            $name = explode(' ', $name);

            if ($first) {
                return $name[0];
            }

            return $name[0] . ' ' . end($name);
        }
    }

    if (!function_exists('getWishDate')) {
        function getWishDate($item)
        {
            if ($item->protest?->tipoNota == 'NA') {
                return $item->protest->dtConclusaoDesej;
            }
            return $item->medProtest->dtFimMedidaDesej;
        }
    }

    if (!function_exists('getApertureDate')) {
        function getApertureDate($item)
        {
            if ($item->protest?->tipoNota == 'NA') {
                return $item->protest->dtAberturaNota;
            }
            return $item->medProtest->dtCriacaoMedida;
        }
    }
@endphp

<div>
    {{-- Loading --}}
    <x-show-loading />

    {{-- Top Controls --}}
    <div class="d-flex flex-wrap gap-3 mb-3 align-items-center">
        <div class="flex-grow-1 position-relative">
            <input wire:model.debounce.500ms="search" class="form-control" id="searchInput" placeholder="Buscar..." />
            <button type="button"
                class="btn btn-outline-secondary position-absolute end-0 top-50 translate-middle-y me-2 border-0"
                data-bs-toggle="modal" data-bs-target="#buscarMultiModal" title="Busca múltipla">
                <i class="ri-checkbox-multiple-blank-line"></i>
            </button>
        </div>

        <select class="form-select w-auto" wire:model="perPage">
            <option value="25">25</option>
            <option value="50">50</option>
            <option value="100">100</option>
        </select>
    </div>

    {{-- Filtros --}}
    <div class="d-flex flex-wrap gap-3 mb-3 align-items-end">
        <div>
            <select class="form-select" id="filter1" wire:model="deep">
                <option value="">Selecione...</option>
                @foreach ($deepList as $d)
                    <option value="{{ $d }}">Nível {{ $d }}</option>
                @endforeach
            </select>
        </div>


        <div>
            <label for="filter2" class="form-label">Usuarios Superiores</label>
            <select class="form-select" id="filter2" wire:model.defer="userViewer">
                @if (!empty($userViewerList))
                    <option value="" selected>Selecione um usuário</option>
                    @foreach ($userViewerList as $id => $name)
                        <option value="{{ $id }}">{{ $name }}</option>
                    @endforeach
                @else
                    <option value="" disabled selected>Selecione um nivel</option>
                @endif
            </select>
        </div>


        {{-- <div>
            <label for="filter3" class="form-label">Filtro 3</label>
            <select class="form-select" id="filter3" wire:model="filter3">
                <option value="">Selecione...</option>
                <option value="1">Opção 1</option>
                <option value="2">Opção 2</option>
                <option value="3">Opção 3</option>
            </select>
        </div> --}}

        <div>
            <button type="button" class="btn btn-primary" wire:click="applyFilters">
                <i class="ri-search-line me-1"></i>
                Aplicar
            </button>
            <button type="button" class="btn btn-primary" wire:click="cleanFilters">
                <i class="ri-search-line me-1"></i>
                Limpar
            </button>
        </div>
    </div>

    {{-- Header da tabela / ações --}}


    @if ($lists)
        {{-- Paginação topo --}}
        <div class="d-flex justify-content-between align-items-center mt-2">
            {{ $lists->links() }}
            <div class="text-muted small">
                Exibindo {{ $lists->firstItem() ?? 0 }} - {{ $lists->lastItem() ?? 0 }} de {{ $lists->total() }}
                registros
            </div>
        </div>

        {{-- Header de seção --}}
        <div class="card">
            <div class="card-header py-0 text-bg-danger d-flex justify-content-between align-items-center">
                <h5 class="card-title my-0">RECLAMAÇÕES EM ANDAMENTO</h5>

                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-sm btn-outline-light" title="Exportar para Excel"
                        wire:click="exportToExcel" wire:loading.attr="disabled" wire:target="exportToExcel">
                        <span wire:loading.remove wire:target="exportToExcel">
                            <i class="ri-file-excel-line me-1"></i>
                            Exportar Excel
                        </span>
                        <span wire:loading wire:target="exportToExcel">
                            <i class="spinner-border spinner-border-sm me-1" role="status"></i>
                            Exportando...
                        </span>
                    </button>


                </div>
            </div>

            {{-- Tabela --}}
            <table class="table table-sm table-striped table-condensed">
                <thead class="table-dark">
                    <tr class="align-middle text-center sticky-top" style="top: 60px;">
                        <th>Prioridade</th>
                        <th>Despachante</th>
                        <th>Tipo</th>
                        <th></th>
                        <th>Nota</th>
                        <th>Medida</th>
                        <th>Cod</th>
                        <th>TipoReclamação</th>
                        <th>Município</th>
                        <th>Responsável</th>
                        <th>Empresa</th>

                        <th>Abertura</th>
                        <th>FimDesejado</th>
                        <th>SLA</th>

                        <th>Status</th>
                        <th style="width:48px;"></th>
                    </tr>
                </thead>

                <tbody>
                    @foreach ($lists as $item)
                        @php
                            $slaLeft = now()->diffInDays($item->sla_due_at, false);
                            $slaClass = ($slaLeft < 0
                                    ? 'text-bg-danger'
                                    : $slaLeft == 0)
                                ? 'text-bg-warning'
                                : 'text-bg-success';

                        @endphp
                        <tr class="text-center">
                            <td>
                                <span class="badge {{ $item->priority_badge_class }}">{{ $item->priority_label }}</span>
                            </td>
                            <td class="fw-bold">{{ reduceName($item->creator?->name) }}
                            </td>
                            <td><span class="badge text-bg-secondary">{{ $item->protest?->tipoNota }}</span></td>
                            <td>
                                @if ($item->is_advance)
                                    <span class="badge text-bg-info">A</span>
                                @endif
                            </td>
                            <td class="fw-bold">

                                {{ $item->protest?->nota }}
                            </td>
                            <td class="fw-bold"># {{ $item->medProtest?->med_id }}</td>

                            <td><span class="badge text-bg-secondary">{{ $item->protest?->codecodf }}</span></td>

                            {{-- As demais <td> podem ser preenchidas conforme sua lógica --}}
                            <td class="text-uppercase">{{ $item->protest?->txtGrpCodificacao }}</td>
                            <td>{{ $item->protest?->cidade }}</td>
                            <td class="text-uppercase fw-bold">{{ reduceName($item->owner?->name) }}</td>
                            <td class="text-uppercase">{{ reduceName($item->owner?->company?->name, true) }}
                            </td>

                            <td>{{ getApertureDate($item) ? getApertureDate($item)->format('d/m/Y') : '---' }}</td>
                            <td>{{ getWishDate($item) ? getWishDate($item)->format('d/m/Y') : '---' }}</td>
                            <td class="fw-bold">
                                <span class="badge {{ $slaClass }}"
                                    title="Dias para o Vencimento">{{ $slaLeft }} d</span>
                            </td>
                            <td>
                                <div class="badge {{ $item->status_badge_class }}">{{ $item->status_label }}</div>
                            </td>
                            <td style="width:48px;">
                                <div class="d-flex gap-1 justify-content-center">
                                    <button type="button" class="btn btn-sm btn-outline-primary" title="Visualizar"
                                        wire:click="$emitTo('protests.dispatch.actions.view-protest-job', 'open', {{ $item->id }})">
                                        <i class="ri-eye-line"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary"
                                        wire:click="goTo({{ $item->protest?->nota }})" title="Seguir">
                                        <i class="ri-bookmark-line"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        {{-- Paginação base --}}
        <div class="d-flex justify-content-between align-items-center mt-2">
            {{ $lists->links() }}
            <div class="text-muted small">
                Exibindo {{ $lists->firstItem() ?? 0 }} - {{ $lists->lastItem() ?? 0 }} de {{ $lists->total() }}
                registros
            </div>
        </div>
    @else
        <div class="card">
            <div class="card-body text-center">
                <p>Não há registros para exibir.</p>
            </div>
        </div>
    @endif


    {{-- Drawer lateral de detalhes --}}
    @livewire('protests.dispatch.actions.view-protest-job', key('view-protest-job'))

    {{-- Modal de busca múltipla --}}
</div>
