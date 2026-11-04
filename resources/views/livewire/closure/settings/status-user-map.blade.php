{{-- Mapa de status por serviço (Gestão do ciclo). Padrão de configuração: docs/PADRAO_LISTAS_OPERACIONAIS.md (hero + painéis .ops-settings). --}}
<div class="closure-dash my-panel ops-list">
    <x-ops.hero eyebrow="Gestão do ciclo · Encerramento" title="Caixas das atividades" subtitle="Escolha, para cada serviço, quais status do usuário (SAP) pertencem a ele. A caixa do serviço mostra, só para acompanhar, as Ordens do Encerramento que estão nesses status, e a Ordem sai da caixa sozinha quando o status muda." />

    <div class="ops-settings">
        @foreach ($areas as $area => $label)
            @php $owned = collect($catalog)->where('area', $area)->count(); @endphp
            <section class="mp-panel" aria-labelledby="map-{{ $area }}" x-data="{ q: '' }" wire:key="status-map-{{ $area }}">
                <div class="mp-panel-head">
                    <div>
                        <h2 class="mp-panel-title" id="map-{{ $area }}">{{ $label }}</h2>
                        <div class="mp-panel-sub">{{ count($selected[$area] ?? []) }} status marcado(s) · {{ $owned }} salvo(s). Um status só pode estar em um serviço.</div>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-outline-secondary btn-sm btn-dash" wire:click="reload" wire:loading.attr="disabled"><i class="ri-restart-line" aria-hidden="true"></i> Desfazer</button>
                        <button type="button" class="btn btn-primary btn-sm btn-dash" wire:click="save('{{ $area }}')" wire:loading.attr="disabled" wire:target="save"><i class="ri-save-line" aria-hidden="true"></i> Salvar {{ $label }}</button>
                    </div>
                </div>
                <div class="mp-panel-body">
                    <input type="search" class="form-control form-control-sm mb-2" placeholder="Buscar status…" aria-label="Buscar status de {{ $label }}" x-model="q">
                    <div class="ops-settings-list">
                        @forelse ($catalog as $row)
                            @php $taken = $row['area'] !== null && $row['area'] !== $area; @endphp
                            <label class="ops-settings-item @if ($taken) is-taken @endif" wire:key="map-{{ $area }}-{{ $row['prefix'] }}"
                                   x-show="q === '' || @js(mb_strtolower($row['prefix'])).includes(q.toLowerCase())">
                                <input type="checkbox" class="form-check-input mt-0" value="{{ $row['prefix'] }}" wire:model.defer="selected.{{ $area }}" @disabled($taken)>
                                <span class="flex-grow-1 fw-semibold">{{ $row['prefix'] }}</span>
                                @if ($taken)
                                    <x-ops.badge sm tone="amber" :label="'Já em ' . \App\Services\Closure\ClosureArea::label($row['area'])" hint="Libere o status no outro serviço para usá-lo aqui." />
                                @endif
                                <x-ops.badge sm tone="blue" :label="$row['count'] . ' no Encerramento'" hint="Ordens do Encerramento (abertas) neste status agora" />
                                <x-ops.badge sm tone="muted" :label="$row['total'] . ' no total'" hint="Todas as Ordens do SAP neste status" />
                            </label>
                        @empty
                            <x-closure.empty-state title="Nenhum status encontrado" text="Ainda não há Ordens do Encerramento com status do usuário." />
                        @endforelse
                    </div>
                </div>
            </section>
        @endforeach
    </div>
</div>
