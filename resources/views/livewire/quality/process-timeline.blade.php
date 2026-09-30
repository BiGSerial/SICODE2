<div wire:poll.20s>
    @php
        use App\Support\QualityUi;
        $tones = ['success' => ['#10b981', '#ecfdf5'], 'danger' => ['#ef4444', '#fef2f2'], 'warning' => ['#f59e0b', '#fffbeb'], 'primary' => ['#2563eb', '#eff6ff'], 'info' => ['#0891b2', '#ecfeff'], 'secondary' => ['#64748b', '#f1f5f9']];
        $roleNames = ['DESIGNER' => 'Usuário', 'N1' => 'N1', 'N2' => 'N2', 'Gestão' => 'Gestão'];
    @endphp
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div class="btn-group btn-group-sm" role="group" aria-label="Filtrar histórico">
            @foreach (['todos' => 'Tudo', 'decisoes' => 'Decisões', 'fluxo' => 'Fluxo'] as $key => $label)
                <button type="button" wire:click="setFilter('{{ $key }}')" class="btn {{ $filter === $key ? 'btn-primary' : 'btn-outline-primary' }}">{{ $label }}</button>
            @endforeach
        </div>
        <div class="small text-muted">{{ $total }} evento(s) · atualiza sozinho</div>
    </div>

    @forelse ($days as $day => $events)
        @php
            $date  = \Illuminate\Support\Carbon::parse($day);
            $label = $date->isToday() ? 'Hoje' : ($date->isYesterday() ? 'Ontem' : $date->translatedFormat('d \d\e F \d\e Y'));
        @endphp
        <div class="tl-day"><span>{{ $label }}</span></div>
        @foreach ($events as $event)
            @php
                [$color, $soft] = $tones[$event->type->tone()];
                $actor  = $event->Actor?->name ?? 'Sistema';
                $change = $event->from_state && $event->to_state && $event->from_state !== $event->to_state;
            @endphp
            <div class="tl-item" wire:key="tl-{{ $event->id }}">
                <div class="tl-avatar" style="background: {{ QualityUi::avatarTone($actor) }}" title="{{ $actor }}">{{ QualityUi::initials($actor) }}<span class="tl-ico" style="background: {{ $color }}"><i class="{{ $event->type->icon() }}"></i></span></div>
                <div class="tl-card" style="border-left-color: {{ $color }}">
                    <div class="d-flex justify-content-between align-items-start gap-2 flex-wrap">
                        <div class="tl-title">{{ $event->type->label() }}@if ($event->Target) <span class="tl-target">→ {{ $event->Target->name }}</span>@endif</div>
                        <span class="tl-time" title="{{ $event->created_at->format('d/m/Y H:i:s') }}">{{ $event->created_at->format('H:i') }}</span>
                    </div>
                    <div class="tl-meta">
                        <strong>{{ $actor }}</strong>
                        @if ($event->actor_role)<span class="chip">{{ $roleNames[$event->actor_role] ?? $event->actor_role }}</span>@endif
                        @if ($event->stage_type)<span class="chip chip-cycle">{{ $event->stage_type->short() }}@if ($event->round_number) · rodada {{ $event->round_number }}@endif</span>@endif
                    </div>
                    @if ($change)
                        <div class="tl-change"><span>{{ $event->from_state->labelFor(auth()->user()->can('quality.n2')) }}</span><i class="ri-arrow-right-line"></i><span class="to">{{ $event->to_state->labelFor(auth()->user()->can('quality.n2')) }}</span></div>
                    @endif
                    @if ($event->observation)<blockquote class="tl-quote">{{ $event->observation }}</blockquote>@endif
                </div>
            </div>
        @endforeach
    @empty
        <div class="ob-empty"><i class="ri-history-line"></i><div class="fw-bold">Nenhum evento neste filtro.</div></div>
    @endforelse

    @if ($shown < $total)
        <div class="text-center mt-2"><button type="button" wire:click="loadMore" wire:loading.attr="disabled" class="btn btn-outline-primary btn-sm">Carregar mais ({{ $total - $shown }} restante(s))</button></div>
    @endif
</div>
