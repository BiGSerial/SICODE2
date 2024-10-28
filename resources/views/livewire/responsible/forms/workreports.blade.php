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
                    <button type="button" class="btn btn-sm btn-primary text-center"
                        wire:click.prevent="search()">BUSCAR</button>
                </div>
                @if ($notes && $notes->count())
                    <div>
                        <h6 class="fw-bold">SELECIONE UMA OBRA PARA INFORMAR</h6>
                        <table class="table table-sm table-condensed table-striped">
                            <tbody>
                                @foreach ($notes as $note)
                                    @if (!$note->WorkForm)
                                        <tr wire:key="{{ $note->id }}"
                                            wire:click="toConfirmWork({{ $note }})" style="cursor: pointer;">
                                            <td class="fw-bold align-middle">{{ $note->note }}</td>
                                            <td class="align-middle">
                                                @if ($note->Orders->count())
                                                    @foreach ($note->Orders->filter(function ($order) {
        return !(strpos($order->statusSist, 'ENT') === 0 || strpos($order->statusSist, 'ENC') === 0);
    }) as $order)
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
                                            <td class="align-middle fw-bold">
                                                {{ $note->WorkForm ? 'OBRA INFORMADA' : 'NÃO INFORMADA' }}
                                            </td>
                                        </tr>
                                    @else
                                        <tr wire:key="{{ $note->id }}">
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
                                            <td class="align-middle fw-bold">
                                                {{ $note->WorkForm ? 'OBRA INFORMADA' : 'NÃO INFORMADA' }}
                                            </td>
                                        </tr>
                                    @endif
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    @endif

    @if ($this->note)
        <form wire:submit.prevent="submit">
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
                                        <td class="align-middle text-end" style="width: 150px;">Group4</td>
                                        <td class="align-middle">{{ $note->group4 }}</td>
                                    </tr>
                                    <tr>
                                        <td class="align-middle text-end" style="width: 150px;">Group5</td>
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
                                Informe. <span class="text-danger fw-bold">*</span> (Obs: Adicione todas as Ordens que
                                constem no projeto.)
                                <i class="ri-question-line text-primary fw-bold" data-bs-toggle="popover"
                                    data-bs-title="Popover title"
                                    data-bs-content="And here's some amazing content. It's very engaging. Right?"></i></label>
                            <select class="form-select mb-3" aria-label="Default select example"
                                wire:model.defer="s_order">
                                <option selected>Selecionar</option>
                                @if ($note->Orders->count())
                                    @foreach ($note->Orders->filter(function ($order) {
        return !(strpos($order->statusSist, 'ENT') === 0 || strpos($order->statusSist, 'ENC') === 0);
    }) as $order)
                                        <option value="{{ $order->id }}">{{ $order->ordem }}</option>
                                    @endforeach
                                @endif
                            </select>

                            <button type="button" class="btn btn-sm btn-primary mb-3"
                                wire:click="addOrders()">Adicionar</button>

                            <div class="card">
                                <h5 class="my-0 py-1 card-header">ORDENS/DRs RELACIONADAS</h5>
                                @if (!empty($temp_orders))
                                    <table class="table table-sm table-condensed table-striped-columns">
                                        <tbody>
                                            @foreach ($temp_orders as $index => $t_order)
                                                <tr class="px-2">
                                                    <td class="text-center">{{ $t_order['ordem'] }}</td>
                                                    <td class="text-center"><i class="ri-delete-bin-2-line text-danger"
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

                        @if (session()->has('message'))
                            <div class="alert alert-success">
                                {{ session('message') }}
                            </div>
                        @endif

                        @if (!empty($temp_orders))
                            <div class="mb-3" style="max-width: 300px">
                                <label for="exampleFormControlInput1" class="form-label">Data Conclusão da Obra: <span
                                        class="text-danger fw-bold">*</span></label>
                                <input type="date" class="form-control" id="dateWork" max="{{ date('Y-m-d') }}"
                                    wire:model.defer="form.date">
                                @error($form['date'])
                                    <span class="error">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="mb-3 " style="max-width: 300px">
                                <label for="exampleFormControlInput1" class="form-label">Houve Instalação ou
                                    Desinstalação
                                    de Equipamento? <span class="text-danger fw-bold">*</span></label>
                                <select class="form-select" aria-label="Default select example"
                                    wire:model="form.equipment">
                                    <option selected>Selecione</option>
                                    <option value="1">Sim</option>
                                    <option value="0">Não</option>
                                </select>
                            </div>

                            @if ($form['equipment'])
                                <div class="col-md-6">
                                    <div class="clear-fix">
                                        <div class="card">
                                            <div class="card-header">
                                                <h5 class="my-0 py-0">CADASTRAR EQUIPAMENTOS</h5>
                                            </div>
                                            <div class="card-body">

                                                <div class="row">
                                                    <div class="mb-3 col-md-4">
                                                        <label for="exampleFormControlInput1" class="form-label">Tipo
                                                            de
                                                            Equipamento:</label>
                                                        <select class="form-select"
                                                            aria-label="Default select example" id="type"
                                                            wire:model.defer="model_equipment.type">
                                                            <option value="" selected>Selecione</option>
                                                            @foreach (SelectOptions::getEquipmentOptions() as $item)
                                                                <option value="{{ $item->nick }}">
                                                                    {{ $item->info }}
                                                                </option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                    <div class="mb-3 col-md-3">
                                                        <label for="exampleFormControlInput1"
                                                            class="form-label">Patrimônio:</label>
                                                        <input type="text" class="form-control" id="patrimony"
                                                            wire:model.defer="model_equipment.patrimony">
                                                    </div>
                                                    <div class="mb-3 col-md-3">
                                                        <label for="exampleFormControlInput1"
                                                            class="form-label">Movimento:</label>
                                                        <select class="form-select"
                                                            aria-label="Default select example"
                                                            wire:model.defer="model_equipment.installed">
                                                            <option selected>Selecione</option>
                                                            <option value="1">Instalação</option>
                                                            <option value="0">Desinstalação</option>
                                                        </select>
                                                    </div>
                                                    <div class="mb-3 col-md-4">
                                                        <label for="exampleFormControlInput1" class="form-label">Fases
                                                            Ligadas:</label>
                                                        <select class="form-select"
                                                            aria-label="Default select example" id="fases"
                                                            wire:model.defer="model_equipment.fases">
                                                            <option selected>Selecione</option>
                                                            @foreach (SelectOptions::getFasesOptions() as $item)
                                                                <option value="{{ $item->nick }}">
                                                                    {{ $item->info }}
                                                                </option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                    <div class="mb-3 col-md-3">
                                                        <label for="exampleFormControlInput1" class="form-label">Poste
                                                            Referencial:</label>
                                                        <input type="text" class="form-control" id="pole"
                                                            wire:model.defer="model_equipment.pole">
                                                    </div>
                                                    <div class="mb-3 col-md-3">

                                                        <button type="button" class="btn btn-sm btn-primary mt-4"
                                                            wire:click="addEquipment()">Adicionar</button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="card">
                                            <div class="card-header">
                                                <h5 class="my-0 py-0">EQUIPAMENTOS</h5>
                                            </div>
                                            @if (!empty($temp_equipment))
                                                <table class="table table-sm table-condensed table-striped-columns">
                                                    <thead>
                                                        <tr class="table-secondary">
                                                            <th scope='col' class="text-center">Tipo</th>
                                                            <th scope='col' class="text-center">Patrimônio</th>
                                                            <th scope='col' class="text-center">Movimento</th>
                                                            <th scope='col' class="text-center">Fases</th>
                                                            <th scope='col' class="text-center">Poste</th>
                                                            <th scope='col' class="text-center"></th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach ($temp_equipment as $index => $equip)
                                                            <tr class="px-2">
                                                                <td class="text-center align-middle">
                                                                    {{ $equip['type'] }}
                                                                </td>
                                                                <td class="text-center align-middle">
                                                                    {{ $equip['patrimony'] }}</td>
                                                                <td class="text-center align-middle">
                                                                    {{ $equip['installed'] ? 'INSTALAÇÃO' : 'DESINSTALAÇÃO' }}
                                                                </td>
                                                                <td class="text-center align-middle">
                                                                    {{ $equip['fases'] }}
                                                                </td>
                                                                <td class="text-center align-middle">
                                                                    {{ $equip['pole'] }}
                                                                </td>
                                                                <td class="text-center align-middle"><i
                                                                        class="ri-delete-bin-2-line text-danger"
                                                                        wire:click="remEquipment({{ $index }})"
                                                                        style="cursor: pointer;"></i></td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            @else
                                                <div class="card-body">
                                                    <h5 class="text-center">NENHUM EQUIPAMENTO ASSOCIADO</h5>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endif

                            <div class="mb-3 " style="max-width: 300px">
                                <label for="exampleFormControlInput1" class="form-label">Houve Alterações no
                                    projeto? <span class="text-danger fw-bold">*</span></label>
                                <select class="form-select" aria-label="Default select example"
                                    wire:model="form.changes">
                                    <option selected>Selecione</option>
                                    <option value="1">Sim</option>
                                    <option value="0">Não</option>
                                </select>
                            </div>

                            <div class="mb-3 col-md-6">
                                @livewire('files.manager.create-gen-files', ['note' => $note, 'service' => 'INFORME DE OBRA'], key('files_forms'))
                            </div>

                            <div class="mb-3 col-md-6">
                                <label for="exampleFormControlInput1" class="form-label">Observações (Desligamento
                                    programado/ Alterações/ Informações Gerais): </label>
                                <textarea type="text" class="form-control" id="observacao" rows="4" wire:model.defer="form.observation"> </textarea>
                            </div>

                            <div class="mb-3 " style="max-width: 300px">
                                <label for="exampleFormControlInput1" class="form-label">Houveram danos a propriedade
                                    de
                                    particulares? (Ex.: Calçada Quebrada, Padrão Danificado, e outros.) <span
                                        class="text-danger fw-bold">*</span></label>
                                <select class="form-select" aria-label="Default select example"
                                    wire:model="form.damage" id="damage">
                                    <option selected>Selecione</option>
                                    <option value="1">Sim</option>
                                    <option value="0">Não</option>
                                </select>
                            </div>

                            @if ($form['damage'])
                                <div class="mb-3 col-md-6">
                                    <label for="exampleFormControlInput1" class="form-label">Detalhar os Danos
                                        Causados e
                                        Previsão de reparo: <span class="text-danger fw-bold">*</span></label>
                                    <textarea type="text" class="form-control" id="description" rows="4" wire:model.defer="form.description"> </textarea>
                                </div>
                            @endif

                            <div class="mb-3 " style="max-width: 300px">
                                <label for="exampleFormControlInput1" class="form-label">Ligação foi executada do
                                    momento
                                    da obra? <span class="text-danger fw-bold">*</span></label>
                                <select class="form-select" aria-label="Default select example"
                                    wire:model="form.connection" id="connection">
                                    <option selected>Selecione</option>
                                    <option value="1">Sim</option>
                                    <option value="0">Não</option>
                                </select>
                            </div>

                            <div class="mb-3 " style="max-width: 300px">
                                <label for="exampleFormControlInput1" class="form-label">Foram Instalados
                                    Medidores? <span class="text-danger fw-bold">*</span></label>
                                <select class="form-select" aria-label="Default select example" wire:model="meeters">
                                    <option selected>Selecione</option>
                                    <option value="1">Sim</option>
                                    <option value="0">Não</option>
                                </select>
                            </div>

                            @if ($meeters)
                                <div class="col-md-6">
                                    <div class="clear-fix">
                                        <div class="card">
                                            <div class="card-header">
                                                <h5 class="my-0 py-0">CADASTRAR MEDIDORES</h5>
                                            </div>
                                            <div class="card-body">

                                                <div class="row">

                                                    <div class="mb-3 col-md-3">
                                                        <label for="exampleFormControlInput1"
                                                            class="form-label">Número:</label>
                                                        <input type="text" class="form-control" id="number"
                                                            wire:model.defer="model_meeter.number">
                                                    </div>

                                                    <div class="mb-3 col-md-3">
                                                        <label for="exampleFormControlInput1"
                                                            class="form-label">Bornes:</label>
                                                        <input type="text" class="form-control" id="borne"
                                                            wire:model.defer="model_meeter.borne">
                                                    </div>
                                                    <div class="mb-3 col-md-4">
                                                        <label for="exampleFormControlInput1" class="form-label">Fases
                                                            Ligadas:</label>
                                                        <select class="form-select"
                                                            aria-label="Default select example" id="m_fases"
                                                            wire:model.defer="model_meeter.fases">
                                                            <option selected>Selecione</option>
                                                            @foreach (SelectOptions::getFasesOptions() as $item)
                                                                <option value="{{ $item->nick }}">
                                                                    {{ $item->info }}
                                                                </option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                    <div class="mb-3 col-md-3">

                                                        <button type="button" class="btn btn-sm btn-primary mt-4"
                                                            wire:click="addMeeters()">Adicionar</button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="card">
                                            <div class="card-header">
                                                <h5 class="my-0 py-0">MEDIDORES</h5>
                                            </div>
                                            @if (!empty($temp_meeters))
                                                <table class="table table-sm table-condensed table-striped-columns">
                                                    <thead>
                                                        <tr class="table-secondary">
                                                            <th scope='col' class="text-center">Numero</th>
                                                            <th scope='col' class="text-center">Borne</th>
                                                            <th scope='col' class="text-center">Fases</th>
                                                            <th scope='col' class="text-center"></th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach ($temp_meeters as $index => $sMeeter)
                                                            <tr class="px-2">
                                                                <td class="text-center align-middle">
                                                                    {{ $sMeeter['number'] }}
                                                                </td>
                                                                <td class="text-center align-middle">
                                                                    {{ $sMeeter['borne'] }}</td>
                                                                <td class="text-center align-middle">
                                                                    {{ $sMeeter['fases'] }}
                                                                </td>

                                                                <td class="text-center align-middle"><i
                                                                        class="ri-delete-bin-2-line text-danger"
                                                                        wire:click="remMeeters({{ $index }})"
                                                                        style="cursor: pointer;"></i></td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            @else
                                                <div class="card-body">
                                                    <h5 class="text-center">NENHUM MEDIDOR ASSOCIADO</h5>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endif

                            <div class="mb-3 col-md-3">
                                <label for="exampleFormControlInput1" class="form-label">Numero da DD (Ultimo
                                    Relacionado a esta obra) <span class="text-danger fw-bold">*</span></label>
                                <input type="text" class="form-control" id="dd"
                                    wire:model.defer="form.dd">
                            </div>

                            <div class="mb-3 col-md-3">
                                <label for="exampleFormControlInput1" class="form-label">Nome da Equipe (WPA) <span
                                        class="text-danger fw-bold">*</span>:</label>
                                <input type="text" class="form-control" id="team"
                                    wire:model.defer="form.team">
                            </div>

                            <div class="mb-3 col-md-3">
                                <label for="exampleFormControlInput1" class="form-label">Qual o encarregado
                                    responsável
                                    pela execução da atividade? <span class="text-danger fw-bold">*</span></label>
                                <input type="text" class="form-control" id="responsible"
                                    wire:model.defer="form.responsible">
                            </div>

                            <div class="mb-3 col-md-3">
                                <label for="exampleFormControlInput1" class="form-label">Responsável por este
                                    informe? <span class="text-danger fw-bold">*</span></label>
                                <input type="text" class="form-control" id="informer"
                                    wire:model.defer="form.informer">
                            </div>

                            <button class="btn btn-sm btn-primary" type="submit">ENVIAR</button>
                            <button class="btn btn-sm btn-danger" type="reset"
                                wire:click='calcelForm()'>CANCELAR</button>
                        @endif
                    </div>
                </div>
            </div>
        </form>
    @endif
</div>

@push('script')
    <script>
        const popoverTriggerList = document.querySelectorAll('[data-bs-toggle="popover"]')
        const popoverList = [...popoverTriggerList].map(popoverTriggerEl => new bootstrap.Popover(popoverTriggerEl))

        document.querySelectorAll('form button[type="button"]').forEach(button => {
            button.addEventListener('click', function(event) {
                event.preventDefault();
            });
        });
    </script>
@endpush
