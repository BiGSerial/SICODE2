@php
    use Carbon\Carbon;
@endphp
<div>
    {{-- Carrega o Loading da página --}}
    <x-show-loading />

    <div class="row justify-content-between">
        <div class="mb-3 col-3">
            <label for="search" class="form-label">Buscar</label>
            <input wire:model.bounce.2s="search" type="email" class="form-control border border-2 border-secondary"
                id="search" placeholder="Buscar">
        </div>

    </div>

    <div class="row">


        @if ($lists->count())
            <div class="col-6">
                {{ $lists->links() }}
            </div>
        @endif
        <div class="col-6 d-flex justify-content-end align-middle">
            <span class="align-middle"> Exibindo {{ $lists->firstItem() }} até
                {{ $lists->lastItem() }}
                de {{ $lists->total() }}
                registros.

            </span>
        </div>
    </div>
    <dic class="card">

        @if (!$lists->count())
            <div class="card-body">
                <h4 class="text-center">SEM NOTAS EM ESPERA PARA {{ $service->service }} @if ($service->Status->count())
                        @foreach ($service->Status->where('exclusion', false)->unique('value') as $sts)
                            ({{ $sts->value }})
                        @endforeach
                    @endif
                </h4>
            </div>
        @else
            <h4 class="card-header fw-bold text-bg-info">EM ESPERA PARA {{ mb_strtoupper($service->service) }}
                @if ($service->Status->count())
                    @foreach ($service->Status->where('exclusion', false)->unique('value') as $sts)
                        ({{ $sts->value }})
                    @endforeach
                @endif
            </h4>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm table-striped table-condensed">
                        <thead class="table-dark">
                            <tr>
                                <th scope="col" class="fw-bold">Nota</th>
                                <th scope="col" class="fw-bold">Criado em</th>
                                <th scope="col" class="fw-bold">Finalizado em</th>
                                <th scope="col" class="fw-bold">Status</th>
                                <th scope="col" class="fw-bold"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($lists as $list)
                                {{-- @dump($list->Productions) --}}
                                <tr class="align-middle">
                                    <td class="fw-bold copy-text" data-value="{{ $list->note }}">{{ $list->note }}
                                    </td>
                                    <td class="fw-light">{{ date('d/m/Y H:i:s', strToTime($list->created_at)) }}</td>
                                    <td class="fw-light">
                                        {{ $list->finish_at ? date('d/m/Y H:i:s', strToTime($list->finish_at)) : '' }}
                                    </td>
                                    <td class="fw-light">
                                        @if ($list->completed && !$list->confirmed && !$list->cancel)
                                            <span class="badge text-bg-danger">EM AGUARDO</span>
                                        @elseif (!$list->completed && !$list->confirmed && !$list->cancel)
                                            <span class="badge text-bg-success">EM ANDAMENTO</span>
                                        @elseif ($list->cancel)
                                            <span class="badge text-bg-secondary">EM CANCELAMENTO</span>
                                        @endif
                                    </td>
                                    <td class="fw-bold text-center">
                                        @if (!$list->completed && !$list->confirmed && !$list->cancel)
                                            <button class="btn btn-sm btn-success py-0"
                                                wire:click.prevent="to_confirm({{ $list->id }})"
                                                data-bs-toggle="tooltip" data-bs-placement="top"
                                                data-bs-custom-class="custom-tooltip" data-bs-title="Confirmar"><i
                                                    class="ri-checkbox-circle-line fs-5 m-0"></i></button>
                                            <button class="btn btn-sm btn-primary py-0"
                                                wire:click.prevent="to_cancel({{ $list->id }})"
                                                data-bs-toggle="tooltip" data-bs-placement="top"
                                                data-bs-custom-class="custom-tooltip"
                                                data-bs-title="Para cancelamento"><i
                                                    class="ri-close-circle-line fs-5 m-0"></i></button>
                                        @endif

                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </dic>
    <div class="row">
        <div class="col-6">
            {{ $lists->links() }}
        </div>
        <div class="col-6 d-flex justify-content-end align-middle">
            <span class="align-middle"> Exibindo {{ $lists->firstItem() }} até
                {{ $lists->lastItem() }}
                de {{ $lists->total() }}
                registros.</span>
        </div>
    </div>
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
