<div>
    <div class="modal fade" id="notificationsModal" tabindex="-1" aria-hidden="true" wire:ignore.self>
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content edp-bg-gray">
                <div class="modal-header edp-bg-sprucegreen-100 edp-text-verde">
                    <h5 class="modal-title text-white">Todas as Notificações</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Fechar"></button>
                </div>

                <div class="modal-body">
                    {{-- Ações em massa + contadores/legenda --}}
                    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                        <div class="d-flex align-items-center gap-2">
                            <button class="btn btn-sm btn-outline-success" wire:click="recognize_all">
                                <i class="bi bi-check2-all"></i> Marcar todas como lidas
                            </button>
                            <button class="btn btn-sm btn-outline-danger" wire:click="delete_all"
                                onclick="return confirm('Tem certeza que deseja apagar TODAS as notificações?') || event.stopImmediatePropagation()">
                                <i class="bi bi-trash"></i> Apagar todas
                            </button>
                        </div>

                        @php
                            $unreadOnPage = $unreadTotal;
                        @endphp
                        <div class="d-flex align-items-center gap-3 text-muted small">
                            <span>Total: <strong>{{ $notifies->total() }}</strong></span>
                            <span>Não lidas: <strong>{{ $unreadOnPage }}</strong></span>
                            <span class="d-none d-md-inline">|</span>
                            <span><i class="bi bi-envelope me-1"></i>Não lida</span>
                            <span><i class="bi bi-envelope-open me-1"></i>Lida</span>
                        </div>
                    </div>

                    {{-- Lista paginada --}}
                    @forelse ($notifies as $notify)
                        @php
                            $status = \App\Helpers\NotifyStatus::getStatus($notify->data['status'] ?? null);
                            $isUnread = is_null($notify->read_at);
                            $badgeText = $isUnread ? 'Não lida' : 'Lida';
                            $badgeCls = $isUnread ? 'bg-danger' : 'bg-secondary';
                            $envIcon = $isUnread ? 'bi bi-envelope' : 'bi bi-envelope-open';
                            $cardStyle = $isUnread ? '' : 'background-color: #f6f6f6';
                            $cardBorder = $isUnread ? 'border' : 'border-0';
                        @endphp

                        <div class="card mb-2 {{ $cardBorder }}" style="{{ $cardStyle }}"
                            wire:key="item-{{ $notify->id }}">
                            <div class="card-body d-flex">
                                <div class="me-3">
                                    <i class="{{ $status->icon ?? '' }} {{ $status->color ?? '' }}"></i>
                                </div>

                                <div class="flex-grow-1">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div class="d-flex align-items-center gap-2">
                                            <h6 class="fw-bold mb-0">
                                                {{ $notify->data['titulo'] ?? '-' }}
                                            </h6>
                                            <span class="badge {{ $badgeCls }}">{{ $badgeText }}</span>
                                            <span class="badge {{ $badgeCls }}">{{ $notify->id }}</span>
                                        </div>
                                        <small
                                            class="text-muted">{{ \Carbon\Carbon::parse($notify->created_at)->diffForHumans() }}</small>
                                    </div>

                                    <div class="mt-2 mb-2">{!! $notify->data['mensagem'] ?? '-' !!}</div>

                                    <div class="d-flex gap-2">
                                        <button
                                            class="btn btn-sm {{ $isUnread ? 'btn-success' : 'btn-outline-success' }}"
                                            wire:click="open('{{ $notify->id }}')">
                                            <i class="{{ $envIcon }}"></i> Ler
                                        </button>

                                        <button class="btn btn-sm btn-outline-danger"
                                            wire:click="delete('{{ $notify->id }}')"
                                            onclick="return confirm('Apagar esta notificação?') || event.stopImmediatePropagation()">
                                            <i class="bi bi-trash"></i> Apagar
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="alert alert-info mb-0">Sem notificações.</div>
                    @endforelse

                    <div class="mt-3">
                        {{-- paginação isolada (nome notifyPage) com bootstrap --}}
                        {{ $notifies->onEachSide(1)->links() }}
                    </div>
                </div>

                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                </div>
            </div>
        </div>
    </div>
</div>
