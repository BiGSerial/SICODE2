<div>
    <x-show-loading />
    <div wire:ignore.self class="modal fade" id="modal_viability" tabindex="-1" aria-labelledby="exampleModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content edp-bg-stategrey-50">
                <div class="modal-header edp-bg-sprucegreen-70 text-edp-verde">
                    <h4 class="my-auto fw-bold">
                        VIABILIDADE
                    </h4>
                </div>
                <div class="modal-body">

                    {{-- FILES --}}
                    <div class="card">
                        <div class="card-header edp-bg-sprucegreen-70 text-edp-verde d-flex justify-content-start">
                            <h4 class="my-auto">Dados de Envio</h4>
                        </div>
                        <div class="card-body d-flex justify-content-between">
                            <div class="mb-3 col-5">
                                <label for="form-label" class="text-secondary">Selecione a Empreiteira</label>
                                <select class="form-select" wire:model.defer="company">
                                    <option>----</option>
                                    @if ($companies)
                                        @foreach ($companies as $company)
                                            <option value="{{ $company->id }}">{{ $company->name }}</option>
                                        @endforeach
                                    @endif

                                </select>


                            </div>
                            <div class="mb-3 col-5">
                                <label for="form-label" class="text-secondary">Selecione o Responsável
                                    Responsável</label>
                                <select class="form-select" wire:model.defer="user">

                                    @if ($users)
                                        <option>----</option>
                                        @foreach ($users as $usr)
                                            <option value="{{ $usr->id }}">{{ $usr->name }}</option>
                                        @endforeach
                                    @endif

                                </select>


                            </div>
                        </div>
                    </div>
                    <div class="my-2"> <button class="btn btn-sm btn-primary"
                            onclick="document.getElementById('file-modal').click()">ADICIONAR ARQUIVO</button>
                        <button class="btn btn-sm btn-danger" wire:click.prevent='cancel'
                            wire:loading.attr='disabled'><span wire:target='cancel' wire:loading.remove>REMOVER
                                ARQUIVOS</span><span wire:target='cancel' wire:loading>REMOVENDO...</span></button>
                    </div>
                    <div x-data="{ isUploading: false, progress: 0 }" x-on:livewire-upload-start="isUploading = true"
                        x-on:livewire-upload-finish="isUploading = false"
                        x-on:livewire-upload-error="isUploading = false"
                        x-on:livewire-upload-progress="progress = $event.detail.progress">

                        <form wire:submit.prevent="saveFile">
                            <input type="file" id="file-modal" multiple wire:model="uploadsfiles" value=""
                                accept=".pdf,.gif,.jpg,.png" hidden>

                        </form>

                        <div x-show="isUploading" class="mb-3">

                            <div class="progress my-0" role="progressbar" aria-label="Danger example"
                                aria-valuenow="100" aria-valuemin="0" aria-valuemax="100"
                                style="width: 100%; border-radius: 0;">
                                <span class="progress-bar bg-danger" x-bind:style="`width: ${progress}%`"
                                    x-text="`${progress}%`">
                            </div>
                        </div>
                    </div>

                    @if ($orders)



                        <div class="card mt-2">

                            <div class="card-body p-1">
                                @if (count($toViabilities))
                                    <div class="container">
                                        <table class="table table-sm table-striped-columns">
                                            <thead>
                                                <th scope="col" class="col-1 text-center">Ordem</th>
                                                <th scope="col" class="col-1 text-center">Nota/Ov</th>
                                                <th scope="col" class="text-center">Files</th>
                                                <th scope="col" class="col-1 text-center"></th>
                                            </thead>

                                            @foreach ($toViabilities as $index => $viability)
                                                @if (isset($viability['order']['ordem']))
                                                    <tr>
                                                        <td class="text-center align-middle">
                                                            {{ $viability['order']['ordem'] }}</td>
                                                        <td class="text-center align-middle">
                                                            {{ $viability['order']['note']['note'] }}</td>
                                                        <td class="text-center align-middle">
                                                            @if (count($viability['files']))
                                                                @foreach ($viability['files'] as $index2 => $file)
                                                                    <p class="mb-0">
                                                                        {{ $file->getClientOriginalName() }} <i
                                                                            class="bx bxs-trash text-danger fs-5"
                                                                            wire:click.prevent="deleteFile({{ $index }},{{ $index2 }})"
                                                                            style="cursor: pointer;"></i></p>
                                                                @endforeach
                                                            @elseif($viability['hasFiles'])
                                                                ARQUIVO HERDADO
                                                            @else
                                                                SEM ARQUIVOS
                                                            @endif
                                                        </td>
                                                        <td class="text-center align-middle">
                                                            <i class="bx bxs-trash text-danger fs-4"
                                                                wire:click.prevent="deleteRegister({{ $index }})"
                                                                style="cursor: pointer;">
                                                            </i>
                                                        </td>
                                                    </tr>
                                                @endif
                                            @endforeach

                                        </table>
                                    </div>
                                @else
                                    <div class="my-2 py-2 text-center">
                                        <h4 class="fw-bold">SEM ARQUIVOS</h4>
                                    </div>
                                @endif
                            </div>

                            {{-- @if ($notNote)
                                <div class="card-footer text-bg-danger text-center">
                                    EXISTEM ARQUIVOS QUE PARECEM NÃO TER REFERÊNCIA A ESTA OBRA ({{ $note->note }}).
                                </div>
                            @endif --}}
                        </div>
                    @endif
                    {{-- End Files --}}


                </div>
                <div class="modal-footer edp-bg-sprucegreen-70 text-edp-verde">
                    <div class="me-3 align-middle" wire:target='updatedUploadsfiles()' wire:loading>
                        <div class="spinner-border text-light" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        Aguarde.
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" value="true" id="flexCheckIndeterminate"
                            wire:model.defer="hiring">
                        <label class="form-check-label" for="flexCheckIndeterminate">
                            CONTRATADO
                        </label>
                    </div>
                    <button class="btn btn-primary btn-sm" wire:click.prevent="goViability()"
                        wire:loading.attr='disabled'>ENVIAR</button>
                    <button class="btn btn-danger btn-sm" wire:click.prevent="cancelarViab()"
                        wire:loading.attr='disabled'>CANCELAR</button>
                </div>
            </div>
        </div>
    </div>
</div>
