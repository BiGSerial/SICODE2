@php
    use App\Custom\Viabilitiesstatus;
    use App\Custom\Notestatus;
    use Carbon\Carbon;
@endphp
<div>
    <x-show-loading />
    <div class="card edp-bg-gray">
        <div class="card-header  edp-bg-sprucegreen-100 edp-text-verde-dark">
            <h4 class="fs-4">RETORNO INTERNO (RI) {{ $service->service }}</h4>
        </div>
        <div class="card-body py-0 mt-3">
            <div class="mb-3 d-flex justify-content-end">

                <button class="btn btn-sm btn-danger ms-2" wire:click.prevent='cleanUser' wire:target="cleanUser"
                    @disabled(!$filterUser) wire:loading.attr="disabled" data-bs-toggle="tooltip"
                    data-bs-placement="top" data-bs-title="Limpar Filtro Usuario"><i
                        class="ri-filter-off-line fs-4 m-0 align-middle" wire:target="cleanUser"
                        wire:loading.remove></i>
                    <div class="spinner-border spinner-border-sm" role="status" wire:target="cleanUser" wire:loading>
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </button>
                <button class="btn btn-sm btn-primary ms-2" wire:click.prevent='exportToExcel'
                    wire:target="exportToExcel" wire:loading.attr="disabled" data-bs-toggle="tooltip"
                    data-bs-placement="top" data-bs-title="Exportar para Excel"><i
                        class="ri-file-excel-2-line fs-4 m-0 align-middle" wire:target="exportToExcel"
                        wire:loading.remove></i>
                    <div class="spinner-border spinner-border-sm" role="status" wire:target="exportToExcel"
                        wire:loading>
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </button>

            </div>
        </div>

        <div class="row mx-3">
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

        <table class="table table-sm table-condensed table-striped-columns">
            <thead>
                <th class="text-center"><input type="checkbox" class="form-checkbox" wire:model="selectAll"></th>
                <th scope="col" class="text-center">Nota</th>
                <th scope="col" class="text-center">Files</th>
                <th scope="col" class="text-center">Rubrica</th>
                <th scope="col" class="text-center">Municipio</th>
                <th scope="col" class="text-center">Grp5</th>
                <th scope="col" class="text-center">Material</th>
                <th scope="col" class="text-center">Categoria</th>
                <th scope="col" class="text-center">Data Envio</th>
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

                            $approvalColor = '';

                            if ($list->Approvals->isNotEmpty()) {
                                $approvalColor = 'text-bg-warning';
                            }
                        @endphp

                        <tr wire:key="row-{{ $list->id }}">
                            <td class="text-center align-middle">
                                <input type="checkbox" class="form-checkbox" wire:model.defer="selected"
                                    value="{{ $list->id }}">
                            </td>
                            <td class="{{ $approvalColor }} text-center align-middle fw-bold">{{ $list->Note->note }}
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
        <div class="row mx-3">
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


    {{-- Livewires Components Functions --}}
    @livewire('dispatchs.users.richange-user', key('change-users-intern-return'))
    @livewire('dispatchs.users.riatt-user', ['service' => $service], key('att-users-intern-return'))
    @livewire('dispatchs.common.reclaim-info', key('reclaim-info-intern-return'))

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
