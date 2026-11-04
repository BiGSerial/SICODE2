{{--
    Modal de Operações da Ordem (situação + fim real de cada operação).
    Variáveis: $opsOrder (Order|null), $opsList (Collection<Operation>), $opsClose (nome do método Livewire que fecha).
    Aberto só quando $opsOrder existe; mesmo markup em Gestão da Obra e no Controle/Minha fila.
--}}
@if ($opsOrder ?? null)
    @php
        $isConf   = fn ($status) => str_starts_with(strtoupper((string) $status), 'CONF');
        $opsTotal = $opsList->count();
        $opsConf  = $opsList->filter(fn ($op) => $isConf($op->status))->count();
        $opsPct   = $opsTotal ? (int) round($opsConf / $opsTotal * 100) : 0;
    @endphp
    <div class="cpop-overlay" wire:click.self="{{ $opsClose }}" x-data @keydown.escape.window="$wire.{{ $opsClose }}()">
        <div class="cpop cpop-wide" role="dialog" aria-modal="true" aria-label="Operações da Ordem {{ $opsOrder->ordem }}">
            <div class="cpop-head">
                <span class="cpop-icon"><i class="ri-list-check-2"></i></span>
                <div class="flex-grow-1">
                    <h3 class="cpop-title">Operações da Ordem {{ $opsOrder->ordem }}</h3>
                    <div class="cpop-sub">Nota {{ $opsOrder->Note?->note ?? '—' }} · {{ $opsConf }} de {{ $opsTotal }} confirmadas</div>
                    <div class="cpop-progress" aria-hidden="true"><span style="width: {{ $opsPct }}%;"></span></div>
                </div>
                <button type="button" class="cpop-close" aria-label="Fechar" wire:click="{{ $opsClose }}"><i class="ri-close-line"></i></button>
            </div>
            <div class="cpop-body">
                <ul class="cpop-ops">
                    @forelse ($opsList as $op)
                        @php $done = $isConf($op->status); @endphp
                        <li wire:key="cpop-op-{{ $op->id }}">
                            <i class="{{ $done ? 'ri-checkbox-circle-fill text-success' : 'ri-checkbox-blank-circle-line text-muted' }}" aria-hidden="true"></i>
                            <span class="cpop-op-code">{{ $op->operacao }}</span>
                            <span class="cpop-op-desc text-truncate" title="{{ $op->descOperacao }}">{{ $op->descOperacao ?: '—' }}</span>
                            <span class="cpop-op-date" title="Fim real da operação">
                                <small>Fim real</small>
                                {{ $op->fimReal?->format('d/m/Y') ?? '—' }}
                            </span>
                            <span class="badge {{ $done ? 'bg-success' : 'bg-light text-dark border' }}">{{ $op->status ?: '—' }}</span>
                        </li>
                    @empty
                        <li class="text-muted justify-content-center py-4">Esta Ordem não possui operações.</li>
                    @endforelse
                </ul>
            </div>
            <div class="cpop-foot">
                <button type="button" class="btn btn-outline-secondary btn-sm px-3" wire:click="{{ $opsClose }}">Fechar</button>
            </div>
        </div>
    </div>
@endif
