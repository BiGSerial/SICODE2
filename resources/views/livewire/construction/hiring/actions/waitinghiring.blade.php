@php

    use App\Helpers\SelectOptions;

@endphp
<div>
    <x-show-loading />
    <div wire:ignore.self class="modal" tabindex="-1" id="modal_viability">
        <div class="modal-dialog modal-xl">
            <div class="modal-content edp-bg-stategrey-50">
                <div class="modal-header edp-bg-sprucegreen-70 text-edp-verde">
                    <h5 class="modal-title">VIABILIDADE</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="card">
                        <div class="card-header edp-bg-sprucegreen-70 text-edp-verde d-flex justify-content-start">
                            <h4 class="my-auto">Dados de Envio</h4>
                        </div>
                        <div class="card-body d-flex justify-content-between">
                            <div class="mb-3 col-5">
                                <label for="form-label" class="text-secondary">Selecione a Empreiteira</label>
                                <select class="form-select border-secondary" wire:model="company_id">
                                    <option>----</option>
                                    @if ($companies)
                                        @foreach ($companies as $company)
                                            <option value="{{ $company->id }}">{{ $company->name }}</option>
                                        @endforeach
                                    @endif
                                </select>
                                @error('company_id')
                                    <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>
                            <div class="mb-3 col-5">
                                <label for="form-label" class="text-secondary">Selecione o Responsável</label>
                                <select class="form-select border-secondary" wire:model.defer="responsible_id">
                                    @if ($responsibles)
                                        <option>----</option>
                                        @foreach ($responsibles as $responsible)
                                            <option value="{{ $responsible->id }}">{{ $responsible->name }}</option>
                                        @endforeach
                                    @endif
                                </select>
                                @error('responsible_id')
                                    <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                    </div>
                    <div class="card">
                        <div class="card-header edp-bg-sprucegreen-70 text-edp-verde d-flex justify-content-start">
                            <h4 class="my-auto">Obras para Viabilidade</h4>
                        </div>
                        <table class="table table-sm table-condensed table-striped">
                            <thead>
                                <tr class="align-middle text-center">
                                    <th scope="col">Contratar</th>
                                    <th scope="col">Reter</th>
                                    <th scope="col">Obra</th>
                                    <th scope="col">Ordens</th>
                                    <th scope="col">Rubrica</th>
                                    <th scope="col">Município</th>
                                    <th scope="col">Arquivos</th>
                                    <th scope="col"></th>
                                    <th scope="col"></th>
                                </tr>
                            </thead>
                            <tbody>
                                @if (count($toViabilities))
                                    @foreach ($toViabilities as $key => $viability)
                                        <tr class="align-middle text-center">
                                            <td><input type="checkbox" name="contratar" id="contratar"
                                                    wire:model="toViabilities.{{ $key }}.contratar" /></td>
                                            <td><input type="checkbox" name="reter" id="reter"
                                                    wire:model="toViabilities.{{ $key }}.reter"></td>

                                            <td>{{ $viability['note']['note'] }}</td>
                                            <td>
                                                @if (!empty($viability['note']['orders']))
                                                    @foreach ($viability['note']['orders'] as $order)
                                                        <p class="py-0 my-0">{{ $order['ordem'] }}</p>
                                                    @endforeach
                                                @endif
                                            </td>
                                            <td>{{ $viability['note']['rubrica'] }}</td>
                                            <td>{{ $viability['note']['lexp'] }}</td>
                                            <td>
                                                @if (count($viability['note']['files']))
                                                    @foreach ($viability['note']['files'] as $file)
                                                        <p class="py-0 my-0">{{ $file['file_name'] }}</p>
                                                    @endforeach
                                                @endif

                                                @if (isset($viability['temp_files']['files']) && count($viability['temp_files']['files']))
                                                    @foreach ($viability['temp_files']['files'] as $file)
                                                        <p class="my-0 py-0">{{ $viability['temp_files']['typeFile'] }}
                                                        </p>
                                                        <p class="py-0 my-0">{{ $file->getClientOriginalName() }}</p>
                                                    @endforeach
                                                @endif

                                            </td>
                                            <td>
                                                @if (!count($viability['note']['files']))
                                                    <select
                                                        wire:model="toViabilities.{{ $key }}.temp_files.typeFile"
                                                        class="form-select border-secondary">
                                                        <option value="">Selecione o tipo</option>
                                                        <option value="PROJETO">PROJETO</option>
                                                    </select>
                                                    <input type="file" class="form-control border-secondary"
                                                        @disabled(!isset($toViabilities[$key]['temp_files']['typeFile'])) name="file"
                                                        wire:model="toViabilities.{{ $key }}.temp_files.files"
                                                        multiple />
                                                    @error('toViabilities.{{ $key }}.temp_files.files')
                                                        <span class="error text-danger">{{ $message }}</span>
                                                    @enderror
                                                @endif
                                            </td>

                                            <td>
                                                <button class="btn btn-sm btn-danger"
                                                    wire:click.prevent="removeViability({{ $key }})">Remover</button>
                                            </td>
                                        </tr>
                                    @endforeach
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer edp-bg-sprucegreen-70 text-edp-verde">
                    <button type="button" class="btn btn-secondary" wire:click.prevent="closeAll">Close</button>
                    <button type="button" class="btn btn-primary" wire:click.prevent="toViability">Enviar</button>
                </div>
            </div>
        </div>
    </div>


    <script>
        // Capturando o evento de fechamento do modal
        document.getElementById('modal_viability').addEventListener('hidden.bs.modal', () => {

            Livewire.emitTo('construction.hiring.actions.waitinghiring', 'closeAll');
        });
    </script>

</div>
