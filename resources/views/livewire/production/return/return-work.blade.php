@php
    use App\Helpers\SelectOptions;
@endphp

<div>
    <x-show-loading />
    <div wire:ignore.self class="modal fade" id="returnWorkform" tabindex="-1" aria-labelledby="returnWorkformLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            @if ($production)
                <div class="modal-content border-0 shadow">
                    <div class="modal-header edp-bg-sprucegreen-70 text-edp-verde border-0">
                        <div>
                            <div class="small text-uppercase fw-semibold opacity-75">Devolução de informe final</div>
                            <h1 class="modal-title fs-5 fw-bold" id="returnWorkformLabel">
                                {{ $production->Note->note }}
                            </h1>
                        </div>
                        <button type="button" class="btn-close btn-close-white" aria-label="Fechar"
                            wire:click.prevent="close()"></button>
                    </div>

                    <form>
                        <div class="modal-body bg-light">
                            <div class="alert alert-warning border-0 d-flex gap-3 align-items-start mb-4">
                                <i class="ri-alert-line fs-4 lh-1"></i>
                                <div>
                                    <div class="fw-semibold mb-1">A produção vinculada ao informe selecionado será descartada.</div>
                                    <div class="small mb-0">
                                        Se houver mais de um informe final para esta obra, devolva somente o escopo que precisa de revisão.
                                    </div>
                                </div>
                            </div>

                            <div class="mb-4">
                                <label class="form-label fw-semibold">
                                    Informe final para devolver <span class="text-danger">*</span>
                                </label>

                                <div class="list-group">
                                    @foreach ($returnableWorkReports as $workReport)
                                        <label class="list-group-item list-group-item-action p-3"
                                            wire:key="return-work-report-{{ $workReport['id'] }}">
                                            <div class="d-flex gap-3 align-items-start">
                                                <input class="form-check-input mt-1" type="checkbox"
                                                    value="{{ $workReport['id'] }}"
                                                    wire:model.defer="selectedWorkReportIds">

                                                <div class="flex-grow-1">
                                                    <div class="d-flex flex-wrap gap-2 align-items-center mb-2">
                                                        <span class="fw-bold">Informe #{{ $workReport['id'] }}</span>
                                                        @foreach ($workReport['scopes'] as $scope)
                                                            <span class="badge {{ $scope['class'] }}">{{ $scope['label'] }}</span>
                                                        @endforeach
                                                    </div>

                                                    <div class="row g-2 small text-muted">
                                                        <div class="col-md-4">
                                                            <span class="d-block text-uppercase fw-semibold">Empreiteira</span>
                                                            <span class="text-body">{{ $workReport['company'] }}</span>
                                                        </div>
                                                        <div class="col-md-3">
                                                            <span class="d-block text-uppercase fw-semibold">Informado em</span>
                                                            <span class="text-body">{{ $workReport['date'] }}</span>
                                                        </div>
                                                        <div class="col-md-5">
                                                            <span class="d-block text-uppercase fw-semibold">Ordens</span>
                                                            <span class="text-body">
                                                                {{ count($workReport['orders']) ? implode(', ', $workReport['orders']) : '---' }}
                                                            </span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </label>
                                    @endforeach
                                </div>

                                @error('selectedWorkReportIds')
                                    <div class="text-danger small mt-2">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="row g-3">
                                <div class="col-md-5">
                                    <label class="form-label fw-semibold">
                                        Motivo <span class="text-danger">*</span>
                                    </label>
                                    <select class="form-select" wire:model.defer="returnWork.category" required>
                                        <option value="" selected>Selecione</option>
                                        @foreach (SelectOptions::getReasonPublication() as $category)
                                            <option value="{{ $category->value }}">{{ $category->reason }}</option>
                                        @endforeach
                                    </select>
                                    @error('returnWork.category')
                                        <div class="text-danger small mt-2">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-7">
                                    <label class="form-label fw-semibold">
                                        Instrução ao parceiro <span class="text-danger">*</span>
                                    </label>
                                    <textarea class="form-control" placeholder="Detalhe o motivo de retorno" rows="4"
                                        wire:model.defer="returnWork.text_obs" required></textarea>
                                    @error('returnWork.text_obs')
                                        <div class="text-danger small mt-2">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="modal-footer bg-white border-0">
                            <button type="button" class="btn btn-outline-secondary" wire:click.prevent="close()">
                                Cancelar
                            </button>
                            <button type="submit" class="btn btn-danger" wire:click.prevent="toSave()">
                                <i class="ri-delete-back-2-line me-1"></i>
                                Devolver informe
                            </button>
                        </div>
                    </form>
                </div>
            @endif
        </div>
    </div>
    <script>
        document.getElementById('returnWorkform').addEventListener('hidden.bs.modal', () => {
            Livewire.emitTo('production.return.return-work', 'close');
        });
    </script>
</div>
