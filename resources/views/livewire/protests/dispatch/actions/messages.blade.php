@push('css')
    <style>
        .messages-modal-header {
            background: linear-gradient(135deg, #2563eb 0%, #7c3aed 100%);
            color: #fff;
            border-radius: 18px 18px 0 0;
            padding: 1.5rem 1.75rem;
        }

        .messages-modal-body {
            background: #f8f9fb;
        }

        .chat-thread {
            max-height: 420px;
            overflow-y: auto;
            padding-right: .5rem;
        }

        .chat-message {
            display: flex;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .chat-message.is-mine {
            flex-direction: row-reverse;
            text-align: right;
        }

        .chat-message.is-mine .message-bubble {
            background: rgba(59, 130, 246, 0.15);
            border-color: rgba(59, 130, 246, 0.2);
        }

        .avatar-circle {
            --avatar-size: 50px;
            width: var(--avatar-size);
            height: var(--avatar-size);
            min-width: var(--avatar-size);
            min-height: var(--avatar-size);
            border-radius: 50%;
            overflow: hidden;
            border: 2px solid #fff;
            box-shadow: 0 4px 10px rgba(15, 23, 42, 0.15);
            background: #f1f5f9;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .avatar-circle img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        .message-bubble {
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            padding: 1rem;
            background: #fff;
            min-width: 0;
        }

        .message-meta {
            font-size: .85rem;
        }

        .message-input textarea {
            min-height: 120px;
            resize: vertical;
        }
    </style>
@endpush

<div wire:ignore.self class="modal fade" id="medProtestMessagesModal" tabindex="-1"
    aria-labelledby="medProtestMessagesLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content overflow-hidden">

            <div class="messages-modal-header d-flex justify-content-between align-items-start">
                <div>
                    <h5 class="mb-1 fw-bold" id="medProtestMessagesLabel">
                        Mensagens da Medida
                    </h5>
                    @if ($medProtest)
                        <div class="small text-white-75">
                            Reclamação #{{ $medProtest->protest?->nota }} • Medida #{{ $medProtest->med_id }}
                        </div>
                    @else
                        <div class="small text-white-75">
                            Nenhuma medida selecionada
                        </div>
                    @endif
                </div>

                <button type="button" class="btn btn-outline-light btn-sm" wire:click="closeModal">
                    <i class="ri-close-line me-1"></i>
                    Fechar
                </button>
            </div>

            <div class="messages-modal-body p-4">
                @if ($medProtest)
                    <div class="chat-thread mb-4" id="medMessagesThread">
                        @forelse ($medProtest->Comments?->sortBy('created_at') as $comment)
                            @php
                                $isMine = $comment->user_id === auth()->id();
                                $user = $comment->user;
                            @endphp

                            <div class="chat-message {{ $isMine ? 'is-mine' : '' }}">
                                <div class="avatar-circle" title="{{ $user?->name }}">
                                    @if ($user?->avatar_url)
                                        <img src="{{ $user->avatar_url }}" alt="Avatar de {{ $user->name }}">
                                    @else
                                        <span class="fw-semibold text-secondary">
                                            {{ mb_substr($user?->name ?? '?', 0, 1) }}
                                        </span>
                                    @endif
                                </div>

                                <div class="flex-grow-1">
                                    <div class="d-flex {{ $isMine ? 'justify-content-end' : 'justify-content-between' }} gap-2 align-items-center message-meta mb-1">
                                        <div class="d-flex align-items-center gap-2 {{ $isMine ? 'flex-row-reverse' : '' }}">
                                            <span class="fw-semibold {{ $isMine ? 'text-primary' : 'text-dark' }}">
                                                {{ $user?->name ?? 'Usuário' }}
                                            </span>

                                            @if ($user?->email)
                                                <button class="btn btn-sm btn-outline-primary p-1"
                                                    onclick="window.open('msteams://teams.microsoft.com/l/chat/0/0?users={{ $user->email }}', '_blank')"
                                                    title="Abrir chat no Teams">
                                                    <i class="bx bxl-microsoft-teams fs-6"></i>
                                                </button>
                                            @endif
                                        </div>

                                        <small class="text-muted d-flex align-items-center gap-1">
                                            <i class="ri-time-line"></i>
                                            {{ $comment->created_at?->diffForHumans() }}
                                        </small>
                                    </div>

                                    <div class="message-bubble">
                                        <p class="mb-0 text-dark">{{ $comment->message }}</p>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="text-center text-muted py-5">
                                <i class="ri-chat-3-line fs-1 d-block mb-2"></i>
                                Ainda não há mensagens nesta medida.
                            </div>
                        @endforelse
                    </div>

                    <div class="message-input bg-white p-3 rounded-3 shadow-sm">
                        <div class="form-floating">
                            <textarea class="form-control @error('message') is-invalid @enderror" placeholder="Digite sua mensagem" id="newMessageInput"
                                wire:model.defer="message"></textarea>
                            <label for="newMessageInput">Nova mensagem</label>
                            @error('message')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="d-flex justify-content-end mt-3">
                            <button type="button" class="btn btn-primary px-4" wire:click="sendMessage"
                                wire:loading.attr="disabled">
                                <span wire:loading.remove>
                                    <i class="ri-send-plane-2-line me-1"></i>
                                    Enviar
                                </span>
                                <span wire:loading>
                                    <span class="spinner-border spinner-border-sm me-1" role="status"></span>
                                    Enviando...
                                </span>
                            </button>
                        </div>
                    </div>
                @else
                    <div class="text-center text-muted py-5">
                        <i class="ri-information-line fs-1 d-block mb-2"></i>
                        Nenhuma medida selecionada. Selecione uma linha da lista para visualizar as mensagens.
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

@push('scripts')
    <script>
        document.addEventListener('livewire:load', () => {
            const scrollThreadToBottom = () => {
                const thread = document.getElementById('medMessagesThread');
                if (thread) {
                    thread.scrollTop = thread.scrollHeight;
                }
            };

            Livewire.hook('message.processed', (message, component) => {
                if (component.fingerprint.name === 'protests.dispatch.actions.messages') {
                    scrollThreadToBottom();
                }
            });

            window.addEventListener('scrollMessagesThread', scrollThreadToBottom);

            scrollThreadToBottom();
        });
    </script>
@endpush
