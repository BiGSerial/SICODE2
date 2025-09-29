@php
    use App\Helpers\DaysLeft;
@endphp

<div class="modal fade" id="modalApproveReclaim" tabindex="-1" aria-labelledby="modalEntityProtocolLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content edp-background text-light rounded-4 shadow-lg"
            style="font-family: 'Nunito', sans-serif;">

            <!-- HEADER -->
            <div class="modal-header  bg-edp-sprucegreen-100 text-edp-verde">
                <h5 class="modal-title d-flex align-items-center gap-2">
                    <i class="bi bi-file-earmark-text-fill text-edp-verde"></i>
                    Resolução de Retorno Interno
                    <span
                        class="badge bg-success bg-opacity-25 border border-edp">{{ $item?->externals->first()->entity?->nick }}</span>

                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                    aria-label="Close"></button>
            </div>

            <!-- BODY -->
            <div class="modal-body">
                <div class="row g-4">

                    <!-- COLUNA ESQUERDA -->
                    <div class="col-lg-6 d-flex flex-column gap-4">

                        <!-- DADOS DA NOTA -->
                        <div class="card bg-edp-marineblue-100 border border-edp">
                            <div class="card-header border-bottom border-edp">
                                <h6 class="mb-0">Dados da Nota <span
                                        class="badge bg-secondary">#{{ $item?->note?->note }}</span>
                                </h6>
                            </div>
                            <div class="card-body small">
                                <div class="row g-3">
                                    <div class="col-6">
                                        <div class="text-muted">Cliente</div>
                                        <div class="fw-bold">{{ $item?->note?->client }}</div>
                                    </div>
                                    <div class="col-6">
                                        <div class="text-muted">rubrica</div>
                                        <span class="badge bg-warning text-dark">{{ $item?->note?->rubrica }}</span>
                                    </div>
                                    <div class="col-6">
                                        <div class="text-muted">Prazo</div>
                                        @php
                                            if ($item) {
                                                $daysLeft = new DaysLeft($item?->note);
                                            } else {
                                                $daysLeft = null;
                                            }
                                        @endphp
                                        <div class="fw-bold">{{ $daysLeft?->getLastDate() }}</div>
                                    </div>
                                    <div class="col-6">
                                        <div class="text-muted">Prioridade</div>
                                        <span class="badge bg-danger">Alta</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- RECLAMAÇÃO -->
                        <div class="card bg-edp-marineblue-100 border border-edp">
                            <div class="card-header border-bottom border-edp">
                                <h6 class="mb-0">Informações da Reclamação</h6>
                            </div>
                            <div class="card-body small">
                                <div class="row g-3">
                                    <div class="col-6">
                                        <div class="text-muted">Quem abriu</div>
                                        <div class="fw-semibold">Fulano de Tal (Agência Centro)</div>
                                    </div>
                                    <div class="col-6">
                                        <div class="text-muted">Quando</div>
                                        <div class="fw-semibold">24/09/2025 10:22</div>
                                    </div>
                                    <div class="col-6">
                                        <div class="text-muted">Motivo</div>
                                        <div class="fw-semibold">Prazo ultrapassado</div>
                                    </div>
                                    <div class="col-6">
                                        <div class="text-muted">Categoria</div>
                                        <div class="fw-semibold">Atendimento / SLA</div>
                                    </div>
                                    <div class="col-12">
                                        <div class="text-muted">Descrição</div>
                                        <div class="p-3 bg-edp-sprucegreen-70 border border-edp text-light rounded">
                                            O cliente relata atraso superior a 10 dias em relação ao prazo comunicado.
                                            Solicita retorno com nova previsão e justificativa.
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>

                    <!-- COLUNA DIREITA -->
                    <div class="col-lg-6 d-flex flex-column gap-4">

                        <!-- ARQUIVOS -->
                        <div class="card bg-edp-marineblue-100 border border-edp">
                            <div class="card-header border-bottom border-edp">
                                <h6 class="mb-0">Arquivos da Nota</h6>
                            </div>
                            <div class="card-body small">
                                <strong class="d-block text-edp-verde mb-2">Projeto / Viabilidade</strong>
                                <ul class="list-group list-group-flush">
                                    <li class="list-group-item bg-dark border-secondary d-flex justify-content-between">
                                        <span><i class="bi bi-file-earmark-pdf me-2"></i>planta_baixa.pdf</span>
                                        <button class="btn btn-sm btn-outline-light">Abrir</button>
                                    </li>
                                    <li class="list-group-item bg-dark border-secondary d-flex justify-content-between">
                                        <span><i
                                                class="bi bi-file-earmark-word me-2"></i>memorial_descritivo.docx</span>
                                        <button class="btn btn-sm btn-outline-light">Baixar</button>
                                    </li>
                                    <li class="list-group-item bg-dark border-secondary d-flex justify-content-between">
                                        <span><i class="bi bi-image me-2"></i>croqui.jpg</span>
                                        <button class="btn btn-sm btn-outline-light">Visualizar</button>
                                    </li>
                                </ul>
                            </div>
                        </div>

                        <!-- RESPOSTA -->
                        <div class="card bg-edp-marineblue-100 border border-edp">
                            <div class="card-header border-bottom border-edp">
                                <h6 class="mb-0">Resposta ao Protocolo</h6>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label for="mockProtocol" class="form-label text-muted">Protocolo (opcional)</label>
                                    <input type="text" class="form-control bg-dark text-light border-secondary"
                                        id="mockProtocol" placeholder="Ex.: 2025-000123-ABC">
                                </div>
                                <div class="mb-3">
                                    <label for="mockTitle" class="form-label text-muted">Título / Motivo</label>
                                    <select id="mockTitle" class="form-select bg-dark text-light border-secondary">
                                        <option>Selecione...</option>
                                        <option>Prazo Reajustado</option>
                                        <option>Informação Complementar</option>
                                        <option>Inconsistência Corrigida</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label for="mockObservations" class="form-label text-muted">Descrição</label>
                                    <textarea class="form-control bg-dark text-light border-secondary" id="mockObservations" rows="4"
                                        placeholder="Descreva aqui o parecer, justificativas e encaminhamentos."></textarea>
                                </div>
                                <div class="text-muted mb-2">Anexos (opcional)</div>
                                <div class="border border-secondary border-dashed rounded p-3 text-center text-muted">
                                    Arraste e solte arquivos ou
                                    <button class="btn btn-sm btn-edp ms-2">Selecionar</button>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>

            <!-- FOOTER -->
            <div class="modal-footer border-top border-edp d-flex justify-content-between">
                <div class="text-warning d-flex align-items-center gap-2 small">
                    <i class="bi bi-exclamation-triangle-fill"></i> Revise as informações antes de concluir
                </div>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-outline-light" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-outline-danger">Recusar</button>
                    <button type="button" class="btn btn-edp">Aprovar</button>
                </div>
            </div>

        </div>
    </div>
</div>
