{{-- Ocupação da atividade no rodapé do menu lateral padrão. Visual em <x-nav.occupation>; atualiza a cada 15 s. --}}
<div wire:poll.15s>
    @if ($lists->count())
        <x-nav.occupation :rows="$lists" :companies="$this->company_l" :online="$online" :avatars="$avatars" />
    @endif
</div>
