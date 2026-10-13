{{--
    Cartão de obra. Espera $process.
    Opcionais: $selectable, $action (rótulo), $primary, $ageLabel, $showReason, $showCompany, $who = 'user'|'n1'|'n2'|'auto'
--}}
@php
    use App\Enum\QualityProcessState as S;

    $seesN2     = auth()->user()->can('quality.n2');
    $idx        = $process->flowIndex();
    $urgency    = $process->urgency();
    $state      = $process->state;
    $initials   = fn (?string $name) => $name ? collect(preg_split('/\s+/', trim($name)))->filter(fn ($w) => mb_strlen($w) > 2)->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->take(2)->implode('') : '?';
    $who        = ($who ?? 'auto');
    [$roleLabel, $personName] = match (true) {
        $who === 'n1' || ($who === 'auto' && in_array($state, [S::AWAITING_N1_DISPATCH, S::AWAITING_N1_REVIEW, S::N2_RETURNED], true)) => ['N1', $process->N1User?->name],
        $who === 'n2' || ($who === 'auto' && in_array($state, [S::AWAITING_N2_REVIEW, S::CLOSING, S::SAP_FAILED], true)) => ['N2', null],
        $state === S::COMPLETED => ['Concluída por', $process->CompletedBy?->name],
        default => ['Usuário', $process->CurrentDesigner?->name],
    };
    $reject     = ($showReason ?? false) ? $process->Rejections->first() : null;
    $dotLabels  = ['Despacho do N1', 'Execução (1º ciclo)', 'Análise N1 (1º ciclo)', 'Decisão N2 (1º ciclo)', 'Execução (2º ciclo)', 'Análise N1 (2º ciclo)', 'Decisão N2 (2º ciclo)'];
    $action     = $action ?? 'Abrir';
@endphp
<tr class="wk-table-row is-{{ $urgency }}" data-href="{{ route('quality.process', $process) }}">
    @if (!empty($selectable))
        <td class="text-center wk-col-check" onclick="event.stopPropagation()"><input type="checkbox" class="form-check-input wk-row" name="process_ids[]" value="{{ $process->id }}" aria-label="Selecionar obra {{ $process->Note?->note }}"></td>
    @endif

    <td class="wk-note text-nowrap"><span class="ob-note">{{ $process->Note?->note }}</span><span class="chip chip-cycle ms-1">{{ $process->phase->short() }}</span>@if ($process->round_number > 1)<span class="chip chip-loop ms-1" title="Rodada {{ $process->round_number }}">↺{{ $process->round_number }}</span>@endif</td>
    <td class="wk-material" title="{{ $process->Note?->material }}">{{ $process->Note?->material ?: '—' }}</td>
    <td class="wk-location text-nowrap"><span>{{ $process->Note?->rubrica ?: '—' }}</span>@if ($process->Note?->lexp)<span class="chip chip-where ms-1"><i class="ri-map-pin-line"></i>{{ $process->Note->lexp }}</span>@endif @if ($reject)<span class="chip chip-reason ms-1" title="{{ $reject->Items->pluck('Category.name')->filter()->implode(' · ') }}"><i class="ri-arrow-go-back-line"></i>{{ $reject->Items->count() }}</span>@endif</td>

    @if (!empty($showCompany))<td class="wk-company">{{ $process->Company?->name ?? '—' }}</td>@endif

    <td>
        <div class="ob-people">
        <div class="ob-avatar {{ $personName ? '' : 'is-empty' }}">{{ $personName ? $initials($personName) : '—' }}</div>
        <div><div class="ob-person">{{ $personName ? \Illuminate\Support\Str::limit($personName, 24) : ($roleLabel === 'N2' ? 'Equipe N2' : 'a definir') }}</div><div class="ob-role">{{ $roleLabel }}</div></div>
        </div>
    </td>

    <td><div class="ob-flow" title="{{ $state->labelFor($seesN2) }}">
        <div class="flow-dots">
            @foreach (range(0, 6) as $i)
                @if ($i === 4)<span class="flow-sep"></span>@endif
                <span class="flow-dot {{ $i < $idx ? 'is-done' : ($i === $idx ? 'is-now ' . ($urgency === 'late' ? 'is-late' : '') : '') }}" title="{{ $dotLabels[$i] }}"></span>
            @endforeach
        </div>
        <div class="flow-cap">{{ $state === S::COMPLETED ? 'concluída' : $state->labelFor($seesN2) }}</div>
    </div></td>

    <td class="text-end" onclick="event.stopPropagation()"><div class="ob-side">
        <div class="ob-age">@include('quality._age', ['since' => $process->state_changed_at])<small>{{ $ageLabel ?? 'nesta etapa' }}</small></div>
        <a href="{{ route('quality.process', $process) }}" class="btn btn-sm btn-{{ !empty($primary) ? 'primary' : 'outline-primary' }}">{{ $action }}</a>
    </div></td>
</tr>
