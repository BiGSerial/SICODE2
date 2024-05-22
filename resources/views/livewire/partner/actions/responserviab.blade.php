@php
    use App\Custom\Viabilitiesstatus;
    use App\Custom\Notestatus;
    use App\Helpers\SelectOptions;
    use Carbon\Carbon;
@endphp
<div>
    <x-show-loading />
    <div wire:ignore.self class="modal fade" id="modal_resp_viability" tabindex="-1" aria-labelledby="exampleModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content edp-bg-stategrey-50">
                <div class="modal-header edp-bg-sprucegreen-70 text-edp-verde">
                    <h4 class="my-auto fw-bold">
                        VIABILIDADE
                    </h4>
                </div>
                <div class="modal-body">
                    @if ($this->note)
                        @php
                            $status = null;

                            $dueDate = $note->Viabilities->count()
                                ? Carbon::parse($note->Viabilities->last()->sended_at)->addDays(7)
                                : null;
                            $today = Carbon::now();
                            $daysDifference = 0;

                            if ($dueDate) {
                                $daysDifference = $dueDate ? $today->diffInDays($dueDate) : null;

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
                            $days_left = 0;

                            // Dias Restantes
                            if ($note->type_note == 1) {
                                if ($note->mesalization && $note->mesalization != 'erro') {
                                    preg_match('/\d+\/\d+/', $note->mesalization, $matches);

                                    if (!empty($matches)) {
                                        [$mes, $ano] = explode('/', $matches[0]);

                                        if ($mes >= 1) {
                                            $data = "{$ano}-{$mes}-28 23:59:59";

                                            $hoje = Carbon::now();

                                            $dataCarbon = Carbon::createFromFormat('Y-m-d H:i:s', $data);

                                            $days_left = $hoje->diffInDays($dataCarbon, false);
                                        } else {
                                            $data = "{$ano}-12-28 23:59:59";

                                            $hoje = Carbon::now();

                                            $dataCarbon = Carbon::createFromFormat('Y-m-d H:i:s', $data);

                                            $days_left = $hoje->diffInDays($dataCarbon, false);
                                        }
                                    }
                                }
                            } elseif ($note->type_note == 2) {
                                $days_left = $note->days_left;
                            }

                            if ($note->Viabilities->count()) {
                                $count = 0;

                                foreach ($note->Viabilities as $order) {
                                    if ($order->approved) {
                                        $count++;

                                        $block = [
                                            'color' => 'green',
                                            'command' => true,
                                        ];

                                        $color = 'green';
                                    } elseif ($order->rejected) {
                                        $count++;

                                        $block = [
                                            'color' => 'danger',
                                            'command' => true,
                                        ];

                                        $color = 'red';
                                    }

                                    if (($order->rejected || $order->approved) && !$order->completed) {
                                        $status = [
                                            'color' => 'text-bg-primary',
                                            'info' => 'EM AVALIAÇÂO',
                                        ];
                                    }
                                }

                                if ($count == $note->Viabilities->count()) {
                                    $block = array_merge($block, ['command' => false]);
                                }
                            }

                        @endphp
                        <div class="card">
                            <h5 class="card-header py-1 my-0 edp-bg-sprucegreen-70 text-edp-verde">INFORMAÇÕES</h5>
                            <div class="table-responsive">
                                <table class="table table-sm table-condensed table-striped-columns">
                                    <tbody>
                                        <tr>
                                            <td class="fw-bold col-2 align-middle">NOTA/OV:</td>
                                            <td class="align-middle fw-bold">{{ $note->note }}</td>
                                        </tr>
                                        <tr>
                                            <td class="fw-bold col-2 align-middle">ORDEM:</td>
                                            <td class="align-middle">
                                                @if ($note->Viabilities->count())
                                                    @foreach ($note->Viabilities as $viab)
                                                        <p class="my-1 py-0">{{ $viab->Order->ordem }}</p>
                                                    @endforeach
                                                @endif
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="fw-bold col-2 align-middle">RUBRICA:</td>
                                            <td class="align-middle text-uppercase">{{ $note->rubrica }}</td>
                                        </tr>
                                        <tr>
                                            <td class="fw-bold col-2 align-middle">MUNICIPIO:</td>
                                            <td class="align-middle">{{ $note->lexp }}</td>
                                        </tr>
                                        <tr>
                                            <td class="fw-bold col-2 align-middle">CONTRATADO:</td>
                                            <td class="align-middle">
                                                @if ($note->Viabilities->count() && $note->Viabilities->first()->hired)
                                                    <span class="text-success fw-bold fs-6">CONTRATADO</span>
                                                @else
                                                    <span class="text-danger fw-bold fs-6">NÃO CONTRATADO</span>
                                                @endif
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="fw-bold col-2 align-middle">CONTRATADO EM:</td>
                                            <td class="align-middle">
                                                @if ($note->Viabilities->count() && $note->Viabilities->first()->hired)
                                                    <span
                                                        class="fw-bold">{{ date('d/m/Y H:i:s', strToTime($note->Viabilities->first()->hired_at)) }}</span>
                                                @else
                                                    <span class="fw-bold"> --- </span>
                                                @endif
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="fw-bold col-2 align-middle">STATUS VIAB:</td>
                                            <td class="align-middle">
                                                @if ($note->Viabilities->count() && $note->Viabilities->last()->approved && !$note->Viabilities->last()->rejected)
                                                    <span class="text-success fs-6"> APROVADO </span>
                                                @elseif (!$note->Viabilities->last()->approved && $note->Viabilities->last()->rejected)
                                                    <span class="text-danger fs-6"> REJEITADO </span>
                                                @else
                                                    <span class="text-secondary fs-6"> DESCONHECIDO </span>
                                                @endif
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="fw-bold col-2 align-middle">RECEBIDO EM:</td>
                                            <td class="align-middle">
                                                @if ($note->Viabilities->count() && $note->Viabilities->last()->sended_at)
                                                    <span
                                                        class="fw-bold">{{ date('d/m/Y H:i:s', strToTime($note->Viabilities->last()->sended_at)) }}</span>
                                                @else
                                                    <span class="fw-bold"> --- </span>
                                                @endif
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="fw-bold col-2 align-middle">PRAZO VIABILIDADE:</td>
                                            <td class="align-middle">
                                                @if ($note->Viabilities->count() && $note->Viabilities->last()->sended_at)
                                                    <span
                                                        class="fw-bold text-danger">{{ Carbon::parse($note->Viabilities->last()->sended_at)->addDays(7)->format('d/m/Y') }}</span>
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
                                                    class="badge {{ Viabilitiesstatus::status($note->Viabilities->last()->status)->colorbg }} word-wrap">{{ Viabilitiesstatus::status($note->Viabilities->last()->status)->status }}</span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="fw-bold col-2 align-middle">RESPONSÁVEL:</td>
                                            <td class="align-middle">
                                                @if ($note->Viabilities->last()->Engineer)
                                                    <span
                                                        class="fw-bold text-secondary">{{ $note->Viabilities->last()->Engineer->name }}
                                                        ( {{ $note->Viabilities->last()->Engineer->email }} )</span>
                                                @endif

                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        @if ($note->Viabilities->count() && $note->Viabilities->last()->Form)
                            @php
                                $form = $note->Viabilities->last()->Form;
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
                                                <td class="align-middle text-uppercase">{{ $form->responsible }}</td>
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

                        @if ($note->Viabilities->count() && $note->Viabilities->last()->Comments->count())

                            <div class="card">
                                <h5 class="card-header py-1 my-0 edp-bg-sprucegreen-70 text-edp-verde">COMENTÁRIOS</h5>
                                <div class="table-responsive">
                                    <table class="table table-sm table-condensed table-striped-columns">
                                        <tbody>

                                            @foreach ($note->Viabilities->last()->Comments as $comment)
                                                <tr>
                                                    <td class="col-2">
                                                        {{ date('d/m/Y H:i', strToTime($comment->created_at)) }}</td>
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

                            @if ($note->Viabilities->count() && !$note->Viabilities->last()->treplica && $note->Viabilities->last()->status == 5)
                                <div class="card">
                                    <h5 class="card-header py-1 my-0 edp-bg-sprucegreen-70 text-edp-verde">
                                        RESPONDER ATIVIDADE
                                    </h5>
                                    <div class="card-body">
                                        <div class="row mb-3">
                                            <div class="col-3">
                                                <label for="" class="form-label">Decisão</label>
                                                <select class="form-select form-select-sm border border-secondary"
                                                    wire:model.defer="decision">
                                                    @foreach (SelectOptions::getResponserOptions() as $options)
                                                        <option @once selected @endonce value="{{ $options->value }}">
                                                            {{ $options->info }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="col mb-3">
                                                <label for="" class="form-label">Texto
                                                    Descritivo</label>
                                                <textarea class="form-control border border-secondary" id="exampleFormControlTextarea1" rows="3"
                                                    wire:model.defer="responser"></textarea>
                                            </div>
                                        </div>
                                        <div class="clear-fix">
                                            <div class="d-flex justify-content-end">
                                                <button class="btn btn-sm btn-danger"
                                                    wire:click="toResponser()">ENVIAR</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endif

                        @endif

                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
