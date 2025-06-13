<div>
    <div class="row">
        <div class="col-12 col-md-10">
            <div class="card mb-0 shadow rounded-bottom-0">
                <div class="card-header bg-primary text-white py-2">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Reclamação #{{ $protest->nota }}</h5>
                        <span class="badge bg-light text-primary">{{ $protest->tipoNota }}</span>
                    </div>
                </div>

                <div class="card-body">
                    <div class="row g-3 mb-3" style="min-height: 200px;">
                        <div class="col-md-4">
                            <div class="border rounded p-3 h-100">
                                <h6 class="text-muted mb-2">Informações Básicas</h6>
                                <p class="mb-1"><strong>Nota:</strong> {{ $protest->nota }}</p>
                                <p class="mb-1"><strong>Municipio:</strong> {{ $protest->cidade }}</p>
                                <p class="mb-1"><strong>Grupo:</strong> {{ $protest->txtGrpCodificacao }}</p>
                                <p class="mb-1"><strong>Causa:</strong> {{ $protest->descCausa }}</p>
                                <p class="mb-1"><strong>SubCausa:</strong> {{ $protest->descSubCausa }}</p>
                            </div>
                        </div>
                        @php
                            if ($protest->dtConclusaoDesej->isPast()) {
                                $status['color'] = 'badge bg-danger';
                                $status['text'] = 'Vencida'; # code...
                            } elseif ($protest->dtConclusaoDesej?->addDays(3) < now()) {
                                $status['color'] = 'badge bg-success';
                                $status['text'] = 'No Prazo';
                            } else {
                                $status['color'] = 'badge bg-warning';
                                $status['text'] = 'Vencendo';
                            }
                        @endphp

                        <div class="col-md-4">
                            <div class="border rounded p-3 h-100">
                                <h6 class="text-muted mb-2">Datas</h6>
                                <p class="mb-1"><strong>Abertura:</strong>
                                    {{ $protest->dtAberturaNota?->format('d/m/Y') }}</p>
                                <p class="mb-1"><strong>Conclusão Prevista:</strong>
                                    {{ $protest->dtConclusaoDesej?->format('d/m/Y') }}</p>
                                <p class="mb-1"><strong>Status:</strong>
                                    <span class="badge {{ $status['color'] }}">{{ $status['text'] }}</span>
                                </p>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="border rounded p-3 h-100">
                                <h6 class="text-muted mb-2">Status</h6>
                                <p class="mb-1">
                                    <strong>Total Medidas:</strong>
                                    <span class="badge bg-secondary">{{ $protest->medProtests?->count() }}</span>
                                </p>
                                <p class="mb-1">
                                    <strong>Última Movimentação:</strong>
                                    <span class="badge bg-info">{{ $protest->medProtests?->count() }}</span>
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-8">
                            <div class="border rounded p-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <h6 class="text-muted mb-2">Obras Associadas</h6>
                                    <button class="btn btn-sm btn-primary" title="Adicionar obra">
                                        <i class="ri-add-box-fill fs-6 align-middle text-center"></i>
                                    </button>
                                </div>
                                <table class="table table-condensed table-stripped table-sm table-hover">
                                    <thead>
                                        <tr>
                                            <th>Nota/OV</th>
                                            <th>Cliente</th>
                                            <th>Rubrica</th>
                                            <th>Municipio</th>
                                            <th>Descrição</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>

                                            <td>OV123456</td>
                                            <td>Maria do Carmo Rabelo Pinto</td>
                                            <td>R001</td>
                                            <td>Vitoria</td>
                                            <td>Descrição da obra A</td>
                                        </tr>
                                        <tr>

                                            <td>OV789012</td>
                                            <td>Joanete Rabelo Pinto</td>
                                            <td>R002</td>
                                            <td>Vitoria</td>
                                            <td>Descrição da obra B</td>
                                        </tr>
                                        <tr>

                                            <td>OV345678</td>
                                            <td>Joanete Rabelo Pinto</td>
                                            <td>R003</td>
                                            <td>Vitoria</td>
                                            <td>Descrição da obra C</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card mb-0 mt-0 shadow-sm border-top-0 rounded-top-0" style="z-index: -1">
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-8">
                            <div class="border rounded p-3">
                                <h6 class="text-muted mb-2">Medidas:</h6>
                                <table class="table table-condensed table-stripped table-sm table-hover">
                                    <thead>
                                        <tr>
                                            <th>Status</th>
                                            <th>Descrição</th>
                                            <th>Data Criação</th>
                                            <th>Data Fim Desejada</th>
                                            <th>Data Fim</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($protest->medProtests?->sortByDesc('dtCriacaoMedida') as $medProtest)
                                            <tr>
                                                <td>
                                                    @if ($medProtest->statusSist === 'MEDA')
                                                        <span class="badge text-bg-danger">ABERTO</span>
                                                    @else
                                                        <span class="badge text-bg-secondary">FECHADO</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    {{ $medProtest->txtCodMedida }}
                                                </td>
                                                <td>
                                                    {{ $medProtest->dtCriacaoMedida?->format('d/m/Y') }}
                                                </td>
                                                <td>
                                                    {{ $medProtest->dtFimMedidaDesej?->format('d/m/Y') }}
                                                </td>
                                                <td>
                                                    {{ $medProtest->dtFimMedida?->format('d/m/Y') }}
                                                </td>
                                            </tr>

                                        @empty
                                            <tr>
                                                <td colspan="5" class="text-center text-muted">
                                                    Nenhuma medida registrada.
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="border rounded p-3">
                                <h6 class="text-muted mb-2">Datas</h6>
                                <p class="mb-1"><strong>Abertura:</strong>
                                    {{ $protest->dtAberturaNota?->format('d/m/Y') }}</p>
                                <p class="mb-1"><strong>Conclusão Prevista:</strong>
                                    {{ $protest->dtConclusaoDesej?->format('d/m/Y') }}</p>
                            </div>
                        </div>


                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
