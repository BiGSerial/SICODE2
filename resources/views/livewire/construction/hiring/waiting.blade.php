@php
    use App\Custom\Viabilitiesstatus;
    use App\Custom\Notestatus;
    use Carbon\Carbon;
@endphp
<div>
    <x-show-loading />
    <div class="card edp-bg-gray">
        <div class="card-header  edp-bg-sprucegreen-100 edp-text-verde-dark">
            <h4 class="fs-4">EM ESPERA PARA RESOLUÇÃO</h4>
        </div>
        <div class="card-body py-0 mt-3">
            <div class="mb-3 d-flex justify-content-end">
                <select name="" id="" class="form-select form-select-sm" style="max-width: 200px;"
                    wire:model="action">
                    <option value="" selected>Selecione uma Ação</option>
                    <option value="1">Viabilizar</option>
                    <option value="2">Contratar</option>
                </select>
                <button class="btn btn-sm btn-primary ms-2" wire:click.prevent='go_att_mass'
                    @disabled(!$action) wire:target="go_att_mass" wire:loading.attr="disabled"
                    data-bs-toggle="tooltip" data-bs-placement="top" data-bs-title="Executar"><i
                        class="bx bx-send fs-4 m-0 align-middle" wire:target="go_att_mass" wire:loading.remove></i>
                    <div class="spinner-border spinner-border-sm" role="status" wire:target="go_att_mass" wire:loading>
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </button>
            </div>
        </div>
        <table class="table table-sm table-condensed table-striped-columns">
            <thead>
                <th class="text-center"><input type="checkbox" class="form-checkbox" wire:model="selectAll"></th>
                <th scope="col" class="text-center">Nota</th>
                <th scope="col" class="text-center">Rubrica</th>
                <th scope="col" class="text-center">Municipio</th>
                <th scope="col" class="text-center">Categoria</th>
                <th scope="col" class="text-center">Serviço</th>
                <th scope="col" class="text-center">Data Envio</th>
                <th scope="col" class="text-center">Em Atividade</th>
                <th scope="col" class="text-center">Status</th>
                <th scope="col" class="text-center">Responsável</th>
                {{-- <th scope="col" class="text-center"></th> --}}
            </thead>
            <tbody class="table-group-divider">
                @if ($lists)
                    @foreach ($lists as $list)
                        <tr wire:key="row-{{ $list->id }}">
                            <td class="text-center aling-middle">

                                @if ($list->Reclaim && $list->Reclaim->completed)
                                    <input type="checkbox" class="form-checkbox" wire:model.defer="selected"
                                        value="{{ $list->Note->id }}">
                                @endif


                            </td>
                            <td class="text-center aling-middle fw-bold">{{ $list->Note->note }}</td>
                            <td class="text-center aling-middle">{{ $list->Note->rubrica }}</td>
                            <td class="text-center aling-middle">{{ $list->Note->lexp }}</td>
                            <td class="text-center aling-middle">{{ $list->category }}</td>
                            <td class="text-center aling-middle">{{ $list->Reclaim->Service->service }}</td>
                            <td class="text-center aling-middle">
                                {{ Carbon::parse($list->created_at)->format('d/m/Y H:i') }}
                            </td>
                            <td class="text-center aling-middle">
                                {{ Carbon::parse($list->created_at)->diffForHumans(Carbon::now(), ['locale' => 'pt_br', 'syntax' => \Carbon\CarbonInterface::DIFF_ABSOLUTE]) }}
                            </td>
                            <td class="text-center aling-middle">
                                @if ($list->Reclaim->Production)
                                    <span
                                        class="badge {{ Notestatus::status($list->Reclaim->Production->status)->colorbg }}">
                                        {{ Notestatus::status($list->Reclaim->Production->status)->status }}</span>
                                @else
                                    <span class="badge text-bg-secondary">
                                        Aguardando Atribuição</span>
                                @endif

                            </td>
                            <td class="text-center aling-middle">
                                {{ $list->Reclaim->Production ? ($list->Reclaim->Production->User ? $list->Reclaim->Production->User->name : 'Desconhecido') : '' }}
                            </td>
                            {{-- <td class="text-center aling-middle"></td> --}}
                        </tr>
                    @endforeach
                @endif

            </tbody>
        </table>
    </div>



    {{-- Modals --}}

    <div wire:ignore.self class="modal fade" id="viability_modal" tabindex="-1" aria-labelledby="exampleModalLabel"
        aria-hidden="true">


        <div class="modal-dialog modal-lg">

            <div class="modal-content edp-bg-stategrey-50">
                <div class="modal-header edp-bg-sprucegreen-70 text-edp-verde">
                    <h4 class="my-auto fw-bold">
                        @if ($action == 1)
                            VIABILIDADE
                        @elseif ($action == 2)
                            CONTRATAÇÃO
                        @elseif ($action == 3)
                            INTERROMPER
                        @else
                            AÇÃO DESCONHECIDA
                        @endif

                    </h4>
                </div>

                <div class="modal-body"> {{-- Inicio Modal Body --}}

                    <div class="card">
                        <div class="card-header edp-bg-sprucegreen-70 text-edp-verde d-flex justify-content-start">
                            <h4 class="my-auto">Dados de Envio</h4>
                        </div>
                        <div class="card-body d-flex justify-content-between">
                            <div class="mb-3 col-5">
                                <label for="form-label" class="text-secondary">Selecione a Empreiteira</label>
                                <select class="form-select" wire:model.defer="company_s">
                                    <option>----</option>
                                    @if ($companies)
                                        @foreach ($companies as $company)
                                            <option value="{{ $company->id }}">{{ $company->name }}</option>
                                        @endforeach
                                    @endif

                                </select>


                            </div>
                            <div class="mb-3 col-5">
                                <label for="form-label" class="text-secondary">Selecione o Engenheiro
                                    Responsável</label>
                                <select class="form-select" wire:model.defer="engineer_s">
                                    @if ($engineers)
                                        <option>----</option>
                                        @foreach ($engineers as $engineer)
                                            <option value="{{ $engineer->id }}">{{ $engineer->name }}</option>
                                        @endforeach
                                    @endif

                                </select>


                            </div>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header edp-bg-sprucegreen-70 text-edp-verde d-flex justify-content-between">
                            <h4 class="my-auto">Arquivos</h4>
                            <button class="btn btn-sm btn-primary"
                                onclick="document.getElementById('file-input').click()">Add</button>

                        </div>

                        <div x-data="{ isUploading: false, progress: 0 }" x-on:livewire-upload-start="isUploading = true"
                            x-on:livewire-upload-finish="isUploading = false"
                            x-on:livewire-upload-error="isUploading = false"
                            x-on:livewire-upload-progress="progress = $event.detail.progress">

                            <form wire:submit.prevent="saveFile">
                                <input type="file" id="file-input" multiple wire:model="files" hidden>
                                {{-- <button type="submit" id="id-submit"></button> --}}
                            </form>

                            <div x-show="isUploading" class="mb-3">
                                {{-- <div class="progress-bar progress-bar-striped progress-bar-animated"
                                    role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"
                                    x-bind:style="`width: ${progress}%`">
                                    <span class="align-middle" x-text="`${progress}%`"></span>
                                </div> --}}
                                <div class="progress" role="progressbar" aria-label="Danger example"
                                    aria-valuenow="100" aria-valuemin="0" aria-valuemax="100"
                                    style="width: 100%; border-radius: 0;">
                                    <span class="progress-bar bg-danger" x-bind:style="`width: ${progress}%`"
                                        x-text="`${progress}%`">
                                </div>
                            </div>
                        </div>

                        <div class="card-body " id="drop-area">
                            <div class="row g-1 justify-content-between mb-3">

                                @if (count($show_existing_files))
                                    @foreach ($show_existing_files as $file)
                                        <div class="col-6 border border-secondary d-flex justify-content-between p-0">
                                            <div class="p-1 m-0 border-end border-secondary"><i
                                                    class="bx bxs-file-{{ $file['ext'] }} text-success fs-4"></i>
                                            </div>
                                            <div class="p-1 m-0 text-center no-wrap">{{ $file['name'] }}</i>
                                            </div>
                                            <div class="p-1 m-0 border-start border-secondary">
                                                <i class="ri-file-cloud-line text-succes fs-4"></i>
                                            </div>
                                        </div>
                                    @endforeach
                                @endif

                                @if (count($show_files))
                                    @foreach ($show_files as $file)
                                        <div class="col-6 border border-secondary d-flex justify-content-between p-0">
                                            <div class="p-1 m-0 border-end border-secondary"><i
                                                    class="bx bxs-file-{{ $file['ext'] }} @if ($file['chk']) text-success
                                                    @else text-danger @endif fs-4 align-middle"></i>
                                            </div>
                                            <div class="p-1 m-0 text-center no-wrap">{{ $file['name'] }}</i>
                                            </div>
                                            <div class="p-1 m-0 border-start border-secondary"><i
                                                    class="bx bx-trash text-danger fs-4 align-middle"
                                                    wire:click.prevent="delete_file({{ $file['id'] }})"
                                                    style="cursor: pointer;"></i>
                                            </div>
                                        </div>
                                    @endforeach
                                @elseif (!count($show_files) && !count($show_existing_files))
                                    <h4 class="fs-4 fw-bold my-auto text-center">SEM ARQUIVOS</h4>
                                @endif
                            </div>
                        </div>

                    </div>

                    <div class="card">

                        <div class="card-header edp-bg-sprucegreen-70 text-edp-verde d-flex justify-content-between">
                            <h4 class="my-auto">Ordens/Ovs</h4>


                        </div>




                        <div class="card-body ">
                            @if ($show_registers)
                                <div class="table-responsive">
                                    <table class="table table-sm table-condensed table-striped">
                                        <thead class="table-dark">

                                            <th scope="col">Ordem</th>
                                            <th scope="col">Note</th>
                                            <th scope="col">File</th>
                                            <th scope="col"></th>
                                        </thead>
                                        <tbody>
                                            @foreach ($show_registers as $register)
                                                <tr>

                                                    <td>{{ $register['order'] }}</td>
                                                    <td>{{ $register['note'] }}</td>
                                                    <td class="fw-bold">
                                                        @if (isset($show_files[$register['file_index']]) && !$register['file_online'])
                                                            {{ $show_files[$register['file_index']]['name'] }}
                                                        @elseif ($register['file_online'])
                                                            Aquivo Existente
                                                        @else
                                                            Sem Arquivo
                                                        @endif
                                                    </td>
                                                    <td>
                                                        <i class="bx bx-trash text-danger fs-4 align-middle"
                                                            wire:click.prevent="delete_note({{ $register['id'] }})"
                                                            style="cursor: pointer;" wire:loading.attr="disabled"
                                                            wire:target='delete_note({{ $register['id'] }})'></i>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @endif
                        </div>

                    </div>








                    {{-- <div class="mb-3">
                        <label for="search" class="form-label">Observações</label>
                        <textarea class="form-control" name="advanceSearch" id="advanceSearch" cols="50" rows="10"></textarea>
                    </div> --}}

                </div> {{-- Fim Modal Body --}}



                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" wire:click.prevent="to_viability">Enviar</button>
                </div>

            </div>

        </div>

    </div>

    {{-- Fim Modals --}}
</div>
