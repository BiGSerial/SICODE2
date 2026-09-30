{{-- Jornada completa da obra: cada etapa com quem, quando e situação. Espera $process e $activityNames. --}}
@php
    use App\Enum\{QualityProcessState as S, QualityStageKind as K, QualityStageLevel as L, QualityStageType as P};

    $state   = $process->state;
    $phases  = [P::PROJECT->value => ['keys' => ['dispatch', 'exec', 'n1', 'n2'], 'base' => 0], P::BUDGET->value => ['keys' => ['exec', 'n1', 'n2'], 'base' => 4]];
    $keyOf   = fn (S $s) => match ($s) { S::AWAITING_N1_DISPATCH => 'dispatch', S::AWAITING_DESIGNER => 'exec', S::AWAITING_N1_REVIEW, S::N2_RETURNED => 'n1', default => 'n2' };
    $offset  = fn (string $phase, string $key) => $phases[$phase]['base'] + array_search($key, $phases[$phase]['keys'], true);
    $current = $state === S::COMPLETED ? 7 : $offset($process->phase->value, $keyOf($state));
    $stages  = $process->Stages;
    $seesN2  = auth()->user()->can('quality.n2');

    $meta = [
        'dispatch' => ['Despacho', 'ri-send-plane-2-line', 'N1 escolhe o usuário'],
        'exec'     => ['Execução', 'ri-user-settings-line', 'Usuário executa'],
        'n1'       => ['Análise N1', 'ri-file-search-line', 'N1 aprova ou rejeita'],
        'n2'       => ['Decisão N2', 'ri-user-star-line', 'N2 aprova ou devolve'],
    ];

    $initials = fn (?string $name) => $name ? collect(preg_split('/\s+/', trim($name)))->filter(fn ($w) => mb_strlen($w) > 2)->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->take(2)->implode('') : '?';

    $detail = function (string $phase, string $key) use ($stages, $process) {
        $sel = $stages->where('type.value', $phase);
        $sel = match ($key) {
            'dispatch' => $sel->where('kind.value', K::DISPATCH->value),
            'exec'     => $sel->where('kind.value', K::EXECUTION->value),
            'n1'       => $sel->where('kind.value', K::REVIEW->value)->where('level.value', L::N1->value),
            default    => $sel->where('kind.value', K::REVIEW->value)->where('level.value', L::N2->value),
        };
        $last = $sel->sortBy('id')->last();
        $who  = match ($key) {
            'exec'  => $last?->AssignedUser?->name ?? $last?->ExecutedBy?->name,
            'n2'    => $last?->ApprovedBy?->name ?? $process->CompletedBy?->name,
            default => $last?->ApprovedBy?->name ?? $last?->DispatchedBy?->name ?? $process->N1User?->name,
        };

        return ['rounds' => $sel->count(), 'at' => $last?->completed_at ?? $last?->dispatched_at, 'who' => $who];
    };
@endphp

<div class="jr">
    @foreach ($phases as $phaseKey => $def)
        @php
            $phaseEnum = P::from($phaseKey);
            $first     = $def['base'];
            $last      = $def['base'] + count($def['keys']) - 1;
            $phaseDone = $current > $last;
            $phaseNow  = $current >= $first && $current <= $last;
            $service   = $activityNames[$phaseKey] ?? null;
        @endphp
        <div class="jr-phase {{ $phaseNow ? 'is-now' : '' }} {{ $phaseDone ? 'is-done' : '' }}">
            <div class="jr-phase-head">
                <div><span class="jr-phase-title">{{ $phaseEnum->label() }}</span>@if ($service)<span class="jr-phase-service"> · {{ $service }}</span>@endif</div>
                @if ($phaseDone)<span class="badge text-bg-success"><i class="ri-check-line"></i> Concluído</span>
                @elseif ($phaseNow)<span class="badge text-bg-primary">Rodada {{ $process->round_number }}</span>
                @else<span class="badge text-bg-light border">Ainda não iniciou</span>@endif
            </div>
            <div class="jr-steps">
                @foreach ($def['keys'] as $key)
                    @php
                        $idx    = $offset($phaseKey, $key);
                        $status = $idx < $current ? 'done' : ($idx === $current ? 'now' : 'todo');
                        $info   = $detail($phaseKey, $key);
                        [$label, $icon, $hint] = $meta[$key];
                        $person = $info['who'] ?? ($status === 'now' && $key === 'exec' ? $process->CurrentDesigner?->name : null);
                        $returned = $status === 'now' && $state === S::N2_RETURNED;
                        $tone   = $status === 'now' ? ($returned || in_array($state, [S::SAP_FAILED, S::CLOSING], true) ? 'danger' : ($state->badge() === 'info' ? 'info' : 'primary')) : $status;
                    @endphp
                    <div class="jr-step is-{{ $status }} tone-{{ $tone }}">
                        <div class="jr-node"><i class="{{ $status === 'done' ? 'ri-check-line' : $icon }}"></i></div>
                        <div class="jr-body">
                            <div class="jr-label">{{ $label }}</div>
                            @if ($key === 'n2' && !$seesN2 && $status === 'now')
                                <div class="jr-person">N2</div>
                            @else
                                <div class="jr-person">@if ($person)<span class="jr-avatar">{{ $initials($person) }}</span>{{ \Illuminate\Support\Str::limit($person, 22) }}@else<span class="text-muted">{{ $hint }}</span>@endif</div>
                            @endif
                            <div class="jr-when">
                                @if ($status === 'now')@include('quality._age', ['since' => $process->state_changed_at])
                                @elseif ($status === 'done' && $info['at']){{ $info['at']->format('d/m H:i') }}
                                @else&nbsp;@endif
                                @if ($info['rounds'] > 1)<span class="jr-loop" title="{{ $info['rounds'] }} rodadas nesta etapa">↺ {{ $info['rounds'] }}</span>@endif
                            </div>
                            @if ($returned)<div class="jr-flag">devolvido pelo N2</div>@endif
                            @if ($status === 'now' && in_array($state, [S::SAP_FAILED, S::CLOSING], true) && $seesN2)<div class="jr-flag">encerramento pendente</div>@endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endforeach
    <div class="jr-end {{ $state === S::COMPLETED ? 'is-done' : '' }}">
        <div class="jr-node"><i class="{{ $state === S::COMPLETED ? 'ri-checkbox-circle-line' : 'ri-flag-2-line' }}"></i></div>
        <div class="jr-label">Concluída</div>
        <div class="jr-when">{{ $process->completed_at?->format('d/m H:i') ?? '&nbsp;' }}</div>
    </div>
</div>
