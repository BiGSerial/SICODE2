@php
    use App\Helpers\SelectOptions;
@endphp
<div>
    <x-show-loading />
    @if (!$this->note)
        <div class="card mx-auto" style="max-width: 30rem;">
            <div class="card-body">
                <div class="align-itens-center text-center mb-3">
                    <h5 class="fw-bold text-center">BUSCAR OBRA</h5>
                    <input class="form-control border border-1 border-secondary mb-3" type="text"
                        placeholder="Digite numero Nota, OV, Ordem ou Diagrama" aria-label="Search Note"
                        wire:model.defer="search">
                    <button class="btn btn-sm btn-primary text-center" wire:click.prevent="search()">BUSCAR</button>
                </div>
                @if ($notes && $notes->count())
                    <div>
                        <h6 class="fw-bold">SELECIONE UMA OBRA PARA INFORMAR</h6>
                        <table class="table table-sm table-condensed table-striped">
                            <tbody>
                                @foreach ($notes as $note)
                                    <tr wire:key="{{ $note->id }}" wire:click="toConfirmWork({{ $note }})"
                                        style="cursor: pointer;">
                                        <td class="fw-bold align-middle">{{ $note->note }}</td>
                                        <td class="align-middle">
                                            @if ($note->Orders->count())
                                                @foreach ($note->Orders as $order)
                                                    <p class="my-0 py-0">{{ $order->ordem }}</p>
                                                @endforeach
                                            @endif
                                        </td>
                                        <td class="align-middle">
                                            @if ($note->Viabilities->count())
                                                {{ $note->Viabilities->last()->completed ? 'VIABILIZADO' : 'NÃO VIABILIZADO' }}
                                            @else
                                                SEM INFORMAÇÔES DE VIABILIDADE
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    @endif

    @if ($this->note)
        <div class="container">
            <div class="card edp-bg-gray">
                <div class="card-header edp-bg-sprucegreen-70 text-edp-verde">
                    <h4>INFORME DE ENTREGA DE OBRA</h4>
                </div>
                <div class="card-body">


                    <div class="card mb-3">
                        <h5 class="card-header py-0 my-0 edp-bg-sprucegreen-70 text-edp-verde">Dados Nota</h5>
                        <table class="table table-condensed table-sm table-striped-columns">
                            <tbody>
                                <tr>
                                    <td class="align-middle text-end" style="width: 150px;">Nota/Ov</td>
                                    <td class="align-middle">{{ $note->note }}</td>
                                </tr>
                                <tr>
                                    <td class="align-middle text-end" style="width: 150px;">Rubrica</td>
                                    <td class="align-middle">{{ $note->rubrica }}</td>
                                </tr>
                                <tr>
                                    <td class="align-middle text-end" style="width: 150px;">Município</td>
                                    <td class="align-middle">{{ $note->lexp }}</td>
                                </tr>
                                <tr>
                                    <td class="align-middle text-end" style="width: 150px;">Group1</td>
                                    <td class="align-middle">{{ $note->group1 }}</td>
                                </tr>
                                <tr>
                                    <td class="align-middle text-end" style="width: 150px;">Group2</td>
                                    <td class="align-middle">{{ $note->group2 }}</td>
                                </tr>
                                <tr>
                                    <td class="align-middle text-end" style="width: 150px;">Group3</td>
                                    <td class="align-middle">{{ $note->group3 }}</td>
                                </tr>
                                <tr>
                                    <td class="align-middle text-end" style="width: 150px;">Group1</td>
                                    <td class="align-middle">{{ $note->group5 }}</td>
                                </tr>
                                <tr>
                                    <td class="align-middle text-end" style="width: 150px;">Centro Trabalho</td>
                                    <td class="align-middle">{{ $note->centerjob }}</td>
                                </tr>
                                <tr>
                                    <td class="align-middle text-end" style="width: 150px;">Status Atual</td>
                                    <td class="align-middle">{{ $note->nstats }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="mb-3" style="max-width: 300px">
                        <label for="exampleFormControlInput1" class="form-label">Adicione as Ordens deste
                            Informe.</label>
                        <select class="form-select mb-3" aria-label="Default select example" wire:model.defer="s_order">
                            <option selected>Selecionar</option>
                            @if ($note->Orders->count())
                                @foreach ($note->Orders as $order)
                                    <option value="{{ $order->id }}">{{ $order->ordem }}</option>
                                @endforeach
                            @endif
                        </select>

                        <button class="btn btn-sm btn-primary mb-3" wire:click="addOrders()">Adicionar</button>

                        <div class="card">
                            <h5 class="my-0 py-1 card-header">ORDENS/DRs RELACIONADAS</h5>
                            @if (!empty($temp_orders))
                                <table class="table-sm table-condensed table-striped-columns">
                                    <tbody>
                                        @foreach ($temp_orders as $index => $t_order)
                                            <tr class="px-2">
                                                <td class="text-start">{{ $t_order['ordem'] }}</td>
                                                <td class="text-end"><i class="ri-delete-bin-2-line text-danger"
                                                        wire:click="remOrders({{ $index }})"
                                                        style="cursor: pointer;"></i></td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            @else
                                <div class="card-body">
                                    <h5 class="text-center">NENHUMA ORDEM ASSOCIADA</h5>
                                </div>
                            @endif
                        </div>
                    </div>

                    @if (!empty($temp_orders))
                        <div class="mb-3 " style="max-width: 300px">
                            <label for="exampleFormControlInput1" class="form-label">Obra BTZERO?</label>
                            <select class="form-select" aria-label="Default select example">
                                <option selected>Selecione</option>
                                <option value="1">Sim</option>
                                <option value="">Não</option>
                            </select>
                        </div>

                        <div class="mb-3" style="max-width: 300px">
                            <label for="exampleFormControlInput1" class="form-label">Data Conclusão da Obra:</label>
                            <input type="date" class="form-control" id="exampleFormControlInput1"
                                max="{{ date('Y-m-d') }}">
                        </div>

                        <div class="mb-3 " style="max-width: 300px">
                            <label for="exampleFormControlInput1" class="form-label">Houve Instalação ou Desinstalação
                                de Equipamento?</label>
                            <select class="form-select" aria-label="Default select example" wire:model="equipment">
                                <option selected>Selecione</option>
                                <option value="1">Sim</option>
                                <option value="">Não</option>
                            </select>
                        </div>

                        @if ($equipment)
                            <div class="col-md-6">
                                <div class="clear-fix">
                                    <div class="row">
                                        <div class="mb-3 col-md-4">
                                            <label for="exampleFormControlInput1" class="form-label">Tipo de
                                                Equipamento:</label>
                                            <select class="form-select" aria-label="Default select example">
                                                <option selected>Selecione</option>
                                                @foreach (SelectOptions::getEquipmentOptions() as $item)
                                                    <option value="{{ $item->nick }}">{{ $item->info }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="mb-3 col-md-3">
                                            <label for="exampleFormControlInput1"
                                                class="form-label">Patrimônio:</label>
                                            <input type="text" class="form-control" id="exampleFormControlInput1">
                                        </div>
                                        <div class="mb-3 col-md-3">
                                            <label for="exampleFormControlInput1"
                                                class="form-label">Movimento:</label>
                                            <select class="form-select" aria-label="Default select example">
                                                <option selected>Selecione</option>
                                                <option value="1">Instalação</option>
                                                <option value="">Desinstalação</option>
                                            </select>
                                        </div>
                                        <div class="mb-3 col-md-4">
                                            <label for="exampleFormControlInput1" class="form-label">Fases
                                                Ligadas:</label>
                                            <select class="form-select" aria-label="Default select example">
                                                <option selected>Selecione</option>
                                                @foreach (SelectOptions::getFasesOptions() as $item)
                                                    <option value="{{ $item->nick }}">{{ $item->info }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="mb-3 col-md-3">
                                            <label for="exampleFormControlInput1" class="form-label">Poste
                                                Referencial:</label>
                                            <input type="text" class="form-control" id="exampleFormControlInput1">
                                        </div>
                                        <div class="mb-3 col-md-3">

                                            <button class="btn btn-sm btn-primary mt-3">Adicionar</button>
                                        </div>
                                        <div class="card">
                                            <h5 class="my-0 py-1 card-header">EQUIPAMENTOS</h5>
                                            @if (!empty($temp_orders))
                                                <table class="table-sm table-condensed table-striped-columns">
                                                    <tbody>
                                                        @foreach ($temp_orders as $index => $t_order)
                                                            <tr class="px-2">
                                                                <td class="text-start">{{ $t_order['ordem'] }}</td>
                                                                <td class="text-end"><i
                                                                        class="ri-delete-bin-2-line text-danger"
                                                                        wire:click="remOrders({{ $index }})"
                                                                        style="cursor: pointer;"></i></td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            @else
                                                <div class="card-body">
                                                    <h5 class="text-center">NENHUMA ORDEM ASSOCIADA</h5>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif
                    @endif
                </div>
            </div>
        </div>
    @endif
</div>
