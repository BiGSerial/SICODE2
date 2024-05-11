<div>
    <x-show-loading />
    <div wire:ignore.self class="modal fade" id="modal_toviability" tabindex="-1" aria-labelledby="exampleModalLabel"
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


                    @if ($orders)

                        <div class="card mt-2">

                            <div class="card-body p-1">

                                <div class="container">
                                    <table class="table table-sm table-striped-columns">
                                        <thead>
                                            <th scope="col" class="text-center">
                                                <input class="form-check-input border border-secondary" type="checkbox"
                                                    wire:model="selectAllorder">
                                            </th>
                                            <th scope="col" class="text-center">Ordem</th>
                                            <th scope="col" class="text-center">Nota/Ov</th>
                                            <th scope="col" class="text-center">Files</th>
                                            <th scope="col" class="text-center">CentroTrab</th>
                                        </thead>

                                        <tbody>
                                            @foreach ($orders as $index => $order)
                                                <tr wire:key='{{ $index }}-{{ $order->id }}'>
                                                    <td class="text-center "><input
                                                            class="form-check-input border border-secondary"
                                                            type="checkbox" wire:model.defer="orderSelected"
                                                            value="{{ $order->id }}">
                                                    </td>
                                                    <td class="text-center">{{ $order->ordem }}</td>
                                                    <td class="text-center">{{ $order->Note->note }}</td>
                                                    <td class="text-center"> <x-files.select-download-list
                                                            :files='$order->Note->Files' /></td>
                                                    <td class="text-center">
                                                        {{ isset($order->Operations->first()->cenTrab) ? $order->Operations->first()->cenTrab : '' }}
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>


                                    </table>
                                </div>


                            </div>


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
