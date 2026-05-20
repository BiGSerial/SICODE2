@props(['assignment'])
@php
    $demand = $assignment->legalDemand ?? $assignment->demand ?? null;
@endphp
<div class="legal-card">
    <div class="d-flex justify-content-between align-items-start mb-2">
        <div>
            @if($demand)
                <span class="badge bg-secondary me-1">{{ $demand->source_type }}</span>
            @endif
            <x-legal.status-badge :status="$assignment->status" />
        </div>
        @if($demand?->source_due_at)
            <x-legal.due-date-chip :date="$demand->source_due_at" />
        @endif
    </div>
    @if($demand)
        <div class="fw-semibold">{{ $demand->source_case_number ?? $demand->source_process_number }}</div>
        <div class="small text-muted">{{ $demand->legalCase?->company_name }}</div>
    @endif
    <div class="small mt-2 text-muted">
        Enviado por: {{ $assignment->sentBy?->name ?? '—' }}
        em {{ \Carbon\Carbon::parse($assignment->sent_at)->format('d/m/Y H:i') }}
    </div>
    @if($assignment->message)
        <div class="small mt-1 border-start border-2 ps-2 text-muted">"{{ Str::limit($assignment->message, 100) }}"</div>
    @endif
</div>
