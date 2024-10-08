@php
    use Carbon\Carbon;
@endphp
<div>
    <x-show-loading />
    <div wire:ignore.self class="modal fade" id="response-viab-modal" tabindex="-1">
        <div class="modal-dialog modal-dialog-scrollable">
            <div class="modal-content edp-bg-gray">
                @if ($viability)
                    <div class="modal-header edp-bg-sprucegreen-70 text-edp-verde">
                        <h5> RESPONDER VIABILIDADE NOTA/OV {{ $viability->Note->note }}</h5>
                    </div>
                    @if ($viability->Form)
                        <div class="modal-body">
                            <div class="card">
                                <h5 class="card-header edp-bg-sprucegreen-70 text-edp-verde">Resultado da Viabilidade
                                </h5>
                                <div class="card-body">
                                    <table class="table table-sm table-condensed table-striped">
                                        <thead>
                                            <tr>
                                                <th class="col-2"></th>
                                                <th class="col"></th>
                                            </tr>
                                        </thead>
                                        <tbody>

                                            <tr>
                                                <td class="text-end align-middle fw-bold">Motivo:</td>
                                                <td class="align-middle text-start fw-bold">
                                                    {{ $viability->Form->reason }}</td>
                                            </tr>
                                            <tr>
                                                <td class="text-end align-middle fw-bold">Descrição:</td>
                                                <td class="align-middle text-start">{{ $viability->Form->description }}
                                                </td>
                                            </tr>
                                            <tr>
                                                <td class="text-end align-middle fw-bold">Impacto:</td>
                                                <td class="align-middle text-start">
                                                    {{ $viability->Form->changes * 10 }}%</td>
                                            </tr>
                                            <tr>
                                                <td class="text-end align-middle fw-bold">Responsável:</td>
                                                <td class="align-middle text-start">{{ $viability->Form->responsible }}
                                                </td>
                                            </tr>
                                            <tr>
                                                <td class="text-end align-middle fw-bold">Status:</td>
                                                <td class="align-middle text-start">
                                                    @if ($viability->Form->rejected && !$viability->Form->approved)
                                                        <span class="badge text-bg-danger">REJEITADO</span>
                                                    @elseif(!$viability->Form->rejected && $viability->Form->approved)
                                                        <span class="badge text-bg-success">APROVADO</span>
                                                    @else
                                                        <span class="badge text-bg-secondary">DESCONHECIDO</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                                <div class="card-footer">
                                    <span class="fw-bold">User Sicode:</span>
                                    {{ $viability->Form->User->name }} -
                                    <p class="text-primary">
                                        {{ Carbon::parse($viability->Form->created_at)->format('d/m/Y H:i:s') }}</p>
                                </div>
                            </div>
                            @if ($viability->Comments->isNotEmpty())
                                @foreach ($viability->Comments as $comment)
                                    <div class="card mt-3">
                                        <div class="card-body">
                                            <table class="table table-sm table-condensed table-striped">
                                                <thead>
                                                    <tr>
                                                        <th class="col-2"></th>
                                                        <th class="col"></th>
                                                    </tr>
                                                </thead>
                                                <tbody>

                                                    <tr>
                                                        <td class="text-end align-middle fw-bold">Comentário:</td>
                                                        <td class="align-middle text-start fw-bold">
                                                            {!! $comment->message !!}</td>
                                                    </tr>

                                                    <tr>
                                                        <td class="text-end align-middle fw-bold">Status:</td>
                                                        <td class="align-middle text-start">
                                                            @if ($comment->dismissed && !$comment->granted)
                                                                <span class="badge text-bg-danger">REJEITADO</span>
                                                            @elseif(!$comment->dismissed && $comment->granted)
                                                                <span class="badge text-bg-success">APROVADO</span>
                                                            @else
                                                                <span
                                                                    class="badge text-bg-secondary">DESCONHECIDO</span>
                                                            @endif
                                                        </td>
                                                    </tr>

                                                </tbody>
                                            </table>
                                        </div>
                                        <div class="card-footer">
                                            <span class="fw-bold">User Sicode:</span>
                                            {{ $comment->User->name }} -
                                            <p class="text-primary">
                                                {{ Carbon::parse($comment->created_at)->format('d/m/Y H:i:s') }}</p>
                                        </div>
                                    </div>
                                @endforeach
                            @endif

                            @if ($viability->status == 5)
                                <div class="card mt-3">
                                    <h5 class="card-header edp-bg-sprucegreen-70 text-edp-verde">Adicionar Resposta

                                    </h5>
                                    <div class="card-body">
                                        <label for="" class="form-label">Escreva Justificativa. <span
                                                class="text-danger">*</span></label>
                                        <textarea class="form-control border-secondary @error('description') is-invalid @enderror" id="" rows="4"
                                            wire:model.defer="description"></textarea>
                                        @error('description')
                                            <div class="invalid-feedback">
                                                {{ $message }}
                                            </div>
                                        @enderror
                                    </div>
                                    <div class="card-footer text-center">
                                        <button class="btn btn-sm btn-danger" wire:click="goDismissed">REJEITAR</button>
                                        <button class="btn btn-sm btn-success" wire:click='goGranted'>ACEITAR</button>
                                    </div>
                                </div>
                            @endif
                        </div>
                    @else
                        <div class="modal-body">
                            <div class="card card-body">
                                <h4 class="text-center">
                                    SEM RESPOSTA VIABILDDADE
                                </h4>
                            </div>
                        </div>
                    @endif
                @endif
            </div>
        </div>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                var modal = document.getElementById('response-viab-modal');
                modal.addEventListener('hidden.bs.modal', function() {
                    Livewire.emit('closeAll');
                });
            });
        </script>
    </div>
