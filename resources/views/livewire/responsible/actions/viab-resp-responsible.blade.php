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
                        VIABILIDADE
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
                                <div class="card mb-2">
                                    <h5 class="card-header py-1 my-0 edp-bg-sprucegreen-70 text-edp-verde">INFORMAÇÕES
                                    </h5>
                                    <div class="card-body p-2">
                                        {{-- Grupo 1: Nota e Ordem --}}
                                        <div class="row row-cols-2 row-cols-md-3 g-3 mb-2 border-bottom pb-2">
                                            <div>
                                                <strong>NOTA/OV:</strong><br>
                                                {{ $viability->Note->note }}
                                            </div>
                                            <div>
                                                <strong>ORDEM:</strong><br>
                                                @if ($viability->Note->Orders->isNotEmpty())
                                                    @foreach ($viability->Note->Orders->filter(fn($o) => !str_starts_with($o->statusSist, 'ENT') && !str_starts_with($o->statusSist, 'ENC')) as $order)
                                                        <div>{{ $order->ordem }}</div>
                                                    @endforeach
                                                @else
                                                    <span class="text-muted">—</span>
                                                @endif
                                            </div>
                                            <div>
                                                <strong>DESCRIÇÃO:</strong><br>
                                                <span class="text-uppercase">{{ $viability->Note->material }}</span>
                                            </div>
                                        </div>

                                        {{-- Grupo 2: Cliente e Local --}}
                                        <div class="row row-cols-2 row-cols-md-3 g-3 mb-2 border-bottom pb-2">
                                            <div>
                                                <strong>CLIENTE:</strong><br>
                                                <span class="text-uppercase">{{ $viability->Note->client }}</span>
                                            </div>
                                            <div>
                                                <strong>RUBRICA:</strong><br>
                                                <span class="text-uppercase">{{ $viability->Note->rubrica }}</span>
                                            </div>
                                            <div>
                                                <strong>MUNICÍPIO:</strong><br>
                                                {{ $viability->Note->lexp }}
                                            </div>
                                        </div>

                                        {{-- Grupo 3: Objeto, status, responsável --}}
                                        <div class="row row-cols-2 row-cols-md-3 g-3 mb-2 border-bottom pb-2">

                                            <div>
                                                <strong>STATUS VIAB:</strong><br>
                                                @if ($viability->approved && !$viability->rejected)
                                                    <span class="text-success">APROVADO</span>
                                                @elseif (!$viability->approved && $viability->rejected)
                                                    <span class="text-danger">REJEITADO</span>
                                                @else
                                                    <span class="text-muted">DESCONHECIDO</span>
                                                @endif
                                            </div>
                                            <div>
                                                <strong>STATUS:</strong><br>
                                                <span
                                                    class="badge {{ Viabilitiesstatus::status($viability->status)->colorbg }}">
                                                    {{ Viabilitiesstatus::status($viability->status)->status }}
                                                </span>
                                            </div>

                                        </div>

                                        {{-- Grupo 4: Datas --}}
                                        <div class="row row-cols-2 row-cols-md-3 g-3 mb-2 border-bottom pb-2">
                                            <div>
                                                <strong>RECEBIDO EM:</strong><br>
                                                {{ $viability->sended_at ? Carbon::parse($viability->sended_at)->format('d/m/Y H:i:s') : '—' }}
                                            </div>
                                            <div>
                                                <strong>PRAZO VIABILIDADE:</strong><br>
                                                @if ($dueDate)
                                                    <span
                                                        class="text-danger fw-bold">{{ $dueDate->format('d/m/Y') }}</span>
                                                @else
                                                    <span class="text-muted">—</span>
                                                @endif
                                            </div>
                                            <div>
                                                <strong>PRAZO OBRA:</strong><br>
                                                <span
                                                    class="text-primary fw-bold">{{ Carbon::now()->addDays($days_left)->format('d/m/Y') }}</span>
                                            </div>
                                        </div>

                                        {{-- Grupo 5: Contratação --}}
                                        <div class="row row-cols-2 row-cols-md-3 g-3 border-bottom pb-2">
                                            <div>
                                                <strong>CONTRATADO:</strong><br>
                                                @if ($viability->hired)
                                                    <span class="text-success fw-bold">CONTRATADO</span>
                                                @else
                                                    <span class="text-danger fw-bold">NÃO CONTRATADO</span>
                                                @endif
                                            </div>
                                            <div>
                                                <strong>CONTRATADO EM:</strong><br>
                                                @if ($viability->hired_at)
                                                    {{ date('d/m/Y H:i:s', strtotime($viability->hired_at)) }}
                                                @else
                                                    <span class="text-muted">—</span>
                                                @endif
                                            </div>
                                        </div>

                                        <div class="row row-cols-2 row-cols-md-3 g-3 mb-2">


                                            <div class="col-12">
                                                <strong>RESPONSÁVEL:</strong><br>
                                                @if ($viability->Engineer)
                                                    <span class="text-muted fw-semibold">
                                                        {{ $viability->Engineer->name }}

                                                    </span>
                                                @else
                                                    <span class="text-muted">—</span>
                                                @endif
                                            </div>
                                            <div class="col-12">
                                                <strong>EMAIL:</strong><br>
                                                @if ($viability->Engineer)
                                                    <span class="text-muted fw-semibold">
                                                        {{ $viability->Engineer->email }}
                                                    </span>
                                                @else
                                                    <span class="text-muted">—</span>
                                                @endif
                                            </div>
                                        </div>

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
                            </div>


                        </div>

                        <div class="card mt-2">
                            <h5 class="card-header py-1 my-0 edp-bg-sprucegreen-70 text-edp-verde">ARQUIVOS ANEXADOS
                            </h5>
                            <div class="card-body py-2 px-3">
                                @livewire('components.files.show-files-pool', ['files' => $viability->Note->Files], key('files-pool-' . $viability->id))
                            </div>
                        </div>


                        @if ($viability->status == 4)
                            <div class="card">
                                <h5 class="card-header py-1 my-0 edp-bg-sprucegreen-70 text-edp-verde">
                                    RESPONDER ATIVIDADE
                                </h5>
                                <div class="card-body">
                                    <div class="row mb-3">
                                        <div class="col-3">
                                            <label for="" class="form-label">Decisão</label>
                                            <select class="form-select form-select-sm border border-secondary"
                                                wire:model="decision">
                                                @foreach (SelectOptions::getResponserOptions() as $optRes)
                                                    <option @once selected @endonce value="{{ $optRes->value }}">
                                                        {{ $optRes->info }}</option>
                                                @endforeach
                                            </select>
                                            @error('decision')
                                                <span class="text-danger">{{ $message }}</span>
                                            @enderror
                                        </div>
                                        <div class="col mb-3">
                                            <label for="" class="form-label">Texto
                                                Descritivo</label>
                                            <textarea class="form-control border border-secondary" id="exampleFormControlTextarea1" rows="3"
                                                wire:model.defer="responser"></textarea>
                                            @error('responser')
                                                <span class="text-danger">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>

                                    @if ($decision === 'CONCORDAR')
                                        <div>
                                            <div class="card mt-3">
                                                <h5 class="card-header py-1 my-0 edp-bg-sprucegreen-70 text-edp-verde">
                                                    DEFINIR DESTINAÇÃO
                                                </h5>
                                                <div class="card-body">
                                                    <div class="row mb-3">
                                                        <div class="col-3">
                                                            <div class="mt-3">
                                                                <label for="" class="form-label">Decisão</label>
                                                                <select
                                                                    class="form-select form-select-sm border border-secondary"
                                                                    wire:model="options">
                                                                    @foreach (SelectOptions::getResponserDestiniesOptions() as $optSel)
                                                                        <option value="{{ $optSel->value }}">
                                                                            {{ $optSel->info }}</option>
                                                                    @endforeach
                                                                </select>
                                                                @error('options')
                                                                    <span class="text-danger">{{ $message }}</span>
                                                                @enderror
                                                            </div>
                                                            @if ($options === 'DEVOLVER')
                                                                <div class="mt-3">
                                                                    <label for="" class="form-label">Motivo
                                                                        Devolução</label>
                                                                    <select
                                                                        class="form-select form-select-sm border border-secondary"
                                                                        wire:model.defer="category">
                                                                        <option value="">
                                                                            Selecione o motivo</option>
                                                                        @foreach (SelectOptions::getRejectOptions() as $optSel)
                                                                            <option value="{{ $optSel->value }}">
                                                                                {{ $optSel->info }}</option>
                                                                        @endforeach
                                                                    </select>
                                                                    @error('category')
                                                                        <span
                                                                            class="text-danger">{{ $message }}</span>
                                                                    @enderror
                                                                </div>
                                                                <div class="mt-3">
                                                                    <label for="" class="form-label">Serviço a
                                                                        Devolver</label>
                                                                    <select
                                                                        class="form-select form-select-sm border border-secondary"
                                                                        wire:model="service">
                                                                        @foreach ($serviceList as $serv)
                                                                            @once <option value="">Selecione
                                                                                Serviço</option> @endonce

                                                                            <option value="{{ $serv->uuid }}">
                                                                                {{ $serv->service }}</option>
                                                                        @endforeach
                                                                    </select>
                                                                    @error('service')
                                                                        <span
                                                                            class="text-danger">{{ $message }}</span>
                                                                    @enderror
                                                                </div>
                                                            @endif
                                                        </div>

                                                        @if ($production)
                                                            <div class="col-9">
                                                                <dic class="card">
                                                                    <div class="card-header">
                                                                        <h5 class="my-0 py-0">RETORNAR OBRA PARA
                                                                            PROJETOS</h5>
                                                                    </div>
                                                                    <table
                                                                        class="table table-sm table-condensed table-striped">
                                                                        <thead>
                                                                            <tr>
                                                                                <th class="fw-bold text-center">SERVIÇO
                                                                                </th>
                                                                                <th class="fw-bold text-center">
                                                                                    RESPONSÁVEL</th>
                                                                                <th class="fw-bold text-center">DATA
                                                                                    ULTIMA
                                                                                    INTERAÇÃO</th>
                                                                            </tr>
                                                                        </thead>
                                                                        <tbody>
                                                                            <tr>
                                                                                <td class="text-center align-middle">
                                                                                    {{ $production->Service->service }}
                                                                                </td>
                                                                                <td class="text-center align-middle">
                                                                                    {{ $production->User->name }}</td>
                                                                                <td class="text-center align-middle">
                                                                                    {{ Carbon::parse($production->completed_at)->format('d/m/Y H:i:s') }}
                                                                                </td>
                                                                            </tr>
                                                                        </tbody>
                                                                    </table>
                                                                </dic>
                                                            </div>
                                                        @endif

                                                        @if ($show)
                                                            <div class="col-9">
                                                                <div class="card card-body">
                                                                    {!! $text !!}
                                                                </div>
                                                            </div>
                                                        @endif

                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endif

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
                </div>
            </div>
        </div>
    </div>
</div>
