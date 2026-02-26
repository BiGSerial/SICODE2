<div>
    <div wire:ignore.self class="modal fade" id="workAcceptanceInfoModal" tabindex="-1"
        aria-labelledby="workAcceptanceInfoModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header edp-bg-sprucegreen-70 text-edp-verde">
                    <h5 class="modal-title" id="workAcceptanceInfoModalLabel">Detalhes do Aceite do Informe</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    @if ($workReport)
                        <div class="row g-3 mb-3">
                            <div class="col-md-4">
                                <small class="text-muted d-block">Numero da Obra</small>
                                <strong>{{ $workReport->Note->note ?? '---' }}</strong>
                            </div>
                            <div class="col-md-4">
                                <small class="text-muted d-block">Empresa</small>
                                <strong>{{ $workReport->Company->name ?? '---' }}</strong>
                            </div>
                            <div class="col-md-4">
                                <small class="text-muted d-block">Usuario SICODE</small>
                                <strong>{{ $workReport->User->name ?? '---' }}</strong>
                                <small class="text-muted d-block">{{ $workReport->User->email ?? '' }}</small>
                            </div>
                        </div>

                        <div class="border rounded p-3 mb-3 bg-light">
                            <p class="mb-2 fw-semibold">Termo de aceite informado pelo parceiro</p>
                            <p class="text-muted mb-2">
                                Ao informar a obra no sistema, o usuário está em acordo que as informações
                                passadas nesse Informe de Conclusão são verdadeiras e não existem divergências.
                                Tendo ciência que existe um prazo para entrega da ADS conforme previsto em
                                contrato, que a data do prazo será considerado o momento do envio deste
                                informe, e não poderá ser contestado posteriormente. Você confirma o
                                entendimento e ciência dessa informação?
                            </p>
                            <p class="text-muted mb-0"><em>
                                "Conforme estabelecido na Especificação Técnica corporativa
                                <strong>ES.DT.PDN.02.01.006 – Construção e Manutenção em Redes Aéreas de
                                    Distribuição – Condições Específicas</strong>, em especial no item
                                <strong>6.3 – Medição dos Serviços e Inventário de Materiais</strong>,
                                a comunicação de conclusão da obra, acompanhada da documentação pertinente,
                                é condição necessária para viabilizar a fiscalização, o aceite dos serviços
                                e o faturamento. Adicionalmente, de acordo com o <strong>item 6.3.4.d</strong>,
                                para a EDP ES, a CONTRATADA dispõe do <strong>prazo de 6 (seis) dias</strong>,
                                contados a partir da conclusão da obra ou serviços, para a entrega do inventário,
                                sendo que, expirado esse prazo, <strong>prevalecerá o inventário elaborado pela
                                    CONTRATANTE</strong>".
                            </em></p>
                        </div>

                        <div class="row g-3 align-items-end">
                            <div class="col-md-4">
                                <small class="text-muted d-block">Nome do aceite</small>
                                <strong>{{ $workReport->acceptance_name ?: '---' }}</strong>
                            </div>
                            <div class="col-md-3">
                                <small class="text-muted d-block">Aceite</small>
                                @if ($workReport->acceptance_accepted)
                                    <span class="badge text-bg-success">ACEITO</span>
                                @else
                                    <span class="badge text-bg-secondary">NAO ACEITO</span>
                                @endif
                            </div>
                            <div class="col-md-3">
                                <small class="text-muted d-block">Quando</small>
                                <strong>{{ $workReport->acceptance_at ? $workReport->acceptance_at->format('d/m/Y H:i') : '---' }}</strong>
                            </div>
                            <div class="col-md-2 text-md-end">
                                @if (!empty($workReport->acceptance_meta))
                                    <button class="btn btn-outline-secondary btn-sm" data-bs-toggle="collapse"
                                        data-bs-target="#acceptanceMetaDetails">
                                        Mais
                                    </button>
                                @endif
                            </div>
                        </div>

                        @if (!empty($workReport->acceptance_meta))
                            <div class="collapse mt-3" id="acceptanceMetaDetails">
                                <div class="border rounded p-2">
                                    <small class="text-muted d-block mb-1">Registros de meta</small>
                                    <pre class="small bg-white border rounded p-2 mb-0" style="max-height:220px; overflow:auto;">{{ json_encode($workReport->acceptance_meta, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                </div>
                            </div>
                        @endif
                    @else
                        <p class="text-muted mb-0">Nenhum aceite disponível para exibição.</p>
                    @endif
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                </div>
            </div>
        </div>
    </div>
</div>
