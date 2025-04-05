@php
    use App\Custom\Viabilitiesstatus;
    use App\Custom\Notestatus;
    use App\Helpers\SelectOptions;
    use Carbon\Carbon;
    use App\Helpers\DaysLeft;
@endphp
<div>
    <x-show-loading />

    <div wire:ignore.self class="modal fade" id="modal_resp_viability" tabindex="-1" aria-labelledby="exampleModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-xl  modal-dialog-scrollable">
            <div class="modal-content edp-bg-stategrey-50">
                <div class="modal-header edp-bg-sprucegreen-70 text-edp-verde">
                    <h4 class="my-auto fw-bold">
                        RETORNO INTERNO
                    </h4>
                </div>
                <div class="modal-body">
                    @if ($viability)
                        @php
                            $status = null;

                            $dueDate = Carbon::parse($viability->sended_at)->addDays($viability->getDays() + 7);

                            $today = Carbon::now();
                            $daysDifference = 0;

                            if ($dueDate) {
                                $daysDifference = $today->diffInDays($dueDate);

                                if ($dueDate->isBefore($today)) {
                                    $daysDifference *= -1;
                                }

                                if ($daysDifference < 1) {
                                    $status = [
                                        'color' => 'text-bg-danger',
                                        'info' => 'VENCIDO',
                                    ];
                                } elseif ($daysDifference >= 1 && $daysDifference < 3) {
                                    $status = [
                                        'color' => 'text-bg-warning',
                                        'info' => 'VENCENDO',
                                    ];
                                } elseif ($daysDifference >= 3) {
                                    $status = [
                                        'color' => 'text-bg-success',
                                        'info' => 'NO PRAZO',
                                    ];
                                }
                            }

                            $block = null;
                            $color = 'grey';
                            $days_left = (new DaysLeft($viability->Note))->getDaysLeft();
                            $count = 0;

                            if ($viability->approved) {
                                $count++;
                                $block = [
                                    'color' => 'success',
                                    'command' => true,
                                ];

                                $color = 'green';
                            } elseif ($viability->rejected) {
                                $count++;
                                $block = [
                                    'color' => 'danger',
                                    'command' => true,
                                ];

                                $color = 'red';
                            }

                            if (($viability->rejected || $viability->approved) && !$viability->completed) {
                                $status = [
                                    'color' => 'text-bg-primary',
                                    'info' => 'EM AVALIAÇÂO',
                                ];
                            }

                            // if (!$count) {
                            //     $block = array_merge($block, ['command' => false]);
                            // }

                        @endphp
                        <div class="row">
                            <div class="col-6">
                                <div class="card">
                                    <h5 class="card-header py-1 my-0 edp-bg-sprucegreen-70 text-edp-verde">INFORMAÇÕES
                                    </h5>
                                    <div class="table-responsive">
                                        <table class="table table-sm table-condensed table-striped-columns">
                                            <tbody>
                                                <tr>
                                                    <td class="fw-bold col-2 align-middle">NOTA/OV:</td>
                                                    <td class="align-middle fw-bold">{{ $viability->Note->note }}</td>
                                                </tr>
                                                <tr>
                                                    <td class="fw-bold col-2 align-middle">ORDEM:</td>
                                                    <td class="align-middle">
                                                        @if ($viability->Orders->isNotEmpty())
                                                            @foreach ($viability->Orders as $order)
                                                                <p class="p-0 m-1">
                                                                    {{ $order->ordem }}
                                                                </p>
                                                            @endforeach
                                                        @endif
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td class="fw-bold col-2 align-middle">CLIENTE:</td>
                                                    <td class="align-middle text-uppercase">
                                                        {{ $viability->Note->client }}</td>
                                                </tr>
                                                <tr>
                                                    <td class="fw-bold col-2 align-middle">DESCRIÇÃO:</td>
                                                    <td class="align-middle text-uppercase">
                                                        {{ $viability->Note->material }}
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td class="fw-bold col-2 align-middle">RUBRICA:</td>
                                                    <td class="align-middle text-uppercase">
                                                        {{ $viability->Note->rubrica }}</td>
                                                </tr>
                                                <tr>
                                                    <td class="fw-bold col-2 align-middle">MUNICIPIO:</td>
                                                    <td class="align-middle">{{ $viability->Note->lexp }}</td>
                                                </tr>
                                                <tr>
                                                    <td class="fw-bold col-2 align-middle">CONTRATADO:</td>
                                                    <td class="align-middle">
                                                        @if ($viability->hired)
                                                            <span class="text-success fw-bold fs-6">CONTRATADO</span>
                                                        @else
                                                            <span class="text-danger fw-bold fs-6">NÃO CONTRATADO</span>
                                                        @endif
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td class="fw-bold col-2 align-middle">CONTRATADO EM:</td>
                                                    <td class="align-middle">
                                                        @if ($viability->hired)
                                                            <span
                                                                class="fw-bold">{{ date('d/m/Y H:i:s', strToTime($viability->hired_at)) }}</span>
                                                        @else
                                                            <span class="fw-bold"> --- </span>
                                                        @endif
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td class="fw-bold col-2 align-middle">STATUS VIAB:</td>
                                                    <td class="align-middle">
                                                        @if ($viability->approved && !$viability->rejected)
                                                            <span class="text-success fs-6"> APROVADO </span>
                                                        @elseif (!$viability->approved && $viability->rejected)
                                                            <span class="text-danger fs-6"> REJEITADO </span>
                                                        @else
                                                            <span class="text-secondary fs-6"> DESCONHECIDO </span>
                                                        @endif
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td class="fw-bold col-2 align-middle">RECEBIDO EM:</td>
                                                    <td class="align-middle">
                                                        @if ($viability)
                                                            <span
                                                                class="fw-bold">{{ Carbon::parse($viability->sended_at)->format('d/m/Y H:i:s') }}</span>
                                                        @else
                                                            <span class="fw-bold"> --- </span>
                                                        @endif
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td class="fw-bold col-2 align-middle">PRAZO VIABILIDADE:</td>
                                                    <td class="align-middle">
                                                        @if ($dueDate)
                                                            <span class="fw-bold text-danger">
                                                                {{ $dueDate->format('d/m/Y') }}</span>
                                                        @else
                                                            <span class="fw-bold"> --- </span>
                                                        @endif
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td class="fw-bold col-2 align-middle">PRAZO OBRA:</td>
                                                    <td class="align-middle">
                                                        <span
                                                            class="fw-bold text-primary">{{ Carbon::now()->addDays($days_left)->format('d/m/Y') }}</span>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td class="fw-bold col-2 align-middle">STATUS:</td>
                                                    <td class="align-middle">
                                                        <span
                                                            class="badge {{ Viabilitiesstatus::status($viability->status)->colorbg }} word-wrap">{{ Viabilitiesstatus::status($viability->status)->status }}</span>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td class="fw-bold col-2 align-middle">RESPONSÁVEL:</td>
                                                    <td class="align-middle">
                                                        @if ($viability->Engineer)
                                                            <span
                                                                class="fw-bold text-secondary">{{ $viability->Engineer->name }}
                                                                ( {{ $viability->Engineer->email }} )</span>
                                                        @endif

                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>



                                <div class="card mt-2">
                                    <h5 class="card-header py-1 my-0 edp-bg-sprucegreen-70 text-edp-verde">ARQUIVOS
                                        ANEXADOS
                                    </h5>
                                    <div class="card-body py-2 px-3">
                                        @livewire('components.files.show-files-pool', ['files' => $viability->Note->Files], key('files-pool-' . $viability->Note->id))
                                    </div>
                                </div>

                            </div>

                            <div class="col-6">
                                @if ($viability->Form)
                                    @php
                                        $form = $viability->Form;
                                    @endphp
                                    <div class="card">
                                        <h5 class="card-header py-1 my-0 edp-bg-sprucegreen-70 text-edp-verde">RETORNO
                                            VIABILIDADE</h5>
                                        <div class="table-responsive">
                                            <table class="table table-sm table-condensed table-striped-columns">
                                                <tbody>
                                                    <tr>
                                                        <td class="fw-bold col-2 align-middle">MOTIVO:</td>
                                                        <td class="align-middle fw-bold">{{ $form->reason }}</td>
                                                    </tr>
                                                    <tr>
                                                        <td class="fw-bold col-2 align-middle">IMPACTO:</td>
                                                        <td class="align-middle">
                                                            {{ $form->changes * 10 }}%
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td class="fw-bold col-2 align-middle">RESPONSÁVEL:</td>
                                                        <td class="align-middle text-uppercase">
                                                            {{ $form->responsible }}</td>
                                                    </tr>
                                                    <tr>
                                                        <td class="fw-bold col-2 align-middle">DESCRIÇÃO:</td>
                                                        <td class="align-middle">{{ $form->description }}</td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                @endif


                                @if ($viability->Comments->count())

                                    <div class="card">
                                        <h5 class="card-header py-1 my-0 edp-bg-sprucegreen-70 text-edp-verde">
                                            COMENTÁRIOS</h5>
                                        <div class="table-responsive">
                                            <table class="table table-sm table-condensed table-striped-columns">
                                                <tbody>

                                                    @foreach ($viability->Comments as $comment)
                                                        <tr>
                                                            <td class="col-2">
                                                                {{ date('d/m/Y H:i', strToTime($comment->created_at)) }}
                                                            </td>
                                                            <td class="fw-bold col-2">{{ $comment->User->name }}
                                                            </td>
                                                            <td class="col">{{ $comment->message }}
                                                            </td>
                                                        </tr>
                                                    @endforeach


                                                </tbody>
                                            </table>
                                        </div>
                                    </div>

                                @endif

                                @if ($viability->Reclaims->isNotEmpty())

                                    <div class="card">
                                        <h5 class="card-header py-1 my-0 edp-bg-sprucegreen-70 text-edp-verde">
                                            RETORNO INTERNO</h5>
                                        <div class="table-responsive">
                                            <table class="table table-sm table-condensed table-striped-columns">
                                                <tbody>

                                                    @if ($viability->Reclaims->last()->completed)
                                                        <tr>
                                                            <td class="text-end fw-bold  col-3">Serviço:</td>
                                                            <td class="col text-uppercase fw-bold">
                                                                {{ $viability->Reclaims->last()->service->service }}
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <td class="text-end fw-bold  col-3">Motivo:</td>
                                                            <td class="col">
                                                                {{ $viability->Reclaims->last()->category }}
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <td class="text-end fw-bold  col-3">Informações:</td>
                                                            <td class="col">
                                                                @if ($viability->Reclaims->last()->Comments->isNotEmpty())
                                                                    @foreach ($viability->Reclaims->last()->Comments as $comment)
                                                                        <p class="my-1 py-0">{{ $comment->message }}
                                                                        </p>
                                                                    @endforeach

                                                                @endif
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <td class="text-end fw-bold  col-3">Data Envio:</td>
                                                            <td class="col">
                                                                {{ date('d/m/Y H:i', strToTime($viability->Reclaims->last()->created_at)) }}
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <td class="text-end fw-bold  col-3">Data Att:</td>
                                                            <td class="col align-middle">
                                                                @if ($viability->Reclaims->last()->Production)
                                                                    {{ date('d/m/Y H:i', strToTime($viability->Reclaims->last()->Production->att_at)) }}
                                                                @endif
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <td class="text-end fw-bold  col-3">Data Conclusao:</td>
                                                            <td class="col align-middle">
                                                                @if ($viability->Reclaims->last()->Production)
                                                                    {{ date('d/m/Y H:i', strToTime($viability->Reclaims->last()->Production->completed_at)) }}
                                                                @endif
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <td class="text-end fw-bold  col-3">Resultado:</td>
                                                            <td class="col align-middle">
                                                                @if ($viability->Reclaims->last()->Production && $viability->Reclaims->last()->Production->Analise)
                                                                    @php
                                                                        $texts = [];

                                                                        if (
                                                                            $viability->Reclaims->last()->Production
                                                                                ->Analise->info
                                                                        ) {
                                                                            $texts = explode(
                                                                                "\n",
                                                                                $viability->Reclaims->last()->Production
                                                                                    ->Analise->info,
                                                                            );
                                                                        }
                                                                    @endphp

                                                                    @foreach ($texts as $text)
                                                                        <p class="my-0 py-0 mx-2">{{ $text }}
                                                                        </p>
                                                                    @endforeach

                                                                @endif
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <td class="text-end fw-bold  col-3">Atendido Por:</td>
                                                            <td class="col align-middle">
                                                                @if ($viability->Reclaims->last()->Production)
                                                                    <p class="my-1 py-0">
                                                                        {{ $viability->Reclaims->last()->Production->User->name }}
                                                                    </p>
                                                                    <p class="my-1 py-0 text-primary">
                                                                        {{ $viability->Reclaims->last()->Production->User->email }}
                                                                    </p>
                                                                @endif
                                                            </td>
                                                        </tr>
                                                    @endif




                                                </tbody>
                                            </table>
                                        </div>
                                    </div>

                                @endif

                            </div>


                        </div>




                        <div class="card">
                            <h5 class="card-header py-1 my-0 edp-bg-sprucegreen-70 text-edp-verde">
                                RESPONDER ATIVIDADE
                            </h5>

                            <div class="card-body">
                                <div class="row">
                                    <div class="col-4">
                                        <label for="select1" class="form-label">Selecione uma Decisão:</label>
                                        <select class="form-select border-secondary" wire:model="decision">
                                            <option selected value="">Selecione uma Opção</option>
                                            <option value="APROVADO">Aprovado</option>
                                            <option value="REPROVADO">Reprovado</option>
                                        </select>
                                        @error('decision')
                                            <small class="text-danger">{{ $message }}</small>
                                        @enderror
                                    </div>
                                    @if ($decision)
                                        <div class="col-4">
                                            <label for="select1" class="form-label">Destinação:</label>
                                            <select class="form-select border-secondary" wire:model="destination">
                                                @foreach (SelectOptions::getReturnInterOptionsResponse() as $option)
                                                    @if ($option->type == 'TODOS')
                                                        <option selected value="{{ $option->value }}">
                                                            {{ $option->info }}
                                                        </option>
                                                    @elseif ($option->type == $decision)
                                                        <option value="{{ $option->value }}">
                                                            {{ $option->info }}
                                                        </option>
                                                    @endif
                                                @endforeach
                                            </select>
                                            @error('destination')
                                                <small class="text-danger">{{ $message }}</small>
                                            @enderror
                                        </div>
                                    @endif
                                    @if ($destination == 'DEVOLVER')
                                        <div class="col-4">
                                            <label for="select1" class="form-label">Serviço para Retorno:</label>
                                            <select class="form-select border-secondary" wire:model="service">
                                                <option selected value="">Selecione</option>
                                                @foreach ($serviceList as $theService)
                                                    <option value="{{ $theService->uuid }}">
                                                        {{ $theService->service }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            @error('service')
                                                <small class="text-danger">{{ $message }}</small>
                                            @enderror
                                        </div>
                                    @endif

                                    @if ($service)
                                        <div class="row mt-2">
                                            <div class="col-4">
                                                <label for="select1" class="form-label">Motivo:</label>
                                                <select class="form-select border-secondary" wire:model="category">
                                                    <option value="" selected>
                                                        Selecione uma Opção
                                                    </option>
                                                    @foreach (SelectOptions::getRejectOptions() as $option3)
                                                        <option value="{{ $option3->value }}">
                                                            {{ $option3->info }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                                @error('category')
                                                    <small class="text-danger">{{ $message }}</small>
                                                @enderror
                                            </div>
                                            <div class="col-8">
                                                @if ($production)
                                                    <div class="card">
                                                        <div class="card-header py-1">ULTIMO USUÁRIO A INTERAGIR</div>
                                                        <div class="card-body py-0">
                                                            <table class="table table-sm my-0">
                                                                <thead>
                                                                    <tr>
                                                                        <th class="fw-bold">Serviço:</th>
                                                                        <th class="fw-bold">Usuario:</th>
                                                                        <th class="fw-bold">Ultima Movimentção:</th>
                                                                    </tr>
                                                                </thead>
                                                                <tbody>
                                                                    <tr>
                                                                        <td>{{ $production->service->service }}</td>
                                                                        <td>{{ $production->User->name }}</td>
                                                                        <td>{{ $production->completed_at }}</td>
                                                                    </tr>
                                                                </tbody>
                                                            </table>
                                                        </div>
                                                    </div>
                                                @else
                                                    <div class="card mt-3 border-secondary">
                                                        <div class="card-body">
                                                            <h5 class="text-center fw-bold">NÃO FOI ENCONTRADO UM
                                                                ULTIMO USUÁRIO A INTERAGIR COM ESSA
                                                                ATIVIDADE. SERÁ ENVIADO A PILHA DO SERVIÇO
                                                                SELECIONADO.
                                                            </h5>
                                                        </div>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    @endif

                                    @if ($category || $destination == 'RETORNAR')
                                        <div class="col-12 mt-3">
                                            <label for="select1" class="form-label">Informações Adicionais:</label>
                                            <textarea class="form-control border-secondary" wire:model.defer="responser" rows="3"></textarea>
                                            @error('responser')
                                                <small class="text-danger">{{ $message }}</small>
                                            @enderror
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>

                    @endif
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"
                        wire:click="clean">Cancelar</button>
                    <button type="button" class="btn btn-primary" wire:click="toResponser">Enviar</button>
                </div>
            </div>

        </div>
    </div>
</div>
