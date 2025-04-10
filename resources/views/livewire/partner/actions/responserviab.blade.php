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
        <div class="modal-dialog modal-xl">
            <div class="modal-content edp-bg-stategrey-50">
                <div class="modal-header edp-bg-sprucegreen-70 text-edp-verde">
                    <h4 class="modal-title fw-bold">VIABILIDADE</h4>
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
                                    $status = ['color' => 'text-bg-danger', 'info' => 'VENCIDO'];
                                } elseif ($daysDifference < 3) {
                                    $status = ['color' => 'text-bg-warning', 'info' => 'VENCENDO'];
                                } else {
                                    $status = ['color' => 'text-bg-success', 'info' => 'NO PRAZO'];
                                }
                            }

                            $color = 'grey';
                            $days_left = (new DaysLeft($viability->Note))->getDaysLeft();
                            $count = 0;

                            if ($viability->approved) {
                                $count++;
                                $color = 'green';
                            } elseif ($viability->rejected) {
                                $count++;
                                $color = 'red';
                            }

                            if (($viability->rejected || $viability->approved) && !$viability->completed) {
                                $status = ['color' => 'text-bg-primary', 'info' => 'EM AVALIAÇÃO'];
                            }
                        @endphp

                        <div class="row g-3">

                            {{-- Bloco: Informações Gerais --}}
                            <div class="col-md-6">
                                <div class="card h-100">
                                    <div class="card-header edp-bg-sprucegreen-70 text-edp-verde fw-bold py-2">
                                        INFORMAÇÕES GERAIS</div>
                                    <div class="card-body">
                                        <dl class="row">
                                            <dt
                                                class="col-sm-5 edp-bg-sprucegreen-100 text-edp-verde py-1 border-bottom">
                                                Nota/OV:</dt>
                                            <dd class="col-sm-7 fw-bold">{{ $viability->Note->note }}</dd>

                                            <dt
                                                class="col-sm-5 edp-bg-sprucegreen-100 text-edp-verde py-1 border-bottom">
                                                Ordens:
                                            </dt>
                                            <dd class="col-sm-7 fw-bold">
                                                @foreach ($viability->Orders as $order)
                                                    <p class="py-0 my-0">{{ $order->ordem }}</p>
                                                @endforeach
                                            </dd>

                                            <dt
                                                class="col-sm-5 edp-bg-sprucegreen-100 text-edp-verde py-1 border-bottom">
                                                Cliente:
                                            </dt>
                                            <dd class="col-sm-7 text-uppercase">{{ $viability->Note->client }}</dd>

                                            <dt
                                                class="col-sm-5 edp-bg-sprucegreen-100 text-edp-verde py-1 border-bottom">
                                                Descrição:
                                            </dt>
                                            <dd class="col-sm-7 text-uppercase">{{ $viability->Note->material }}</dd>

                                            <dt
                                                class="col-sm-5 edp-bg-sprucegreen-100 text-edp-verde py-1 border-bottom">
                                                Rubrica:
                                            </dt>
                                            <dd class="col-sm-7 text-uppercase">{{ $viability->Note->rubrica }}</dd>

                                            <dt
                                                class="col-sm-5 edp-bg-sprucegreen-100 text-edp-verde py-1 border-bottom">
                                                Município:
                                            </dt>
                                            <dd class="col-sm-7">{{ $viability->Note->lexp }}</dd>

                                            <dt
                                                class="col-sm-5 edp-bg-sprucegreen-100 text-edp-verde py-1 border-bottom">
                                                Status
                                                Viabilidade:</dt>
                                            <dd class="col-sm-7">
                                                @if ($viability->approved && !$viability->rejected)
                                                    <span class="text-success">APROVADO</span>
                                                @elseif (!$viability->approved && $viability->rejected)
                                                    <span class="text-danger">REJEITADO</span>
                                                @else
                                                    <span class="text-muted">DESCONHECIDO</span>
                                                @endif
                                            </dd>
                                        </dl>
                                    </div>
                                </div>
                            </div>

                            {{-- Bloco: Status e Datas --}}
                            <div class="col-md-6">
                                <div class="card h-100">
                                    <div class="card-header edp-bg-sprucegreen-70 text-edp-verde fw-bold py-2">STATUS E
                                        DATAS</div>
                                    <div class="card-body p-3">
                                        <dl class="row">
                                            <dl class="row">
                                                <dt
                                                    class="col-sm-5 edp-bg-sprucegreen-100 text-edp-verde py-1 border-bottom">
                                                    Viabilizado Em:</dt>
                                                <dd class="col-sm-7 fw-bold">
                                                    {{ $viability->returned_at?->format('d/m/Y H:i:s') }}</dd>

                                                <dt
                                                    class="col-sm-5 edp-bg-sprucegreen-100 text-edp-verde py-1 border-bottom">
                                                    Prazo Viabilidade:</dt>
                                                </dt>
                                                <dd class="col-sm-7 fw-bold text-danger">

                                                    {{ $dueDate ? $dueDate->format('d/m/Y') : '---' }}
                                                </dd>

                                                <dt
                                                    class="col-sm-5 edp-bg-sprucegreen-100 text-edp-verde py-1 border-bottom">
                                                    Prazo da Obra:
                                                </dt>
                                                <dd class="col-sm-7 text-uppercase fw-bold text-primary">

                                                    {{ (new DaysLeft($viability->Note))->getLastDate() }}</dd>

                                                <dt
                                                    class="col-sm-5 edp-bg-sprucegreen-100 text-edp-verde py-1 border-bottom">
                                                    Satus:
                                                </dt>
                                                <dd class="col-sm-7 text-uppercase"> <span
                                                        class="badge {{ Viabilitiesstatus::status($viability->status)->colorbg }}">
                                                        {{ Viabilitiesstatus::status($viability->status)->status }}
                                                    </span>
                                                </dd>

                                                <dt
                                                    class="col-sm-5 edp-bg-sprucegreen-100 text-edp-verde py-1 border-bottom">
                                                    Contratação:
                                                </dt>
                                                <dd class="col-sm-7 text-uppercase">

                                                    @if ($viability->hired)
                                                        <span class="text-success">CONTRATADO</span>
                                                    @else
                                                        <span class="text-danger">NÃO CONTRATADO</span>
                                                    @endif

                                                </dd>
                                                <dt
                                                    class="col-sm-5 edp-bg-sprucegreen-100 text-edp-verde py-1 border-bottom">
                                                    Contratado Em:
                                                </dt>
                                                <dd class="col-sm-7 text-uppercase fw-bold">

                                                    {{ $viability->hired_at ? $viability->hired_at->format('d/m/Y H:i:s') : '---' }}

                                                </dd>

                                                <dt
                                                    class="col-sm-5 edp-bg-sprucegreen-100 text-edp-verde py-1 border-bottom">
                                                    Programador Responsável:
                                                </dt>
                                                <dd class="col-sm-7">
                                                    @if ($viability->Engineer)
                                                        <span
                                                            class="fw-bold text-primary">{{ $viability->Engineer->name }}</span>
                                                        ({{ $viability->Engineer->email }})
                                                    @endif
                                                </dd>


                                            </dl>

                                        </dl>
                                    </div>
                                </div>
                            </div>





                            {{-- Bloco: Formulário de Retorno --}}
                            @if ($viability->Form)
                                <div class="col-md-6">
                                    <div class="card h-100">
                                        <div class="card-header edp-bg-sprucegreen-70 text-edp-verde fw-bold py-2">
                                            RETORNO VIABILIDADE</div>
                                        <div class="card-body">
                                            <dl class="row my-0">
                                                <dt
                                                    class="col-sm-5 edp-bg-sprucegreen-100 text-edp-verde py-1 border-bottom">
                                                    Motivo:</dt>
                                                <dd class="col-sm-7 edp-bg-gray py-1 fw-bold">
                                                    {{ $viability->Form->reason }}
                                                </dd>

                                                <dt
                                                    class="col-sm-5 edp-bg-sprucegreen-100 text-edp-verde py-1 border-bottom">
                                                    Impacto:</dt>
                                                <dd class="col-sm-7 edp-bg-gray py-1">
                                                    {{ $viability->Form->changes * 10 }}%
                                                </dd>

                                                <dt
                                                    class="col-sm-5 edp-bg-sprucegreen-100 text-edp-verde py-1 border-bottom">
                                                    Responsável:</dt>
                                                <dd class="col-sm-7 edp-bg-gray py-1 text-uppercase">
                                                    {{ $viability->Form->responsible }}
                                                </dd>

                                                <dt
                                                    class="col-sm-5 edp-bg-sprucegreen-100 text-edp-verde py-1 border-bottom">
                                                    Descrição:</dt>
                                                <dd class="col-sm-7 edp-bg-gray py-1">
                                                    {{ $viability->Form->description }}
                                                </dd>
                                            </dl>
                                        </div>
                                    </div>
                                </div>
                            @endif

                            {{-- Bloco: Comentários --}}
                            @if ($viability->Comments->count())
                                <div class="col-6">
                                    <div class="card">
                                        <div class="card-header edp-bg-sprucegreen-70 text-edp-verde fw-bold py-2">
                                            COMENTÁRIOS</div>
                                        <div class="card-body">
                                            <ul class="list-group">
                                                @foreach ($viability->Comments as $index => $comment)
                                                    <li
                                                        class="list-group-item d-flex justify-content-between align-items-start shadow mb-2">
                                                        <div class="ms-2 me-auto">
                                                            <div class="fw-bold mb-2 border-bottom border-success">
                                                                #{{ ++$index }} {{ $comment->User->name }}</div>
                                                            {!! $comment->message !!}
                                                        </div>
                                                        <span
                                                            class="badge bg-light text-dark">{{ date('d/m/Y H:i', strToTime($comment->created_at)) }}</span>
                                                    </li>
                                                @endforeach
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            @endif


                            <div class="col-12">
                                <div class="card">
                                    <div class="card-header edp-bg-sprucegreen-70 text-edp-verde fw-bold py-2">
                                        ARQUIVOS ANEXADOS</div>
                                    <div class="card-body py-2 px-3">
                                        @livewire('components.files.show-files-pool', ['files' => $viability->Note->Files], key('filesView-' . $viability->id))
                                    </div>
                                </div>
                            </div>

                            @if ($viability->tacit && $viability->files->isEmpty())
                                <div class="mb-3">
                                    @livewire('files.manager.create-viab-files', ['viability' => $viability, 'service' => 'VIABILIDADE'], key('files_forms'))
                                </div>
                            @endif

                            {{-- Bloco: Responder Atividade --}}
                            @if ($viability->treplica && $viability->status == 5)
                                <div class="col-12">
                                    <div class="card">
                                        <div class="card-header edp-bg-sprucegreen-70 text-edp-verde fw-bold py-2">
                                            RESPONDER ATIVIDADE</div>
                                        <div class="card-body">
                                            <div class="row g-3">
                                                <div class="col-md-4">
                                                    <label class="form-label">Decisão</label>
                                                    <select class="form-select form-select-sm border border-secondary"
                                                        wire:model.defer="decision">
                                                        @foreach (SelectOptions::getResponserOptions() as $options)
                                                            <option @once selected @endonce
                                                                value="{{ $options->value }}">{{ $options->info }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                <div class="col-md-8">
                                                    <label class="form-label">Texto Descritivo</label>
                                                    <textarea class="form-control border border-secondary" rows="3" wire:model.defer="responser"></textarea>
                                                </div>
                                                <div class="d-flex justify-content-end mt-3">
                                                    <button class="btn btn-sm btn-danger"
                                                        wire:click="toResponser()">ENVIAR</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endif

                        </div> {{-- row --}}

                    @endif

                </div>
                @if ($viability?->tacit && $viability?->files->isEmpty())
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                        <button type="button" class="btn btn-primary" data-bs-dismiss="modal"
                            wire:click="$emitTo('files.manager.create-viab-files', 'saveFiles')">Salvar</button>
                    </div>
                @endif
            </div>
        </div>
    </div>

</div>
