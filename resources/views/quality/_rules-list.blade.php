{{-- Lista de critérios do Pool. Espera $rules e $deletable. --}}
@php
    $pretty = fn ($column) => \App\Support\QualityUi::noteColumn($column);
    $conditionsLabels = \App\Http\Livewire\Config\Services\Addstatus::CONDITIONS;
    $show = function ($value, $condition) {
        $decoded = json_decode((string) $value, true);

        return in_array($condition, ['Em', 'NaoEstaEm'], true) && is_array($decoded) ? implode(', ', $decoded) : $value;
    };
@endphp
@forelse ($rules as $rule)
    <div class="d-flex justify-content-between align-items-start border rounded p-2 mb-2">
        <div class="small">
            <span class="badge text-bg-{{ $rule->exclusion ? 'danger' : 'success' }}">{{ $rule->exclusion ? 'Exclui' : 'Inclui' }}</span>
            <strong>{{ $pretty($rule->column_search) }}</strong> {{ mb_strtolower($conditionsLabels[$rule->condition] ?? $rule->condition) }} <em>{{ $show($rule->value, $rule->condition) }}</em>
            @if ($rule->column_search2)
                <br><span class="text-muted">e</span> <strong>{{ $pretty($rule->column_search2) }}</strong> {{ mb_strtolower($conditionsLabels[$rule->condition2] ?? $rule->condition2) }} <em>{{ $show($rule->value2, $rule->condition2) }}</em>
            @endif
        </div>
        @if ($deletable)
            <form method="post" action="{{ route('quality.rules.destroy', $rule) }}" onsubmit="return confirm('Remover este critério?')">@csrf @method('DELETE')
                <button class="btn btn-sm btn-outline-danger"><i class="ri-delete-bin-line"></i> Remover</button>
            </form>
        @endif
    </div>
@empty
    <div class="text-muted small">Nenhum critério configurado.</div>
@endforelse
