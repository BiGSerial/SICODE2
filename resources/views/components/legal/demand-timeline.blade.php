@props(['events'])
@php
    $iconMap = [
        'triage_started'           => ['icon' => 'bi-clipboard-check',  'color' => 'warning'],
        'sent_to_field'            => ['icon' => 'bi-send',             'color' => 'primary'],
        'field_received'           => ['icon' => 'bi-inbox',            'color' => 'primary'],
        'field_answered'           => ['icon' => 'bi-chat-check',       'color' => 'success'],
        'returned_for_correction'  => ['icon' => 'bi-arrow-return-left','color' => 'danger'],
        'controller_reviewed'      => ['icon' => 'bi-eye',              'color' => 'secondary'],
        'closed_internal'          => ['icon' => 'bi-lock',             'color' => 'dark'],
        'closed_external'          => ['icon' => 'bi-check-circle',     'color' => 'success'],
        'imported'                 => ['icon' => 'bi-cloud-download',   'color' => 'info'],
        'cancelled'                => ['icon' => 'bi-x-circle',         'color' => 'dark'],
        'ignored'                  => ['icon' => 'bi-slash-circle',     'color' => 'secondary'],
        'reopened'                 => ['icon' => 'bi-arrow-clockwise',  'color' => 'warning'],
        'comment_added'            => ['icon' => 'bi-chat',             'color' => 'light'],
        'file_uploaded'            => ['icon' => 'bi-paperclip',        'color' => 'light'],
        'controller_reassigned'    => ['icon' => 'bi-person-gear',      'color' => 'secondary'],
        'approved_for_close'       => ['icon' => 'bi-check2-all',       'color' => 'success'],
    ];
@endphp
<div class="legal-timeline">
    @forelse($events->sortByDesc('occurred_at')->groupBy(fn($e) => \Carbon\Carbon::parse($e->occurred_at)->format('d/m/Y')) as $date => $dayEvents)
        <div class="legal-timeline__date-separator">{{ $date }}</div>
        @foreach($dayEvents as $event)
            @php
                $ic = $iconMap[$event->event_type] ?? ['icon' => 'bi-circle', 'color' => 'secondary'];
            @endphp
            <div class="legal-timeline__item">
                <div class="legal-timeline__icon bg-{{ $ic['color'] }}">
                    <i class="{{ $ic['icon'] }}"></i>
                </div>
                <div class="legal-timeline__content">
                    <div class="legal-timeline__meta">
                        <strong>{{ $event->actor?->name ?? 'Sistema' }}</strong>
                        <span class="text-muted ms-2">{{ \Carbon\Carbon::parse($event->occurred_at)->format('H:i') }}</span>
                    </div>
                    <div class="mt-1">{{ $event->description }}</div>
                    @if($event->from_status && $event->to_status)
                        <div class="mt-1">
                            <x-legal.status-badge :status="$event->from_status" size="sm" />
                            <span class="mx-1 text-muted">→</span>
                            <x-legal.status-badge :status="$event->to_status" size="sm" />
                        </div>
                    @endif
                </div>
            </div>
        @endforeach
    @empty
        <x-legal.empty-state icon="bi-clock-history" message="Nenhum evento registrado ainda." />
    @endforelse
</div>
