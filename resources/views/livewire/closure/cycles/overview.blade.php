<div>
    <div class="row g-3 mb-3">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h5 class="mb-1">Controle de Encerramento — Visão Geral</h5>
                    @if ($currentCycle)
                        <div class="text-muted">Competência mais recente: <strong>{{ $currentCycle->label }}</strong></div>
                    @else
                        <div class="text-muted">Nenhuma competência congelada ainda.</div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small">Meta do mês</div>
                    <div class="fs-3 fw-bold">{{ $metaTotal }}</div>
                    <div class="small">
                        <span class="text-success">{{ $metaClosed }} encerradas</span>
                        &middot;
                        <span class="text-warning">{{ $metaOpen }} em aberto</span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small">Passivo acumulado</div>
                    <div class="fs-3 fw-bold">{{ $passiveTotal }}</div>
                    <div class="small text-muted">Ordens de competências anteriores ainda não encerradas</div>
                </div>
            </div>
        </div>
    </div>

    <div class="mt-3">
        <a href="{{ route('closure.meta') }}" class="btn btn-outline-primary btn-sm">Ver Meta</a>
        <a href="{{ route('closure.passive') }}" class="btn btn-outline-secondary btn-sm">Ver Passivo</a>
    </div>
</div>
