<div wire:poll.8s id="quality-chat">
    @php use App\Support\QualityUi; @endphp
    <div class="small text-muted mb-2"><i class="ri-lock-line"></i> Conversa entre N1, N2 e Gestão. Mensagens <strong>internas</strong> não aparecem para o usuário da atividade.</div>

    <div class="chat-box" id="quality-chat-box">
        @if ($total > $messages->count())
            <div class="text-center mb-2"><button type="button" wire:click="loadOlder" class="btn btn-link btn-sm">Ver mensagens anteriores ({{ $total - $messages->count() }})</button></div>
        @endif

        @forelse ($messages as $message)
            @php
                $mine  = $message->actor_id === auth()->id();
                $name  = $message->Actor?->name ?? 'Sistema';
                $role  = ['DESIGNER' => 'Usuário', 'N1' => 'N1', 'N2' => 'N2'][$message->actor_role] ?? $message->actor_role;
                $shared = ($message->payload['visibility'] ?? 'INTERNAL') === 'ALL';
                $previous = $loop->index > 0 ? $messages[$loop->index - 1] : null;
                $newDay   = !$previous || $previous->created_at->format('Y-m-d') !== $message->created_at->format('Y-m-d');
            @endphp
            @if ($newDay)<div class="tl-day"><span>{{ $message->created_at->isToday() ? 'Hoje' : ($message->created_at->isYesterday() ? 'Ontem' : $message->created_at->format('d/m/Y')) }}</span></div>@endif
            <div class="chat-row {{ $mine ? 'is-mine' : '' }}" wire:key="msg-{{ $message->id }}">
                @unless ($mine)<div class="chat-avatar" style="background: {{ QualityUi::avatarTone($name) }}" title="{{ $name }}">{{ QualityUi::initials($name) }}</div>@endunless
                <div class="chat-bubble">
                    <div class="chat-head"><strong>{{ $mine ? 'Você' : $name }}</strong> <span class="chip">{{ $role }}</span>@if ($shared)<span class="chip chip-where" title="Visível ao usuário da atividade"><i class="ri-eye-line"></i> visível ao usuário</span>@endif</div>
                    <div class="chat-text">{!! nl2br(e($message->observation)) !!}</div>
                    <div class="chat-time">{{ $message->created_at->format('H:i') }}</div>
                </div>
                @if ($mine)<div class="chat-avatar" style="background: {{ QualityUi::avatarTone($name) }}" title="{{ $name }}">{{ QualityUi::initials($name) }}</div>@endif
            </div>
        @empty
            <div class="ob-empty"><i class="ri-chat-3-line"></i><div class="fw-bold">Nenhuma mensagem ainda.</div><div class="small">Use este espaço para alinhar detalhes desta obra entre N1 e N2.</div></div>
        @endforelse
    </div>

    @if ($canPost)
        <form wire:submit.prevent="send" class="chat-composer">
            <textarea wire:model.defer="text" rows="2" class="form-control @error('text') is-invalid @enderror" placeholder="Escreva uma mensagem…" wire:keydown.ctrl.enter="send"></textarea>
            @error('text')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mt-2">
                <select wire:model="visibility" class="form-select form-select-sm" style="max-width: 280px;">
                    <option value="INTERNAL">🔒 Interno (N1, N2 e Gestão)</option>
                    <option value="ALL">👁 Também visível ao usuário</option>
                </select>
                <div class="d-flex align-items-center gap-2"><span class="small text-muted d-none d-md-inline">Ctrl + Enter envia</span><button type="submit" class="btn btn-primary btn-sm btn-dash" wire:loading.attr="disabled" wire:target="send"><i class="ri-send-plane-fill"></i> Enviar</button></div>
            </div>
        </form>
    @endif

    <script>
        (function () {
            const box = document.getElementById('quality-chat-box');
            if (!box) return;
            const down = () => { box.scrollTop = box.scrollHeight; };
            down();
            window.addEventListener('quality-chat-sent', () => setTimeout(down, 60));
            document.addEventListener('shown.bs.tab', e => { if (e.target.dataset.bsTarget === '#q-discussao') down(); });
        })();
    </script>
</div>
