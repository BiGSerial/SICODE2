@php
    use Carbon\Carbon;
    use App\Custom\Notestatus;
    use App\Custom\WpaStatus;
@endphp
<div>

    {{-- Carrega o Loading da página --}}
    <x-show-loading />

    <div class="mb-4">
        <div class="row">
            <div class="col-md-8">
                 {{-- Busca + Multi-busca --}}
                <div class="hx-split flex-grow-1">
                    <div class="hx-floating flex-grow-1 position-relative">
                        <div class="input-group">
                            <div class="form-floating flex-grow-1">
                                <input type="text" class="form-control hx-input" id="searchInput"
                                    wire:model.debounce.2s="search" placeholder="Buscar">
                                <label for="searchInput">Buscar</label>
                            </div>
                            {{-- Botão para abrir modal multi-notas --}}
                            <button type="button" class="btn btn-outline-secondary"
                                data-bs-toggle="modal" data-bs-target="#multiSearchModal" title="Busca múltipla">
                                <i class="ri-checkbox-multiple-blank-line"></i>
                            </button>
                            <button class="btn btn-outline-secondary" type="button" wire:click="resetFilters" title="Limpar filtros">
                                <i class="ri-filter-off-line"></i>
                            </button>
                        </div>
                    </div>
                </div>
                {{-- <div class="input-group mb-3">
                    <span class="input-group-text bg-primary text-white">
                        <i class="fas fa-search"></i>
                    </span>
                    <input type="text" class="form-control form-control-lg" placeholder="Pesquisar..."
                        wire:model.debounce.300ms="search" aria-label="Pesquisar">
                    <button class="btn btn-outline-secondary" type="button" wire:click="resetFilters">
                        <i class="fas fa-times"></i> Limpar
                    </button>
                </div> --}}
                {{-- <livewire:components.filter.smart-filters :config="$filtersConfig" wire:key="filters-main" /> --}}
            </div>
            <div class="col-md-4">
                <div class="alert alert-warning border-start border-warning border-5 shadow-sm" role="alert">
                    <h5 class="alert-heading"><i class="fas fa-exclamation-triangle me-2"></i>Aviso</h5>
                    <p class="mb-0">Esta página está operando em <strong>modo provisório</strong> e está sendo
                        reconstruída para melhor desempenho. Algumas funcionalidades podem estar limitadas.</p>
                </div>
            </div>
        </div>
    </div>

    {{-- <x-showselected :count="$selected" /> --}}

    <div class="mb-3">
        <div class="btn-group" role="group" aria-label="Filter by status">
            @foreach ($statusList as $key => $value)
                <button type="button"
                    class="btn btn-{{ Notestatus::status($key)->color }} position-relative @if ($statusFilter === $key) border-bottom border-dark border-3 @endif"
                    style="@if ($statusFilter === $key) border-left: none; border-right: none; border-top: none; @endif"
                    wire:click="$set('statusFilter', {{ $statusFilter === $key ? 'null' : $key }})">
                    {{ Notestatus::status($key)->status }}
                    <span class="badge bg-light text-dark">
                        {{ $value }}
                    </span>
                </button>
            @endforeach
        </div>
    </div>

    @if ($lists->isNotEmpty())
        <div class="card">
            <div class="card-header py-0 text-bg-danger">
                <h5 class="card-title my-0">CONTROLE DE LEVANTAMENTO</h5>
            </div>
            <table class="table table-sm table-striped table-condensed">
                <thead>
                    <tr clas="">
                        <th>#</th>
                        <th>#</th>
                        <th>Note</th>
                        <th>DD</th>
                        <th>Rubrica</th>
                        <th>Municipio</th>
                        <th>Postes</th>
                        <th>Usuário</th>
                        <th>AttAt</th>

                        <th>Status</th>
                        <th>#</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        if (!function_exists('shortUser')) {
                            function shortUser($name)
                            {
                                if (empty($name)) {
                                    return 'Desconhecido';
                                }

                                $parts = explode(' ', $name);
                                $shortName = $parts[0] . ' ' . end($parts); // Começa com o primeiro nome

                                return $shortName;
                            }
                        }
                    @endphp
                    @foreach ($lists as $item)
                        @php

                            if ($item->priority) {
                                $rowClass = [
                                    'color' => 'table-danger',
                                    'color-text' => 'text-danger',
                                    'info' => 'Prioridade',
                                ];
                            } else {
                                $rowClass = [
                                    'color' => '',
                                    'color-text' => '',
                                    'info' => '',
                                ];
                            }

                            // if ($item->partial) {
                            //     $type['init'] = 'P';
                            //     $type['info'] = 'Parcial';
                            //     $type['color'] = 'text-bg-warning';
                            // } else {
                            //     $type['init'] = 'F';
                            //     $type['info'] = 'Final';
                            //     $type['color'] = 'text-bg-success';
                            // }

                            if ($item->d5) {
                                $status['init'] = 'RI';
                                $status['info'] = 'Retorno Interno';
                                $status['color'] = 'text-bg-primary';
                            } elseif ($item->dfive) {
                                $status['init'] = 'D5';
                                $status['info'] = 'D5';
                                $status['color'] = 'text-bg-danger';
                            } else {
                                $status['init'] = '';
                                $status['info'] = '';
                                $status['color'] = '';
                            }

                            $wpaStatus = WpaStatus::status(
                                $item->wpas?->last()?->dd,
                                $item->wpas?->last()?->execstats,
                                $item->wpas?->last()?->completed_at,
                            );

                        @endphp
                        <tr wire:key="row-{{ $item->id }}" class="align-middle">

                            <td class="{{ $rowClass['color'] ?? '' }} {{ $rowClass['color-text'] ?? '' }} fw-bold">

                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" wire:model.defer="selected"
                                        id="checkbox-{{ $item->id }}" value="{{ $item->id }}">
                                    <label class="form-check-label" for="checkbox-{{ $item->id }}"></label>
                                </div>
                            </td>
                            <td class="{{ $rowClass['color'] ?? '' }} {{ $rowClass['color-text'] ?? '' }}">

                                <span class="badge {{ $status['color'] }}" data-bs-toggle="tooltip"
                                    title="{{ $status['info'] }}">{{ $status['init'] }}</span>

                            </td>

                            <td class="{{ $rowClass['color'] ?? '' }} {{ $rowClass['color-text'] ?? '' }}">
                                {{ $item->note?->note }}</td>
                            <td class="{{ $rowClass['color'] ?? '' }} {{ $rowClass['color-text'] ?? '' }}">
                                <i class="{{ $wpaStatus->icon }} fs-4 {{ $wpaStatus->color }} align-middle"></i>
                                <span
                                    class="badge {{ $wpaStatus->bg_color }} align-middle">{{ $wpaStatus->info }}</span>
                            </td>
                            <td class="{{ $rowClass['color'] ?? '' }} {{ $rowClass['color-text'] ?? '' }}">
                                {{ $item->note?->rubrica }}</td>
                            <td class="{{ $rowClass['color'] ?? '' }} {{ $rowClass['color-text'] ?? '' }}">
                                {{ $item->note?->lexp }}</td>
                            <td class="{{ $rowClass['color'] ?? '' }} {{ $rowClass['color-text'] ?? '' }} fw-bold">
                                {{ $item->note?->postes ?? '---' }}</td>
                            <td class="{{ $rowClass['color'] ?? '' }} {{ $rowClass['color-text'] ?? '' }} fw-bold">
                                {{ shortUser($item->user?->name) }}</td>
                            <td class="{{ $rowClass['color'] ?? '' }} {{ $rowClass['color-text'] ?? '' }}">
                                {{ $item->att_at?->diffInDays(Carbon::now()) }} dias
                            </td>

                            <td class="{{ $rowClass['color'] ?? '' }} {{ $rowClass['color-text'] ?? '' }}">
                                <span class="badge {{ Notestatus::status($item->status)->colorbg }}"
                                    wire:click.prevent="$emitTo('components.status.show-status', 'showStatus', {{ $item->id }}, {{ $item->status }})"
                                    style="cursor: pointer;">{{ Notestatus::status($item->status)->status }}</span>
                            </td>
                            <td class="{{ $rowClass['color'] ?? '' }} {{ $rowClass['color-text'] ?? '' }}">
                                <x-production.action-production :production="$item" />
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        {{ $lists->links() }}

        {{-- END MODALS --}}
        @livewire('audits.info')
        @livewire('components.status.show-status', key('show_status_note'))

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
    @endif


</div>

@push('script')
    <script>
        const copyTextCells = document.querySelectorAll('.copy-text');

        copyTextCells.forEach(cell => {
            cell.addEventListener('click', () => {
                const value = cell.getAttribute('data-value');
                copyToClipboard(value);
                livewire.emit('getCopy',
                    `Valor "${value}" copiado para a área de transferência.`);
                // alert(`Valor "${value}" copiado para a área de transferência.`);
            });
        });

        function copyToClipboard(text) {
            const textArea = document.createElement('textarea');
            textArea.value = text;
            document.body.appendChild(textArea);
            textArea.select();
            document.execCommand('copy');
            document.body.removeChild(textArea);
        }
    </script>
@endpush
