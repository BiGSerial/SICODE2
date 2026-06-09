@php
    $activityAccent = $accent ?? '#0f766e';
    $activityContext = $context ?? 'Atividades do usuário';
    $activitySubtitle = $subtitle ?? 'Gestão e acompanhamento de atividades';
    $activityTotal = $total ?? 0;
@endphp

<div class="user-activity-hero d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3"
    style="--activity-accent: {{ $activityAccent }};">
    <div>
        <div class="activity-meta text-uppercase">{{ $activityContext }}</div>
        <h2>{{ mb_strtoupper($service->service) }}</h2>
        <div class="activity-meta mt-1">{{ $activitySubtitle }}</div>
    </div>
    <div class="d-flex align-items-center gap-4 text-lg-end">
        @if ($service->Status->count())
            <div>
                <div class="activity-meta">Status do serviço</div>
                <strong>
                    @foreach ($service->Status->where('exclusion', false)->unique('value') as $sts)
                        ({{ $sts->value }})
                    @endforeach
                </strong>
            </div>
        @endif
        <div>
            <div class="activity-meta">Registros</div>
            <div class="activity-count">{{ $activityTotal }}</div>
        </div>
    </div>
</div>
