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
                <div class="input-group mb-3">
                    <span class="input-group-text bg-primary text-white">
                        <i class="fas fa-search"></i>
                    </span>
                    <input type="text" class="form-control form-control-lg" placeholder="Pesquisar..."
                        wire:model.debounce.300ms="search" aria-label="Pesquisar">
                    <button class="btn btn-outline-secondary" type="button" wire:click="resetFilters">
                        <i class="fas fa-times"></i> Limpar
                    </button>
                </div>
            </div>
            <div class="col-md-4">
                <div class="alert alert-warning border-start border-warning border-5 shadow-sm" role="alert">
                    <h5 class="alert-heading"><i class="fas fa-exclamation-triangle me-2"></i>Aviso</h5>
                    <p class="mb-0">Esta página está operando em <strong>modo provisório</strong> e está sendo reconstruída para melhor desempenho. Algumas funcionalidades podem estar limitadas.</p>
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
                <h5 class="card-title my-0">CONTROLE DE FISCALIZAÇÃO</h5>
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
                        <th>DtAds</th>
                        <th>MoAberto</th>
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
                            } elseif ($item->priority && $item->workform?->reject) {
                                $rowClass = [
                                    'color' => 'table-dark',
                                    'color-text' => 'text-warning',
                                    'info' => 'Rejeitado',
                                ];
                            } elseif (!$item->priority && $item->note?->workform?->reject) {
                                $rowClass = [
                                    'color' => 'table-dark',
                                    'color-text' => 'text-white',
                                    'info' => 'Rejeitado',
                                ];
                            } else {
                                $rowClass = [
                                    'color' => '',
                                    'color-text' => '',
                                    'info' => '',
                                ];
                            }

                            if ($item->partial) {
                                $type['init'] = 'P';
                                $type['info'] = 'Parcial';
                                $type['color'] = 'text-bg-warning';
                            } else {
                                $type['init'] = 'F';
                                $type['info'] = 'Final';
                                $type['color'] = 'text-bg-success';
                            }

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
                                $item->wpas?->last()->dd,
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
                                <span class="badge {{ $type['color'] }}" data-bs-toggle="tooltip"
                                    title="{{ $type['info'] }}">{{ $type['init'] }}</span>
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
                                {{ $item->note?->workform?->adsform?->created_at ? $item->note?->workform?->adsform?->created_at?->diffInDays(Carbon::now()) . ' dias' : '' }}
                            </td>
                            <td class="{{ $rowClass['color'] ?? '' }} {{ $rowClass['color-text'] ?? '' }} fw-bold">
                                R$ {{ number_format($item->note?->orders?->sum('moaberto'), 2, ',', '.') }}
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
    @endif


    {{-- END MODALS --}}
    {{-- @livewire('audits.info') --}}
    @livewire('components.status.show-status', key('show_status_note'))
    @livewire('production.return.return-work', key('returnWorkfomr'))


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
