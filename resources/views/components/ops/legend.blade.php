@php $states = \App\Support\Ops\RowState::all(); @endphp

{{-- Legenda da cor da linha (fonte: RowState). Mesma em toda lista operacional. --}}
<div class="dropdown ops-legend">
    <button class="btn btn-sm btn-outline-light dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
        <i class="ri-palette-line" aria-hidden="true"></i> Legenda
    </button>
    <div class="dropdown-menu dropdown-menu-end p-3">
        <div class="ops-legend-title">Cor da linha</div>
        @foreach ($states as $key => $state)
            <div class="ops-legend-row">
                <span class="ops-row-swatch ops-row-{{ $key }}" aria-hidden="true"></span>
                <span><strong>{{ $state['label'] }}</strong><br><small>{{ $state['hint'] }}</small></span>
            </div>
        @endforeach
    </div>
</div>
