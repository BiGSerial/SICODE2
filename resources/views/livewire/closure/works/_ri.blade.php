{{-- Aba "Retorno Interno": situação do RI desta obra e o histórico de retornos com o Desenho. --}}
<div class="chart-card mb-3">
    <div class="od-section-head border-bottom">
        <div>
            <h3><i class="ri-draft-line text-muted"></i> Retorno Interno com o Desenho</h3>
            <div class="small text-muted mt-1">O RI vale para a obra inteira: enquanto o Desenho não conclui, todas as Ordens ficam travadas e destravam sozinhas no retorno.</div>
        </div>
        @if ($activeRi)
            @can('closure.manage')
                <button type="button" class="btn btn-outline-warning btn-sm" wire:click="$emit('closureRiCancelOpen', {{ $activeRi->id }})"><i class="ri-lock-unlock-line"></i> Cancelar RI…</button>
            @endcan
        @elseif ($canSendRi)
            <button type="button" class="btn btn-danger btn-sm fw-semibold" wire:click="$emit('closureRiOpen', {{ $noteId }})"><i class="ri-draft-line"></i> Enviar RI ao Desenho</button>
        @endif
    </div>

    @forelse ($returns as $item)
        @php
            $ri    = $item['ri'];
            $stage = $item['stage'];
            $steps = [
                ['label' => 'RI enviado ao Desenho', 'at' => $ri->sent_at, 'by' => $ri->SentBy?->name, 'tone' => 'red', 'note' => $ri->message],
            ];
            if ($item['responsible']) {
                $steps[] = ['label' => 'Desenho assumiu o retorno', 'by' => $item['responsible'] . ($item['company'] ? ' · ' . $item['company'] : ''), 'tone' => 'blue', 'note' => $item['desenho_step'] ? 'Situação no Desenho: ' . $item['desenho_step'] : null];
            }
            foreach (array_slice($item['comments'], 1) as $comment) {
                $steps[] = ['label' => 'Comentário no retorno', 'at' => $comment['at'], 'by' => $comment['author'], 'tone' => 'muted', 'note' => $comment['message']];
            }
            if ($ri->status === 'RETURNED') {
                $steps[] = ['label' => 'Desenho concluiu — Ordens destravadas', 'at' => $ri->returned_at, 'tone' => 'green', 'duration' => $ri->sent_at && $ri->returned_at ? $ri->sent_at->diffForHumans($ri->returned_at, true) : null];
            } elseif ($ri->status === 'CANCELLED') {
                $steps[] = ['label' => 'RI cancelado pela gestão', 'at' => $ri->cancelled_at, 'by' => $ri->CancelledBy?->name, 'tone' => 'amber', 'note' => $ri->cancel_reason];
            }
        @endphp
        <div class="p-3 border-bottom" wire:key="ri-{{ $ri->id }}">
            <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                <strong>RI #{{ $ri->cycle }}</strong>
                <x-closure.status-badge :label="$stage['label']" :tone="$stage['tone']" />
                @if ($item['category'])
                    <span class="od-chip">{{ $item['category'] }}@if ($item['subcategory']) · {{ $item['subcategory'] }}@endif</span>
                @endif
                <span class="small text-muted ms-auto">{{ $stage['hint'] }}</span>
            </div>
            <x-closure.timeline :items="$steps" />
        </div>
    @empty
        <x-closure.empty-state class="py-5" icon="ri-draft-line" title="Nenhum RI nesta obra" text="Quando um RI for enviado ao Desenho, a situação e o retorno aparecem aqui." />
    @endforelse
</div>
