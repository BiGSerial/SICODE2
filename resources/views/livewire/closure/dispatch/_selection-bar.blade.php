{{-- Barra de seleção: ações em massa em dropdown, com a explicação de cada uma; os campos e a prévia ficam no modal. `$withTransfer` acrescenta "Transferir selecionadas" (lista do executor). --}}
@php $selectedCount = count($selectedStateIds); @endphp
@if ($selectedCount > 0)
    <div class="ops-selection" id="closure-control-bulk">
        <div class="ops-selection-count"><strong>{{ $selectedCount }}</strong> {{ $selectedCount === 1 ? 'selecionada' : 'selecionadas' }}</div>
        <div class="ops-selection-actions">
            @if ($batchActions)
                <div class="dropup">
                    <button type="button" class="btn btn-light btn-sm btn-dash dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false"><i class="ri-stack-line" aria-hidden="true"></i> Ações em massa</button>
                    <ul class="dropdown-menu ops-bulk-menu">
                        @foreach ($batchActions as $key => $action)
                            <li><button type="button" class="dropdown-item" wire:click="openBatch('{{ $key }}')"><i class="{{ $action['icon'] }}" aria-hidden="true"></i><span>{{ $action['label'] }}<small>{{ $action['hint'] }}</small></span></button></li>
                        @endforeach
                    </ul>
                </div>
            @endif
            @if ($withTransfer ?? false)
                <button type="button" class="btn btn-light btn-sm btn-dash" wire:click="transferSelected"><i class="ri-user-shared-line" aria-hidden="true"></i> Transferir selecionadas</button>
            @endif
            @if ($states->total() > $selectedCount && $selectedCount < \App\Http\Livewire\Closure\Work\Index::SELECT_ALL_LIMIT)
                <button type="button" class="btn btn-outline-light btn-sm btn-dash" wire:click="selectAllFilteredStates">Selecionar todas filtradas</button>
            @endif
        </div>
        <button type="button" class="ops-selection-clear" wire:click="clearSelectionNow" aria-label="Limpar seleção"><i class="ri-close-line"></i></button>
    </div>
@endif

