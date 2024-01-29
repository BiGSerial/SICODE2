@php
    use Carbon\Carbon;
    use Carbon\CarbonInterval;
    use App\Custom\Notestatus;
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
        <div class="card">
            <h4 class="card-header">
                NOTA/OV <STRONG>{{ $lists->note }}</STRONG>
            </h4>
            <div class="card-body">
                <dl class="row">
                    <dt class="col-sm-4">RUBRICA</dt>
                    <dd class="col-sm-8">{{ $lists->rubrica }}</dd>

                    <dt class="col-sm-4">GRUPO 1</dt>
                    <dd class="col-sm-8">{{ $lists->group1 }}</dd>

                    <dt class="col-sm-4">DESCRICAO</dt>
                    <dd class="col-sm-8">{{ $lists->numPedido }}</dd>

                    <dt class="col-sm-4">MUNICÍPIO</dt>
                    <dd class="col-sm-8">{{ $lists->lexp }}</dd>

                    <dt class="col-sm-4">MMGD</dt>
                    <dd class="col-sm-8">{{ $lists->mmgd ? 'SIM' : 'NÃO' }}</dd>

                    <dt class="col-sm-4">STATUS ATUAL</dt>
                    <dd class="col-sm-8">{{ $lists->nstats }}</dd>
                </dl>

                @if ($lists->Productions->count())
                    <div class="table-responsive">
                        <table class="table table-sm table-condensed table-striped border border-1">
                            <thead class="table-dark">
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
                                        <td>{{ $list->load('Service')->Service ? $list->load('Service')->Service->service : 'Desconhecido' }}
                                        </td>
                                        <td>{{ $list->status_note }}</td>
                                        <td>{{ $list->load('User')->User ? $list->load('User')->User->name : 'Desconhecido' }}
                                        </td>
                                        <td>{{ $list->load('Company')->Company ? $list->load('Company')->Company->name : 'Desconhecido' }}
                                        </td>
                                        <td> <span
                                                class="badge {{ Notestatus::status($list->status)->colorbg }}">{{ Notestatus::status($list->status)->status }}</span>
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
                            <h4 class="text-center">SEM INFORMAÇÃO DE ATIVIDADES NA NOTA/OV</h4>
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
</div>
