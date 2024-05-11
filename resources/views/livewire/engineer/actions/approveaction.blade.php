@php
    use App\Helpers\SelectOptions;
@endphp
<div x-data="{ show1: false, show2: false, text: '', show3: false }">
    <x-show-loading />

    @if (!$blkReturn || $newReturn)

        <p class="fw-bold fs-6 my-0 py-0">Comentário:</p>
        <p class="mb-2 mb-0 py-0">
            <textarea class="form-control border border-secondary" cols="30" rows="6" wire:model.defer="comment"></textarea>
        </p>
        {{-- <div class="form-check">
        <input class="form-check-input border-1 border-secondary" type="checkbox" wire:model.defer="restrict">
        <label class="form-check-label" for="flexCheckDefault">
            Restrito
        </label>
    </div> --}}

        <div class="clear-fix" x-show="show1==false && show2==false">
            <div class="d-flex justify-content-end mb-3">
                @if (!$blkResponse)
                    <button class="btn btn-sm btn-danger mx-2"
                        @click="show1 = true, show2 = false, text = 'Discordar da Análise de Viabilidade?'">DISCORDAR</button>
                @endif
                @if ($newReturn)
                    <button class="btn btn-primary btn-sm" wire:click="newReturn(false)">Cancelar</button>
                @endif
                <button class="btn btn-sm btn-primary mx-2"
                    @click="show1 = false, show2 = true, text = 'Concordar com a Analise da Viabilidade?'">CONCORDAR</button>
            </div>
        </div>

        <div class="card" x-show="show2" @click.away="show1 = show2 = false">
            <div class="card-body">
                <div class="clear-fix">
                    <div class="mb-3">
                        <label for="service_s" class="form-label">Retornar para Serviço:</label>
                        <select class="form-select" wire:model="service_s">
                            @if (isset($services) && $services->count())
                                <option value="" selected>Selecione um Serviço</option>
                                @foreach ($services as $service)
                                    <option value="{{ $service->uuid }}">{{ $service->service }}</option>
                                @endforeach

                            @endif
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="service_s" class="form-label">Categoria:</label>
                        <select class="form-select" wire:model="category">

                            <option value="" selected>Selecione Um Motivo</option>
                            @foreach (SelectOptions::getReclaimsOptions() as $option)
                                <option value="{{ $option->value }}">{{ $option->info }}</option>
                            @endforeach


                        </select>
                    </div>
                    @if ($lastUser)
                        <div class="mb-3">
                            <h5 class="fw-bold text-center">Produção Encontrada para Retorno</h5>
                            <div class="border border-1 rounded p-2 shadow">
                                <table class="table table-sm table-striped my-0">
                                    <thead class="border-top border-secondary">
                                        <th scope="col">Serviço</th>
                                        <th scope="col">RI</th>
                                        <th scope="col">Usuario</th>
                                        <th scope="col">Completado em</th>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>{{ $lastUser->Service->service }}</td>
                                            <td>{{ $lastUser->d5 ? 'SIM' : 'NÂO' }}</td>
                                            <td>{{ $lastUser->User->name }}</td>
                                            <td>{{ date('d/m/Y H:i:s', strToTime($lastUser->completed_at)) }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @elseif (!$lastUser && $service_s)
                        <div class="mb-3">
                            <h5 class="fw-bold text-center">Nanhuma Produção Encontrada para Retorno</h5>
                            <p class="text-justify p-2 border border-1 rounded">Será retornado para o serviço
                                selecionado,
                                ficando a cargo de um despachante o
                                redirecionamento para um usuário disponível.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>


        <div class="card" x-show="(show1 || (show2 && $service_s))">

            <div class="card-body">
                <p class="fs-4 fw-bold text-center mb-3" x-text="text"></p>
                <div class="d-flex justify-content-center">

                    <button class="btn btn-sm btn-primary mx-2" wire:click.prevent="desagree"
                        x-show="show1">SIM</button>
                    <button class="btn btn-sm btn-primary mx-2" wire:click.prevent="agree" x-show="show2">SIM</button>
                    <button class="btn btn-sm btn-danger mx-2" @click="show1 = show2 = false">CANCELAR</button>
                </div>
            </div>
        </div>
    @else
        <button class="btn btn-primary btn-sm" wire:click="newReturn(true)">NOVO RETORNO</button>
    @endif


</div>
