@php
    use Carbon\Carbon;
    use App\Custom\Notestatus;
    use App\Models\Edp_depc\City;
@endphp
<div>
    {{-- Carrega o Loading da página --}}
    <x-show-loading />

    <div class="row">
        <div class="col-1 mb-3">
            <label for="" class="form-label">Por Página</label>
            <select wire:model="perPage" class="form-select form-control-sm  border border-2 border-secondary">
                <option value="25">25</option>
                <option value="50">50</option>
                <option value="100">100</option>
                <option value="250">250</option>
                <option value="500">500</option>
            </select>
        </div>

    </div>

    @if ($lists->count())
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
    @endif
    <dic class="card">

        @if (!$lists->count())
            <div class="card-body">
                <h4 class="text-center">SEM SOLICITAÇÕES DE TRANSFERENCIA EM
                    <strong>{{ mb_strtoupper($service->service) }}</strong>
                    @if ($service->Status->count())
                        @foreach ($service->Status->where('exclusion', false)->unique('value') as $sts)
                            ({{ $sts->value }})
                        @endforeach
                    @endif
                </h4>
            </div>
        @else
            {{-- <h4 class="card-header fw-bold text-bg-danger">ACOMPANHAMENTO -
                {{ mb_strtoupper($service->service) }} - @if ($service->Status->count())
                    @foreach ($service->Status as $sts)
                        ({{ $sts->status }})
                    @endforeach
                @endif
            </h4> --}}
            <div class="card-header">
                <div class="row">
                    <div class="col">
                        <h4 class="my-0">AGUARDANDO LIBERAÇÃO DE TRANSFERÊNCIA
                        </h4>
                    </div>
                    {{-- <div class="col-4 d-flex justify-content-end">
                        <button class="btn btn-sm btn-success me-2" data-bs-toggle="modal"
                            data-bs-target="#add_mass_dds"><i class="ri-checkbox-multiple-fill"></i> Att DD</button>
                        <button class="btn btn-sm btn-primary me-2" wire:click.prevent='go_att_mass'><i
                                class="ri-checkbox-multiple-fill"></i> Atribuir</button>
                        <button class="btn btn-sm btn-primary me-2" wire:click.prevent='export_excel'><i
                                class="ri-file-excel-2-line"></i> Exportar</button>
                        <button class="btn btn-sm btn-primary me-2" wire:click="$refresh"><i
                                class="ri-refresh-line"></i></button>
                    </div> --}}
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm table-striped table-condensed">
                        <thead class="table-dark">
                            <tr>
                                <th>
                                    <input class="form-check-input" type="checkbox" wire:model="selectall">
                                </th>
                                <th scope="col" class="fw-bold text-center">SERVIÇO</th>
                                <th scope="col" class="fw-bold text-center">NOTA</th>
                                <th scope="col" class="fw-bold text-center">REGIÃO</th>
                                <th scope="col" class="fw-bold text-center">MUNICÍPIO</th>
                                <th scope="col" class="fw-bold text-center">DE</th>
                                <th scope="col" class="fw-bold text-center">PARA</th>
                                <th scope="col" class="fw-bold text-center">MOTIVO</th>
                                <th scope="col" class="fw-bold text-center">STATUS</th>
                                <th scope="col" class="fw-bold text-center">DATA TRANSFERÊNCIA</th>
                                <th scope="col" class="fw-bold text-center">NOVA DD</th>

                                <th scope="col" class="fw-bold text-center"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($lists as $list)
                                <tr
                                    class="align-middle
                                    @if ($list->block) table-primary @endif

                                    ">
                                    <td>
                                        <input class="form-check-input border border-1 border-primary" type="checkbox"
                                            value="{{ $list->id }}" wire:model.defer="selected">
                                    </td>

                                    <td class="fw-bold text-center">
                                        {{ $list->Service->service }}
                                    </td>

                                    <td class="fw-bold copy-text @if ($list->priority) text-danger fw-bold @endif"
                                        data-value="{{ $list->Note->note }}" style="cursor: pointer;">
                                        {{ $list->Note->note }}@if ($list->priority)
                                            <i class="ri-alert-fill text-danger align-middle"></i>
                                        @endif
                                    </td>

                                    <td class="fw-bold text-center">
                                        {{ City::where('rdMunicipio', $list->Note->nexp)->first() ? City::where('rdMunicipio', $list->Note->nexp)->first()->regiao : '__' }}
                                    </td>

                                    <td class="fw-bold text-center">
                                        {{ $list->Note->lexp }}
                                    </td>





                                    <td class="fw-bold text-danger text-center">
                                        {{ $list->Transfer->last()?->From?->name }}
                                    </td>

                                    <td class="fw-bold text-danger text-center">
                                        {{ $list->Transfer->last()?->To?->name }}
                                    </td>

                                    <td class="fw-bold text-danger text-center">
                                        {{ $list->Transfer->last()?->info }}
                                    </td>

                                    <td><span
                                            class="badge {{ Notestatus::status($list->Transfer->last()?->status)->colorbg }}">{{ Notestatus::status($list->Transfer->last()?->status)->status }}</span>
                                    </td>

                                    <td
                                        class="fw-light text-center @if ($list->priority) text-danger fw-bold @endif">
                                        {{ date('d/m/Y', strToTime($list->Transfer->last()?->created_at)) }}

                                    </td>


                                    <td class="fw-light text-center">
                                        <input class="form-control form-control-sm border border-2 border-secondary "
                                            type="text" wire:model.defer="dd.{{ $list->id }}" aria-label="">
                                    </td>

                                    <td class="fw-bold fs-5">


                                        <div class="dropdown" style="position: inherit">
                                            <button class="btn btn-danger btn-sm dropdown-toggle" type="button"
                                                data-bs-toggle="dropdown" aria-expanded="false">
                                                <i class="ri-menu-fill"></i>
                                            </button>
                                            <ul class="dropdown-menu  edp-bg-gray">
                                                <li><a class="dropdown-item" href="#"
                                                        wire:click.prevent="verify_transfer({{ $list->id }})"><i
                                                            class="ri-user-shared-fill text-primary align-middle"></i>
                                                        CONFIRMAR TRANSFERÊNCIA</a>
                                                </li>
                                                <li><a class="dropdown-item" href="#"
                                                        wire:click.prevent="cancel_transfer({{ $list->id }})"><i
                                                            class="ri-close-circle-fill text-danger align-middle"></i>
                                                        CANCELAR TRANSFERÊNCIA</a>
                                                </li>
                                            </ul>
                                        </div>

                                    </td>


                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif


    </dic>
    @if ($lists->count())
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
    @endif


    {{-- MODALS --}}

    <div wire:ignore.self class="modal fade" id="buscar_multi" tabindex="-1" aria-labelledby="exampleModalLabel"
        aria-hidden="true">


        <div class="modal-dialog">

            <div class="modal-content edp-bg-stategrey-50">
                <div class="modal-header edp-bg-sprucegreen-70 text-edp-verde">
                    Buscar Multi-Notas
                </div>
                <div>
                    <textarea class="form-control" name="advanceSearch" id="advanceSearch" cols="50" rows="10"
                        wire:model.defer="advanceSearch"></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" wire:click="buscarMulti">OK</button>
                </div>
            </div>

        </div>

    </div>

    <div wire:ignore.self class="modal fade" id="add_mass_notes" tabindex="-1" aria-labelledby="exampleModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content edp-bg-stategrey-50">
                <div class="modal-header edp-bg-sprucegreen-70 text-edp-verde">
                    <h1 class="modal-title fs-5" id="exampleModalLabel">Despachar {{ $service->service }}</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"
                        wire:click.prevent="closeall"></button>
                </div>
                <div class="modal-body">
                    @if ($notes && $notes->count())
                        <div class="row">
                            {{-- <div class="mb-3">
                                <label for="exampleFormControlInput1" class="form-label">Tipo de Despacho</label>
                                <select class="form-select form-select-sm" aria-label="Small select example"
                                    wire:model="type">
                                    <option selected>Selecione</option>
                                    <option value="1">Pilha</option>
                                    <option value="2">Individual</option>
                                </select>
                            </div> --}}
                            <div class="mb-3 ">
                                <label for="exampleFormControlInput1" class="form-label">Empresa:</label>
                                <select class="form-select form-select-sm" aria-label="" wire:model="company_s">
                                    <option selected>Selecione</option>
                                    @if ($company_l && $company_l->count())
                                        @foreach ($company_l as $company)
                                            <option value="{{ $company->id }}">{{ $company->name }}</option>
                                        @endforeach
                                    @endif
                                </select>
                            </div>



                            <div class="mb-3 ">
                                <label for="exampleFormControlInput1" class="form-label">Usuário:</label>
                                <select class="form-select form-select-sm" aria-label="" wire:model="user_s">

                                    @if ($user_l && $user_l->count())
                                        <option value="" selected>Selecione um Usuário</option>
                                        @foreach ($user_l as $user)
                                            <option value="{{ $user->id }}">{{ $user->name }}</option>
                                        @endforeach
                                    @else
                                        <option selected>Escolha uma Empresa Primeiro</option>
                                    @endif
                                </select>
                            </div>


                            <div class="mb-2 ">
                                <label for="exampleFormControlInput1" class="form-label">Relacionar DD em
                                    MASSA:</label>
                                <textarea class="form-control" id="exampleFormControlTextarea1" rows="3"
                                    placeholder="<número OV/NOTA> <número DD> Ex: 4001123232 14034330" wire:model.defer="enter_dd"></textarea>
                            </div>
                            <div class="mb-3">
                                <button class="btn-sm btn btn-primary" wire:click.prevent="add_dd">DD em
                                    MASSA</button>
                            </div>


                            <div class="col-12 fw-bold">
                                DESPACHANDO {{ $notes->count() }} OV/NOTA(S)
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-sm table-condensed table-striped">
                                <thead>
                                    <tr>
                                        <th scope="col">#</th>
                                        <th scope="col">Note</th>
                                        <th scope="col">Desc</th>
                                        <th scope="col">DD</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($notes as $index => $note)
                                        <tr>
                                            <td scope="col" class="fw-bold">{{ $index + 1 }}</td>
                                            <td>{{ $note->note }}</td>
                                            <td>{{ $note->material }}</td>
                                            <td>

                                                <input wire:model.defer="additionalData.{{ $index }}"
                                                    class="form-control form-control-sm" type="text"
                                                    placeholder="Informe a DD" aria-label="">


                                            </td>

                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                    @endif
                </div>
                <div class="modal-footer edp-bg-sprucegreen-70">
                    <button class="btn-sm btn btn-danger" wire:click.prevent="closeall">Cancelar</button>
                    <button class="btn-sm btn btn-primary" wire:click.prevent="confirm_att"
                        wire:loading.attr="disabled" wire:target="confirm_att">
                        Despachar
                    </button>
                </div>
            </div>
        </div>
    </div>


    <div wire:ignore.self class="modal fade" id="add_mass_dds" tabindex="-1" aria-labelledby="exampleModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content edp-bg-stategrey-50">
                <div class="modal-header edp-bg-sprucegreen-70 text-edp-verde">
                    <h1 class="modal-title fs-5" id="exampleModalLabel">Atribuir DD em {{ $service->service }}</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"
                        wire:click.prevent="closeall"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="exampleFormControlInput1" class="form-label">Relacionar DD em
                            MASSA:</label>
                        <textarea class="form-control" id="exampleFormControlTextarea1" rows="10" style="resize: none;"
                            placeholder="<número OV/NOTA> <número DD> Ex: 4001123232 14034330" wire:model.defer="enter_dd"></textarea>
                    </div>
                </div>
                <div class="modal-footer edp-bg-sprucegreen-70">
                    <button class="btn-sm btn btn-danger" wire:click.prevent="closeall">Cancelar</button>
                    <button class="btn-sm btn btn-primary" wire:click.prevent="mass_modal">Atribuir</button>
                </div>
            </div>
        </div>
    </div>

    {{-- END MODALS --}}

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
