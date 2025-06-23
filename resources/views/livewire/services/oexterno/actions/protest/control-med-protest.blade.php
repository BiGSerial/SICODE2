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
                    <div class="row">
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
                                <h6 class="text-muted mb-2 text-primary">INFORMAÇÕES BÁSICAS</h6>
                                <p class="mb-1"><strong>Nota:</strong> {{ $modProtest?->protest?->nota }}</p>
                                <p class="mb-1"><strong>Municipio:</strong> {{ $modProtest?->protest?->cidade }}
                                </p>
                                <p class="mb-1"><strong>Grupo:</strong>
                                    {{ $modProtest?->protest?->txtGrpCodificacao }}</p>
                                <p class="mb-1"><strong>Causa:</strong> {{ $modProtest?->protest?->descCausa }}
                                </p>
                                <p class="mb-1"><strong>SubCausa:</strong>
                                    {{ $modProtest?->protest?->descSubCausa }}
                                </p>
                            </div>

                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>
