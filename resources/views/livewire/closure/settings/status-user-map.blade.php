<div class="closure-dash">
    <div class="dash-hero mb-3">
        <h2 class="dash-title">Mapa de status por serviço</h2>
        <div class="dash-subtitle">Escolha, para cada serviço, quais <strong>status do usuário</strong> (SAP) pertencem a ele. A caixa do serviço mostra, só para acompanhar, as Ordens do Encerramento que estão nesses status — e a Ordem sai da caixa sozinha quando o status muda.</div>
    </div>

    <div class="row g-3">
        @foreach ($areas as $area => $label)
            @php $owned = collect($catalog)->where('area', $area)->count(); @endphp
            <div class="col-12 col-xl-6" wire:key="status-map-{{ $area }}">
                <div class="table-card h-100" x-data="{ q: '' }">
                    <div class="chart-head">
                        <div>
                            <h3 class="chart-title">{{ $label }}</h3>
                            <div class="chart-subtitle">{{ count($selected[$area] ?? []) }} status marcado(s) · {{ $owned }} salvo(s)</div>
                        </div>
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-outline-secondary btn-sm" wire:click="reload" wire:loading.attr="disabled">Desfazer</button>
                            <button type="button" class="btn btn-dash btn-sm text-white map-save" style="background: var(--dash-green);" wire:click="save('{{ $area }}')" wire:loading.attr="disabled" wire:target="save">Salvar {{ $label }}</button>
                        </div>
                    </div>
                    <div class="p-3 pt-0">
                        <input type="search" class="form-control form-control-sm mb-2" placeholder="Buscar status…" aria-label="Buscar status de {{ $label }}" x-model="q">
                        <div class="map-list">
                            @forelse ($catalog as $row)
                                @php $taken = $row['area'] !== null && $row['area'] !== $area; @endphp
                                <label class="map-item {{ $taken ? 'is-taken' : '' }}" wire:key="map-{{ $area }}-{{ $row['prefix'] }}"
                                       x-show="q === '' || @js(mb_strtolower($row['prefix'])).includes(q.toLowerCase())">
                                    <input type="checkbox" class="form-check-input mt-0" value="{{ $row['prefix'] }}" wire:model.defer="selected.{{ $area }}" @disabled($taken)>
                                    <span class="flex-grow-1 fw-semibold">{{ $row['prefix'] }}</span>
                                    @if ($taken)
                                        <span class="badge text-bg-warning" title="Libere o status no outro serviço para usá-lo aqui.">Já em {{ \App\Services\Closure\ClosureArea::label($row['area']) }}</span>
                                    @endif
                                    <span class="badge text-bg-info" title="Ordens do Encerramento (abertas) neste status agora">{{ $row['count'] }} no Encerramento</span>
                                    <span class="badge text-bg-light" title="Todas as Ordens do SAP neste status">{{ $row['total'] }} no total</span>
                                </label>
                            @empty
                                <x-closure.empty-state title="Nenhum status encontrado" text="Ainda não há Ordens do Encerramento com status do usuário." />
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</div>
