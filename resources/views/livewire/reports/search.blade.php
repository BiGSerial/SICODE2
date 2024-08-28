@php
    use Carbon\Carbon;
    use Carbon\CarbonInterval;
    use App\Custom\Notestatus;
    use App\Helpers\FileIcon;
    
@endphp
<div>
    {{-- Carrega o Loading da página --}}
    <x-show-loading />

    <div class="card">
        <h4 class="card-header">
            BUSCAR NOTA/OV
        </h4>
        <div class="card-body">
            <div class="row">
                <div class="mb-3 col-2">
                    <label for="exampleFormControlInput1" class="form-label">Buscar</label>
                    <input class="form-control" type="text" placeholder="Informe a Nota/OV" wire:model.defer='search'>
                </div>

                <div class="mb-3 col-1">
                    <label for=""></label>
                    <button class="btn btn-sm btn-primary form-control mt-2" wire:click.prevent="Search">Buscar</button>
                </div>
            </div>
        </div>
    </div>

    @if ($lists)
        <div class="card edp-bg-sprucegreen-70 edp-text-verde-dark">
            <h4 class="card-header edp-bg-sprucegreen-100 edp-text-verde-dark">
                NOTA/OV <STRONG>{{ $lists->note }}</STRONG>
            </h4>
            <div class="card-body">
                <div class="row">
                    <div class="col-8">
                        <dl class="row">
                            <dt class="col-sm-4 edp-bg-sprucegreen-100 mb-1 align-middle">RUBRICA</dt>
                            <dd class="col-sm-8 text-white text-uppercase">{{ $lists->rubrica }}</dd>

                            @if ($lists->type_note == 2)
                                <dt class="col-sm-4 edp-bg-sprucegreen-100 mb-1 align-middle">GRUPO 1</dt>
                                <dd class="col-sm-8 text-white text-uppercase">{{ $lists->group1 }}</dd>

                                <dt class="col-sm-4 edp-bg-sprucegreen-100 mb-1 align-middle">GRUPO 2</dt>
                                <dd class="col-sm-8 text-white text-uppercase">{{ $lists->group2 }}</dd>

                                <dt class="col-sm-4 edp-bg-sprucegreen-100 mb-1 align-middle">GRUPO 4</dt>
                                <dd class="col-sm-8 text-white text-uppercase">{{ $lists->group4 }}</dd>

                                <dt class="col-sm-4 edp-bg-sprucegreen-100 mb-1 align-middle">GRUPO 5</dt>
                                <dd class="col-sm-8 text-white text-uppercase">{{ $lists->group5 }}</dd>
                            @endif

                            <dt class="col-sm-4 edp-bg-sprucegreen-100 mb-1 align-middle">DESCRICAO</dt>
                            <dd class="col-sm-8 text-white text-uppercase">{{ $lists->numPedido }}</dd>

                            <dt class="col-sm-4 edp-bg-sprucegreen-100 mb-1 align-middle">MUNICÍPIO</dt>
                            <dd class="col-sm-8 text-white text-uppercase">{{ $lists->lexp }}</dd>

                            <dt class="col-sm-4 edp-bg-sprucegreen-100 mb-1 align-middle">MMGD</dt>
                            <dd class="col-sm-8 text-white text-uppercase">{{ $lists->mmgd ? 'SIM' : 'NÃO' }}</dd>

                            <dt class="col-sm-4 edp-bg-sprucegreen-100 mb-1 align-middle">STATUS ATUAL</dt>
                            <dd class="col-sm-8 fw-bold text-white text-uppercase">{{ $lists->nstats }}</dd>

                            @if ($lists->type_note == 1)
                                <dt class="col-sm-4 edp-bg-sprucegreen-100 mb-1 align-middle">CENTRO DE TRABALHO</dt>
                                <dd class="col-sm-8 fw-bold text-white text-uppercase">{{ $lists->centerjob }}</dd>
                            @endif

                            @if ($lists->Orders->count())
                                <dl class="row">
                                    @foreach ($lists->Orders as $order)
                                        <dt class="col-sm-4 edp-bg-sprucegreen-100 mb-1 align-middle">ORDEM</dt>
                                        <dd class="col-sm-8 text-white text-uppercase">{{ $order->ordem }}</dd>
                                        @if ($order->Operations->count())
                                            <div class="table-responsive">
                                                <table class="table table-condensed table-sm table-striped">
                                                    <thead>
                                                        <tr>
                                                            <th scope="col">Operação</th>
                                                            <th scope="col">Status</th>
                                                            <th scope="col">IniPlan</th>
                                                            <th scope="col">FimPlan</th>
                                                            <th scope="col">IniReal</th>
                                                            <th scope="col">FimReal</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach ($order->Operations->sortBy('operacao') as $operation)
                                                            <tr>
                                                                <td>{{ $operation->operacao }}</td>
                                                                <td>{{ $operation->status }}</td>
                                                                <td>{{ $operation->inicioPlanejado ? Carbon::parse($operation->inicioPlanejado)->format('d/m/Y') : '-' }}
                                                                </td>
                                                                <td>{{ $operation->fimPlanejado ? Carbon::parse($operation->fimPlanejado)->format('d/m/Y') : '-' }}
                                                                </td>
                                                                <td>{{ $operation->inicioReal ? Carbon::parse($operation->inicioReal)->format('d/m/Y') : '-' }}
                                                                </td>
                                                                <td>{{ $operation->fimReal ? Carbon::parse($operation->fimReal)->format('d/m/Y') : '-' }}
                                                                </td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                        @endif
                                    @endforeach
                                </dl>
                            @endif



                        </dl>
                    </div>


                    <div class="col-4">
                        <div class="card">
                            <h5 class="card-header edp-bg-sprucegreen-100 edp-text-verde-dark">REGISTRO SICODE</h5>
                            <div class="card-body">
                                <dl class="row">
                                    <dt class="col-6">ENTRADA NO SICODE</dt>
                                    <dd class="col-6">{{ Carbon::parse($lists->created_at)->format('d/m/Y H:i:s') }}
                                    </dd>

                                    <dt class="col-6">ULTIMA MOVIMENTACAO</dt>
                                    <dd class="col-6">{{ Carbon::parse($lists->updated_at)->format('d/m/Y H:i:s') }}
                                    </dd>
                                </dl>
                            </div>
                        </div>

                        <div class="card">
                            <h5 class="card-header edp-bg-sprucegreen-100 edp-text-verde-dark">ARQUIVOS</h5>
                            @if ($lists->Files->count())
                                <table class="table table-sm table-condensed table-striped table-hover">
                                    <thead class="">
                                        <th class="text-center">
                                            {{-- <input class="form-check-input border border-1 border-secondary"
                                                            type="checkbox"></td> --}}
                                        </th>
                                        <th class="text-center col-1">Serviço</th>
                                        <th class="text-center">Tipo</th>
                                        <th class="text-center">Arquivo</th>
                                    </thead>
                                    <tbody>
                                        @foreach ($lists->Files->sortBy('file_name') as $file)
                                            {{-- @dump($file->ext) --}}
                                            <tr>
                                                <td class="text-center align-middle"><input
                                                        class="form-check-input border border-1 border-secondary"
                                                        type="checkbox" value="{{ $file->id }}"
                                                        wire:model.defer="selectedFiles"></td>
                                                <td class="text-center align-middle">
                                                    {{ isset($file->Service->service) ? $file->Service->service : '' }}
                                                </td>
                                                <td class="text-center align-middle"><i
                                                        class="{{ FileIcon::getIcon($file->ext)->icon }} fs-4 align-middle"></i>
                                                </td>
                                                <td class="text-center align-middle"><span
                                                        wire:click.prenvet="downloadFile({{ $file->id }})"
                                                        style="cursor: pointer;">{{ $file->file_name }}</span>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>

                                </table>
                                <button class="btn btn-sm btn-primary" wire:click.prevent="zipFiles"><i
                                        class="bx bxs-cloud-download"></i> Baixar
                                    Selecionados</button>
                            @else
                                <div class="card">
                                    <div class="card-body">
                                        <h4 class="text-center">SEM ARQUIVOS</h4>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                @if ($lists->Productions->count())
                    <div class="table-responsive">
                        <h5 class="edp-bg-sprucegreen-100 edp-text-verde-dark py-1 px-3 my-0 fw-bold">PROJETO</h5>
                        <table class="table table-sm table-condensed table-striped border border-1">
                            <thead class="table-dark">
                                <th scope="col">#</th>
                                <th scope="col">Serviço</th>
                                <th scope="col">Status</th>
                                <th scope="col">Usuário</th>
                                <th scope="col">Empresa</th>
                                <th scope="col">Status</th>
                                <th scope="col">Data Despacho</th>
                                <th scope="col">Data Atribuído</th>
                                <th scope="col">Data Conclusão</th>
                                <th scope="col">Parado</th>
                                <th scope="col">Conclusão</th>
                                <th scope="col">Ent Manual</th>
                                <th scope="col">Conf Prod</th>
                            </thead>
                            <tbody>
                                @foreach ($lists->Productions as $list)
                                    <tr>
                                        <td>
                                            @if ($list->d5)
                                                <span class="badge text-bg-primary align-middle">D5</span>
                                            @endif
                                        </td>
                                        <td>{{ $list->load('Service')->Service ? $list->load('Service')->Service->service : 'Desconhecido' }}
                                        </td>
                                        <td>{{ $list->status_note }}</td>
                                        <td>{{ $list->load('User')->User ? $list->load('User')->User->name : 'Desconhecido' }}
                                        </td>
                                        <td>{{ $list->load('Company')->Company ? $list->load('Company')->Company->name : 'Desconhecido' }}
                                        </td>
                                        <td> <span class="badge {{ Notestatus::status($list->status)->colorbg }}"
                                                wire:click="$emitTo('components.status.show-status', 'showStatus',  {{ $list }}, {{ $list->status }})"
                                                style="cursor: pointer;">{{ Notestatus::status($list->status)->status }}</span>
                                        </td>
                                        <td>{{ $list->dispatch_at ? date('d/m/Y H:i:s', strToTime($list->dispatch_at)) : '-' }}
                                        </td>
                                        <td>{{ $list->att_at ? date('d/m/Y H:i:s', strToTime($list->att_at)) : '-' }}
                                        </td>
                                        <td>{{ $list->completed_at ? date('d/m/Y H:i:s', strToTime($list->completed_at)) : '-' }}
                                        </td>
                                        <td>{{ CarbonInterval::seconds($list->stopped)->cascade()->forHumans(['short' => true]) }}
                                        </td>
                                        <td>@livewire('components.historic.analises', ['production_id' => $list->id], key('hist-' . $list->id))
                                        </td>
                                        <td>{{ $list->manual ? 'SIM' : 'NÃO' }}
                                        </td>
                                        <td>{{ $list->confirmed ? 'SIM' : 'NÃO' }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="card">
                        <div class="card-body">
                            <h4 class="text-center">SEM INFORMAÇÃO DE ATIVIDADES EM PROJETOS NA NOTA/OV</h4>
                        </div>
                    </div>
                @endif

                @if ($lists->Viabilities->count())
                    <div class="table-responsive">
                        <h5 class="edp-bg-sprucegreen-100 edp-text-verde-dark py-1 px-3 my-0 fw-bold">CONTRATAÇÃO</h5>
                        <table class="table table-sm table-condensed table-striped border border-1">
                            <thead class="table-dark">
                                <th scope="col">#</th>
                                <th scope="col">Ordem</th>
                                <th scope="col">Contratante</th>
                                <th scope="col">Contratado</th>
                                <th scope="col">Tácitamente</th>
                                <th scope="col">Dt Contratação</th>
                                <th scope="col">Dt Envio</th>
                                <th scope="col">Dt Retorno</th>
                                <th scope="col">Responsável</th>
                                <th scope="col">Empreiteira</th>
                                <th scope="col">Resp Informe</th>
                            </thead>
                            <tbody>
                                @foreach ($lists->Viabilities as $viab)
                                    <tr>
                                        <td class="aligh-middle"></td>
                                        <td class="aligh-middle">{{ $viab->Order->ordem }}</td>
                                        <td class="aligh-middle">{{ $viab->User->name }}</td>
                                        <td class="aligh-middle">{{ $viab->hired ? 'SIM' : 'NÃO' }}</td>
                                        <td class="aligh-middle">{{ $viab->tacit ? 'SIM' : 'NÃO' }}</td>
                                        <td class="aligh-middle">
                                            {{ $viab->hired ? date('d/m/Y H:i:s', strToTime($viab->hired_at)) : '---' }}
                                        </td>
                                        <td class="aligh-middle">
                                            {{ date('d/m/Y H:i:s', strToTime($viab->sended_at)) }}</td>
                                        <td class="aligh-middle">
                                            {{ $viab->returned_at ? date('d/m/Y H:i:s', strToTime($viab->sended_at)) : '---' }}
                                        </td>
                                        <td class="aligh-middle">{{ $viab->Engineer->name }}</td>
                                        <td class="aligh-middle">{{ $viab->Company->name }}</td>
                                        <td class="aligh-middle">{{ $viab->Form ? $viab->Form->responsible : '---' }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="card">
                        <div class="card-body">
                            <h4 class="text-center">SEM INFORMAÇÃO DE CONTRATAÇÃO NA NOTA/OV</h4>
                        </div>
                    </div>
                @endif


                @if ($lists->WorkForm)
                    <div class="table-responsive">
                        <h5 class="edp-bg-sprucegreen-100 edp-text-verde-dark py-1 px-3 my-0 fw-bold">CONCLUSÃO DE OBRA
                            (INFORME)</h5>
                        <table class="table table-sm table-condensed table-striped border border-1">

                            <thead class="table-dark">
                                <tr>

                                    <th class="text-center" scope="col">Ordens</th>
                                    <th class="text-center" scope="col">Equipamentos</th>
                                    <th class="text-center" scope="col">Alteração</th>
                                    <th class="text-center" scope="col">Equipe WPA</th>
                                    <th class="text-center" scope="col">Responsável</th>
                                    <th class="text-center" scope="col">Conclusão Informada</th>
                                    <th class="text-center" scope="col">Entregue Em</th>
                                </tr>
                            </thead>
                            <tbody>

                                <tr wire:click="$emitTo('partner.show.show-work-form', 'show_form', {{ $lists->WorkForm }})"
                                    wire:key="{{ $lists->WorkForm->id }}" style="cursor: pointer;">

                                    <td class="text-center align-middle">
                                        @if ($lists->WorkForm->Orders->count())
                                            @foreach ($lists->WorkForm->Orders as $order)
                                                <p class="my-0 py-0">{{ $order->ordem }}</p>
                                            @endforeach
                                        @endif
                                    </td>
                                    <td class="text-center align-middle">
                                        {!! $lists->WorkForm->Equipment->count()
                                            ? "<span class='badge text-bg-dark'>" . $lists->WorkForm->Equipment->count() . '</span>'
                                            : '' !!}
                                    </td>
                                    <td class="text-center align-middle">
                                        {{ $lists->WorkForm->changes ? 'SIM' : 'NÂO' }}
                                    </td>
                                    <td class="text-center align-middle">
                                        {{ $lists->WorkForm->team ? $lists->WorkForm->team : 'Desconhecido' }}
                                    </td>
                                    <td class="text-center align-middle">
                                        {{ $lists->WorkForm->responsible ? $lists->WorkForm->responsible : 'Desconhecido' }}
                                    </td>
                                    <td class="text-center align-middle">
                                        {{ $lists->WorkForm->date ? date('d/m/Y', strToTime($lists->WorkForm->date)) : 'Desconhecido' }}
                                    </td>
                                    <td class="text-center align-middle">
                                        {{ $lists->WorkForm->informed_at ? date('d/m/Y', strToTime($lists->WorkForm->informed_at)) : 'Desconhecido' }}
                                    </td>
                                </tr>

                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="card">
                        <div class="card-body">
                            <h4 class="text-center">SEM INFORME DE OBRA</h4>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    @else
        <div class="card">
            <div class="card-body">
                <h4 class="text-center">NADA PARA EXIBIR</h4>
            </div>
        </div>
    @endif

    {{-- Modals Components --}}
    @livewire('partner.show.show-work-form', key('FormModdalShow'))
    @livewire('components.status.show-status', key('show_status_note'))
</div>
