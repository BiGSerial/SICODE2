@extends('layouts.padrao')

@section('breadcrumb')
    <nav aria-label="breadcrumb" class="py-0 my-0">
        <ol class="breadcrumb bg-light px-3 pt-3 rounded-3">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
                <li class="breadcrumb-item">Protests</li>
                <li class="breadcrumb-item active" aria-current="page">Visão Geral</li>
            </ol>
        </ol>
    </nav>
@endsection

@section('content')
    <div class="container-fluid py-3">
        <div class="row g-4">
            <div class="col-12">
                <div class="card border-0 shadow hero-card text-white">
                    <div class="card-body d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between gap-3">
                        <div>
                            <p class="text-uppercase small mb-1 opacity-75">Painel de Protestos</p>
                            <h1 class="fw-bold mb-2">Visão Geral Informativa</h1>
                            <p class="mb-0 opacity-75">
                                Esta página resume os principais indicadores e jornadas que compõem a experiência dentro do módulo de Protestos.
                                Use-a como referência rápida para entender o fluxo e as áreas analisadas no detalhe.
                            </p>
                        </div>
                        <div class="text-end">
                            <p class="text-uppercase small mb-1 opacity-75">Atualizado automaticamente</p>
                            <span class="badge bg-light text-dark fs-6 px-4 py-2">Informativo</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12">
                <div class="row g-3">
                    <div class="col-md-3">
                        <div class="info-card shadow-sm">
                            <p class="info-label">Notas Monitoradas</p>
                            <h2 class="info-value">1.280</h2>
                            <p class="info-description">Total de notas com Medidas vinculadas no ambiente.</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="info-card shadow-sm">
                            <p class="info-label">Medidas Ativas</p>
                            <h2 class="info-value text-warning">312</h2>
                            <p class="info-description">Acompanhadas diariamente pelo time de despacho.</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="info-card shadow-sm">
                            <p class="info-label">Jobs em Progresso</p>
                            <h2 class="info-value text-primary">87</h2>
                            <p class="info-description">Execuções com SLA controlado pela equipe.</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="info-card shadow-sm">
                            <p class="info-label">Fechamentos do Dia</p>
                            <h2 class="info-value text-success">24</h2>
                            <p class="info-description">Registros concluídos e homologados.</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-lg-8">
                <div class="card border-0 shadow h-100">
                    <div class="card-header bg-white border-0 d-flex justify-content-between align-items-center">
                        <h5 class="mb-0 text-uppercase text-muted">Linha do Tempo</h5>
                        <small class="text-muted">Do registro ao encerramento</small>
                    </div>
                    <div class="card-body">
                        <div class="timeline">
                            <div class="timeline-item">
                                <div class="timeline-dot bg-success"></div>
                                <div class="timeline-content">
                                    <h6>Registro do Protesto</h6>
                                    <p class="text-muted mb-0">Nota é integrada ao sistema, criamos a Medida e vinculamos responsáveis.</p>
                                </div>
                            </div>
                            <div class="timeline-item">
                                <div class="timeline-dot bg-primary"></div>
                                <div class="timeline-content">
                                    <h6>Despacho & Jobs</h6>
                                    <p class="text-muted mb-0">Tarefas são distribuídas, com SLAs e status acompanhados pela visão de Dispatch.</p>
                                </div>
                            </div>
                            <div class="timeline-item">
                                <div class="timeline-dot bg-warning"></div>
                                <div class="timeline-content">
                                    <h6>Monitoramento</h6>
                                    <p class="text-muted mb-0">Alertas proativos indicam medidas próximas do vencimento ou aguardando insumos.</p>
                                </div>
                            </div>
                            <div class="timeline-item">
                                <div class="timeline-dot bg-danger"></div>
                                <div class="timeline-content">
                                    <h6>Fechamento</h6>
                                    <p class="text-muted mb-0">Ao cumprir toda a jornada, registramos evidências e finalizamos o Protesto.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-lg-4">
                <div class="card border-0 shadow h-100">
                    <div class="card-header bg-white border-0">
                        <h5 class="mb-0 text-uppercase text-muted">Status de Jobs</h5>
                    </div>
                    <div class="card-body">
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <span><i class="ri-play-circle-line text-primary me-2"></i>Em andamento</span>
                                <span class="badge bg-primary">53</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <span><i class="ri-timer-line text-warning me-2"></i>Aguardando ação</span>
                                <span class="badge bg-warning text-dark">21</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <span><i class="ri-check-double-line text-success me-2"></i>Concluídos</span>
                                <span class="badge bg-success">411</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <span><i class="ri-alert-line text-danger me-2"></i>Com risco</span>
                                <span class="badge bg-danger">9</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="col-12">
                <div class="card border-0 shadow">
                    <div class="card-header bg-white border-0 d-flex justify-content-between flex-wrap gap-2 align-items-center">
                        <div>
                            <h5 class="mb-0 text-uppercase text-muted">Checklist do Protesto</h5>
                            <small class="text-muted">Veja como cada etapa se conecta</small>
                        </div>
                        <div class="d-flex gap-2">
                            <span class="badge bg-success-subtle text-success">CIP</span>
                            <span class="badge bg-primary-subtle text-primary">Construção</span>
                            <span class="badge bg-dark text-white">BT Zero</span>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <div class="step-card h-100">
                                    <h6>1. Diagnóstico</h6>
                                    <p class="mb-1">Analisamos a origem e os artefatos recebidos.</p>
                                    <small class="text-muted">Notas, documentos e histórico do cliente.</small>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="step-card h-100">
                                    <h6>2. Planejamento</h6>
                                    <p class="mb-1">Definimos a Medida com datas e equipe responsável.</p>
                                    <small class="text-muted">Associações com MedProtest e jobs.</small>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="step-card h-100">
                                    <h6>3. Execução</h6>
                                    <p class="mb-1">Monitoramento diário dos prazos e evidências.</p>
                                    <small class="text-muted">Notas, jobs e acompanhamento dos arquivos.</small>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="step-card h-100">
                                    <h6>4. Encerramento</h6>
                                    <p class="mb-1">Consolidação dos resultados e comunicação final.</p>
                                    <small class="text-muted">Confirmação com despachante e partes envolvidas.</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('styles')
    <style>
        .hero-card {
            background: linear-gradient(135deg, #0f5132, #198754);
            border-radius: 18px;
        }

        .info-card {
            background: #fff;
            border-radius: 16px;
            padding: 18px;
        }

        .info-label {
            text-transform: uppercase;
            font-size: .75rem;
            letter-spacing: .08em;
            color: #6c757d;
            margin-bottom: .3rem;
        }

        .info-value {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: .4rem;
        }

        .info-description {
            font-size: .9rem;
            color: #6c757d;
        }

        .timeline {
            position: relative;
            margin-left: 10px;
        }

        .timeline:before {
            content: '';
            position: absolute;
            left: 12px;
            top: 0;
            bottom: 0;
            width: 2px;
            background: #e9ecef;
        }

        .timeline-item {
            position: relative;
            padding-left: 40px;
            margin-bottom: 20px;
        }

        .timeline-dot {
            position: absolute;
            left: 4px;
            top: 4px;
            width: 16px;
            height: 16px;
            border-radius: 50%;
            box-shadow: 0 0 0 6px rgba(0, 0, 0, 0.05);
        }

        .timeline-content h6 {
            margin: 0 0 4px;
            font-weight: 600;
        }

        .timeline-content p {
            margin: 0;
            font-size: .9rem;
        }

        .step-card {
            border: 1px solid #e9ecef;
            border-radius: 12px;
            padding: 16px;
            background: #fff;
        }

        .step-card h6 {
            font-weight: 700;
            margin-bottom: .5rem;
        }
    </style>
@endpush
