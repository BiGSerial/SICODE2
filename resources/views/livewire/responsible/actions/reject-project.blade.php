@php
    use App\Custom\Viabilitiesstatus;
    use App\Custom\Notestatus;
    use App\Helpers\SelectOptions;
    use Carbon\Carbon;
    use App\Helpers\DaysLeft;
@endphp
<div>
    <x-show-loading />



    <div wire:ignore.self class="modal fade" id="rejectProject" tabindex="-1" aria-labelledby="rejectProjectLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg bg-edp-gray">
            @if ($note)
                <div class="modal-content">
                    <div class="modal-header edp-bg-sprucegreen-70 text-edp-verde">
                        <h5 class="modal-title" id="rejectProjectLabel">REJEITAR PROJETO DE {{ $note->note }}</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                            aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="container-fluid">
                            <div class="row">
                                <div class="col-md-6">
                                    <!-- Informações do Produto/Serviço Sendo Devolvido -->
                                    <div class="card mb-3">
                                        <div class="card-header edp-bg-sprucegreen-70 text-edp-verde">
                                            Informações da Nota
                                        </div>
                                        <div class="card-body">
                                            <p><strong>Número da Nota:</strong> {{ $note->note }}</p>
                                            <p><strong>Rubrica:</strong> {{ $note->rubrica }}</p>
                                            @php
                                                $ordens = $note->orders
                                                    ? implode(', ', $note->orders->pluck('ordem')->toArray())
                                                    : '';
                                            @endphp
                                            <p><strong>Ordens:</strong> {{ $ordens }}</p>
                                            <p><strong>Municipio:</strong> {{ $note->lexp }}</p>
                                        </div>
                                    </div>
                                    @if ($production)
                                        <div class="card mb-3">
                                            <div class="card-header edp-bg-sprucegreen-70 text-edp-verde">
                                                Retorno para {{ $production->service->service }}
                                            </div>
                                            <div class="card-body">
                                                <p><strong>Usuário:</strong> {{ $production->User->name }}</p>
                                                <p><strong>Data:</strong>
                                                    {{ $production->completed_at->format('d/m/Y H:i:s') }}</p>

                                            </div>
                                        </div>
                                    @else
                                        <div class="card mb-3">
                                            <div class="card-body">
                                                <h5 class="text-center">NENHUM USUÁRIO ENCONTRADO</h5>

                                            </div>
                                        </div>
                                    @endif
                                </div>
                                <div class="col-md-6">
                                    <!-- Formulário de Devolução -->
                                    <div class="mb-3">
                                        <label for="tipoServico" class="form-label">Tipo de Rejeição: <span
                                                class="text-danger">*</span></label>
                                        <select class="form-select border border-secondary" id="tipoServico"
                                            wire:model.defer='category'>
                                            <option value="">Selecione...</option>
                                            @foreach (SelectOptions::getRejectOptions() as $reclaimOption)
                                                <option value="{{ $reclaimOption->value }}">{{ $reclaimOption->info }}
                                                </option>
                                            @endforeach


                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label for="categoriaDevolucao" class="form-label">Devolver para: <span
                                                class="text-danger">*</span></label>
                                        <select class="form-select border border-secondary" id="service"
                                            wire:model="service">
                                            <option value="">Selecione...</option>
                                            @foreach ($serviceList as $tService)
                                                <option value="{{ $tService->uuid }}">{{ $tService->service }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label for="motivoDevolucao" class="form-label">Motivo Detalhado: <span
                                                class="text-danger">*</span></label>
                                        <textarea class="form-control border border-secondary" id="motivoDevolucao" rows="5" wire:model.defer="details">
                                            placeholder="Detalhar informação a atividade a ser feita."></textarea>
                                    </div>

                                </div>
                                <div class="mb-3">
                                    @livewire('components.files.show-files-pool', ['files' => $note->files], key('files-pool'))
                                </div>
                                <div class="mb-3">
                                    @livewire('files.manager.create-gen-files', ['note' => $note, 'service' => 'REJEITAR'], key('files-note'))
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        {{ $hasFile }}<button type="button" class="btn btn-danger"
                            wire:click.prevent="preReject">REJEITAR</button>
                    </div>
                </div>
            @endif
        </div>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var rejectModal = document.getElementById('rejectProject');
            rejectModal.addEventListener('hidden.bs.modal', function(event) {

                livewire.emitTo('responsible.actions.reject-project', 'clearAll');
            });
        });
    </script>
</div>
