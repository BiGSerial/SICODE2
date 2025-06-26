<div>
    <div wire:ignore.self class="modal fade" id="controlModProtestModal" tabindex="-1"
        aria-labelledby="modalEntityProtocolLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable modal-xl bg-gray">
            <div class="modal-content">
                <div class="modal-header edp-bg-sprucegreen-70 text-edp-verde">
                    <h5 class="modal-title" id="modalEntityProtocolLabel">CONTROLE DO DESDOBRAMENTO PARA:
                        <strong
                            class="text-white">{{ $modProtest?->protest?->nota }}#{{ $modProtest?->med_id }}</strong>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <div class="border rounded p-3 h-100 border-secondary">
                                <h6 class="text-muted mb-2 text-primary">INFORMAÇÕES BÁSICAS</h6>
                                <p class="mb-1"><strong>Nota:</strong> {{ $modProtest?->protest?->nota }}</p>
                                <p class="mb-1"><strong>Municipio:</strong> {{ $modProtest?->protest?->cidade }}</p>
                                <p class="mb-1"><strong>Grupo:</strong>
                                    {{ $modProtest?->protest?->txtGrpCodificacao }}</p>
                                <p class="mb-1"><strong>Causa:</strong> {{ $modProtest?->protest?->descCausa }}</p>
                                <p class="mb-1"><strong>SubCausa:</strong> {{ $modProtest?->protest?->descSubCausa }}
                                </p>
                            </div>
                        </div>
                        <div class="col-md-8">
                            <div class="border rounded p-3 h-100 border-secondary">
                                <h6 class="text-muted mb-2 text-primary">INFORMAÇÕES NOTAS ASSOCIADAS</h6>
                                <p class="mb-1"><strong>Nota:</strong>
                                    {{ $modProtest?->protest?->Notes->isNotEmpty() ? $modProtest?->protest?->Notes[$notePage]?->note : '--' }}
                                </p>
                                <p class="mb-1"><strong>Rubrica:</strong>
                                    {{ $modProtest?->protest?->Notes->isNotEmpty() ? $modProtest?->protest?->Notes[$notePage]?->rubrica : '--' }}
                                </p>
                                <p class="mb-1"><strong>Municipio:</strong>
                                    {{ $modProtest?->protest?->Notes->isNotEmpty() ? $modProtest?->protest?->Notes[$notePage]?->lexp : '--' }}
                                </p>

                                <p class="mb-1"><strong>Cliente:</strong>
                                    {{ $modProtest?->protest?->Notes->isNotEmpty() ? $modProtest?->protest?->Notes[$notePage]?->client : '--' }}
                                </p>
                                <p class="mb-1">
                                    @if ($modProtest?->protest?->Notes[$notePage]?->type_note == 2)
                                        <strong>Status:</strong>
                                        {{ $modProtest?->protest?->Notes->isNotEmpty() ? $modProtest?->protest?->Notes[$notePage]?->nstats : '--' }}
                                    @elseif($modProtest?->protest?->Notes[$notePage]?->type_note == 1)
                                        <strong>CentroTrabalho:</strong>
                                        {{ $modProtest?->protest?->Notes->isNotEmpty() ? $modProtest?->protest?->Notes[$notePage]?->centerJob : '--' }}
                                    @endif
                                </p>
                                <p class="my-0 py-0">
                                <div class="d-flex justify-content-end align-items-center my-0 py-0">
                                    <button wire:click="previousNote" class="btn btn-sm btn-outline-secondary me-2"
                                        {{ $notePage <= 0 ? 'disabled' : '' }}>
                                        <i class="fas fa-chevron-left"></i> Anterior
                                    </button>
                                    <button wire:click="nextNote" class="btn btn-sm btn-outline-secondary"
                                        {{ $notePage >= $modProtest?->protest?->Notes?->count() - 1 ? 'disabled' : '' }}>
                                        Próximo <i class="fas fa-chevron-right"></i>
                                    </button>
                                </div>
                                </p>
                            </div>

                        </div>

                    </div>
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <div class="border rounded p-3 h-100 border-secondary">
                                <h6 class="text-muted mb-2 text-primary">SELECIONAR SERVIÇO PARA ATIVIDADE</h6>
                                <div class="form-floating mb-3">
                                    <select class="form-select" id="serviceType" wire:model="selectedService">
                                        <option value="">Selecione um serviço</option>
                                        @forelse ($serviceList as $service)
                                            <option value="{{ $service->uuid }}">{{ $service->service }}</option>
                                        @empty
                                            <option value="">Nenhum serviço disponível</option>
                                        @endforelse
                                    </select>
                                    <label for="serviceType">Tipo de Serviço</label>
                                </div>

                                <div class="form-check form-switch mb-3">
                                    <input class="form-check-input" type="checkbox" id="needsConfirmation"
                                        wire:model="needsConfirmation">
                                    <label class="form-check-label" for="needsConfirmation">Exigir Confirmação</label>
                                </div>

                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="requireTracking"
                                        wire:model="needsEvidence">
                                    <label class="form-check-label" for="requireTracking">Acompanhamento</label>
                                </div>

                            </div>

                        </div>
                        <div class="col-md-8">
                            <div class="border rounded p-3 h-100 border-secondary">
                                <h6 class="text-muted mb-2 text-primary">SELECIONAR SERVIÇO PARA ATIVIDADE</h6>

                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
