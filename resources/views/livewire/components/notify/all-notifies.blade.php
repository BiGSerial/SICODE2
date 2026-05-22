@php
    $statusBorderColor = [
        'text-danger'    => '#dc3545',
        'text-success'   => '#198754',
        'text-warning'   => '#e6a817',
        'text-primary'   => '#263CC8',
        'text-info'      => '#0dcaf0',
        'text-secondary' => '#6c757d',
    ];
    $statusBgColor = [
        'text-danger'    => 'rgba(220,53,69,0.10)',
        'text-success'   => 'rgba(25,135,84,0.10)',
        'text-warning'   => 'rgba(255,193,7,0.13)',
        'text-primary'   => 'rgba(38,60,200,0.10)',
        'text-info'      => 'rgba(13,202,240,0.12)',
        'text-secondary' => 'rgba(108,117,125,0.10)',
    ];
@endphp

<div>
<style>
    /* ── Modal ─────────────────────────────────────────────── */
    #notificationsModal .modal-content {
        border: none; border-radius: 0.6rem; overflow: hidden;
        box-shadow: 0 16px 48px rgba(33,46,62,0.28);
    }
    #notificationsModal .modal-header {
        background: #212E3E; border-bottom: none; padding: 0.75rem 1.25rem;
    }
    #notificationsModal .modal-header .modal-title {
        font-size: 0.88rem; font-weight: 700;
        letter-spacing: 0.12em; text-transform: uppercase; color: #fff;
    }
    #notificationsModal .modal-body {
        background: #eef2f7; padding: 1rem 1.1rem;
    }
    #notificationsModal .modal-footer {
        background: #eef2f7; border-top: 1px solid #dde3ec; padding: 0.6rem 1.1rem;
    }

    /* ── Barra de ações ─────────────────────────────────────── */
    .notif-modal-toolbar {
        display: flex; align-items: center; justify-content: space-between;
        flex-wrap: wrap; gap: 0.6rem;
        padding: 0.6rem 0.85rem;
        background: #fff; border: 1px solid #dde3ec; border-radius: 0.45rem;
        margin-bottom: 0.85rem;
    }
    .notif-modal-stats {
        display: flex; align-items: center; gap: 0.5rem;
        font-size: 0.78rem; color: #64748b;
    }
    .notif-modal-stats strong { color: #212E3E; }
    .notif-stat-unread {
        display: inline-flex; align-items: center; gap: 0.3rem;
        background: #fef2f2; color: #dc3545;
        border: 1px solid #fca5a5; border-radius: 100px;
        padding: 0.1rem 0.55rem; font-size: 0.72rem; font-weight: 700;
    }

    /* ════════════════════════════════════════════════════════════
       CARD NÃO LIDO
       Fundo branco com sombra + borda esquerda colorida (por status)
       + título bold + badge "NÃO LIDA" vermelho
    ════════════════════════════════════════════════════════════ */
    .notif-card--unread {
        background: #ffffff;
        border: 1px solid #dde3ec;
        border-radius: 0.45rem;
        margin-bottom: 0.5rem;
        overflow: hidden;
        box-shadow: 0 2px 10px rgba(38,60,200,0.10);
        transition: box-shadow 0.12s ease;
    }
    .notif-card--unread:hover {
        box-shadow: 0 4px 16px rgba(38,60,200,0.18);
    }
    .notif-card--unread .notif-card-title {
        font-size: 0.86rem; font-weight: 700; color: #1a2540;
    }
    .notif-card--unread .notif-card-msg { color: #374151; }

    /* ════════════════════════════════════════════════════════════
       CARD LIDO
       Fundo cinza lavado + borda neutra + sem sombra + texto muted
    ════════════════════════════════════════════════════════════ */
    .notif-card--read {
        background: #f0f2f5;
        border: 1px solid #d8dce4;
        border-left-width: 4px !important;
        border-radius: 0.45rem;
        margin-bottom: 0.5rem;
        overflow: hidden;
        opacity: 0.78;
        transition: opacity 0.12s ease;
    }
    .notif-card--read:hover { opacity: 1; }
    .notif-card--read .notif-card-title {
        font-size: 0.85rem; font-weight: 400; color: #64748b;
    }
    .notif-card--read .notif-card-msg { color: #94a3b8; }
    .notif-card--read .notif-card-icon { opacity: 0.5; }

    /* ── Corpo do card ──────────────────────────────────────── */
    .notif-card-body {
        display: flex; align-items: flex-start; gap: 0.75rem;
        padding: 0.75rem 0.9rem;
    }
    .notif-card-icon {
        flex-shrink: 0; width: 2.4rem; height: 2.4rem;
        border-radius: 50%; display: flex; align-items: center; justify-content: center;
        font-size: 1rem; margin-top: 0.05rem;
    }
    .notif-card-content { flex: 1; min-width: 0; }
    .notif-card-top {
        display: flex; align-items: flex-start; justify-content: space-between;
        gap: 0.5rem; margin-bottom: 0.25rem;
    }
    .notif-card-title { line-height: 1.3; }
    .notif-card-time {
        font-size: 0.7rem; color: #94a3b8; white-space: nowrap; flex-shrink: 0;
    }
    .notif-card-msg {
        font-size: 0.8rem; line-height: 1.5; margin-bottom: 0.55rem;
    }

    /* Badges lida / não lida */
    .notif-read-badge {
        display: inline-flex; align-items: center; gap: 0.25rem;
        font-size: 0.67rem; font-weight: 700;
        border-radius: 100px; padding: 0.12rem 0.55rem;
        text-transform: uppercase; letter-spacing: 0.04em;
    }
    .notif-read-badge--unread {
        background: #fef2f2; color: #dc3545; border: 1px solid #fca5a5;
    }
    .notif-read-badge--read {
        background: #f1f5f9; color: #94a3b8; border: 1px solid #cbd5e1;
    }

    /* ── Estado vazio ───────────────────────────────────────── */
    .notif-modal-empty {
        display: flex; flex-direction: column; align-items: center; gap: 0.6rem;
        padding: 3rem 1rem; color: #94a3b8; text-align: center;
    }
    .notif-modal-empty i { font-size: 2.5rem; opacity: 0.4; }
    .notif-modal-empty p { font-size: 0.88rem; margin: 0; }
</style>
    <div class="modal fade" id="notificationsModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">

                {{-- Header navy --}}
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bi bi-bell me-2"></i> Todas as Notificações
                    </h5>
                    <button type="button" class="btn-close btn-close-white"
                            data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>

                <div class="modal-body">

                    {{-- Toolbar --}}
                    <div class="notif-modal-toolbar">
                        <div class="d-flex align-items-center gap-2">
                            <button class="btn btn-sm btn-outline-success" wire:click.stop="recognize_all">
                                <i class="bi bi-check2-all me-1"></i> Marcar todas como lidas
                            </button>
                            <button class="btn btn-sm btn-outline-danger" wire:click.stop="delete_all"
                                    onclick="return confirm('Apagar TODAS as notificações?') || event.stopImmediatePropagation()">
                                <i class="bi bi-trash me-1"></i> Apagar todas
                            </button>
                        </div>
                        <div class="notif-modal-stats">
                            <span>Total: <strong>{{ $notifies->total() }}</strong></span>
                            @if ($unreadTotal)
                                <span class="notif-stat-unread">
                                    <i class="bi bi-envelope"></i>
                                    {{ $unreadTotal }} não lida{{ $unreadTotal > 1 ? 's' : '' }}
                                </span>
                            @endif
                        </div>
                    </div>

                    {{-- Lista --}}
                    @forelse ($notifies as $notify)
                        @php
                            $payload     = \App\Support\Notifications\UserNotificationData::fromArray($notify->data);
                            $status      = \App\Helpers\NotifyStatus::getStatus($payload->status());
                            $isUnread    = is_null($notify->read_at);
                            $borderColor = $statusBorderColor[$status->color ?? ''] ?? '#6c757d';
                            $iconBg      = $statusBgColor[$status->color ?? ''] ?? 'rgba(108,117,125,0.10)';
                        @endphp

                        <div x-data="{ wasUnread: {{ $isUnread ? 'true' : 'false' }} }"
                             @click="if (wasUnread) { $wire.markAsRead('{{ $notify->id }}'); wasUnread = false; }"
                             class="{{ $isUnread ? 'notif-card--unread' : 'notif-card--read' }}"
                             style="border-left: 4px solid {{ $borderColor }};"
                             wire:key="item-{{ $notify->id }}">

                            <div class="notif-card-body">

                                {{-- Ícone de status --}}
                                <div class="notif-card-icon" style="background: {{ $iconBg }};">
                                    <i class="{{ $status->icon ?? 'bi bi-info-circle' }} {{ $status->color ?? 'text-secondary' }}"></i>
                                </div>

                                <div class="notif-card-content">

                                    {{-- Título + badges + hora --}}
                                    <div class="notif-card-top">
                                        <div class="d-flex align-items-center gap-2 flex-wrap">
                                            <span class="notif-card-title">{{ $payload->title() }}</span>
                                            <span class="notif-read-badge {{ $isUnread ? 'notif-read-badge--unread' : 'notif-read-badge--read' }}">
                                                <i class="bi {{ $isUnread ? 'bi-envelope-fill' : 'bi-envelope-open' }}"></i>
                                                {{ $isUnread ? 'Não lida' : 'Lida' }}
                                            </span>
                                        </div>
                                        <span class="notif-card-time">
                                            <i class="bi bi-clock me-1"></i>
                                            {{ \Carbon\Carbon::parse($notify->created_at)->diffForHumans() }}
                                        </span>
                                    </div>

                                    {{-- Mensagem --}}
                                    <div class="notif-card-msg">{!! $payload->message() !!}</div>

                                    {{-- Ações --}}
                                    <div class="d-flex align-items-center gap-2 flex-wrap">
                                        <button class="btn btn-sm {{ $isUnread ? 'btn-success' : 'btn-outline-secondary' }}"
                                                wire:click.stop="open('{{ $notify->id }}')">
                                            <i class="{{ $payload->actionIcon() ?: ($isUnread ? 'bi bi-check2' : 'bi bi-eye') }} me-1"></i>
                                            {{ $payload->actionLabel() }}
                                        </button>
                                        <button class="btn btn-sm btn-outline-danger"
                                                wire:click.stop="delete('{{ $notify->id }}')"
                                                onclick="return confirm('Apagar esta notificação?') || event.stopImmediatePropagation()">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                    @empty
                        <div class="notif-modal-empty">
                            <i class="bi bi-bell-slash"></i>
                            <p>Sem notificações no momento.</p>
                        </div>
                    @endforelse

                    <div class="mt-3">{{ $notifies->onEachSide(1)->links() }}</div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-secondary"
                            data-bs-dismiss="modal">Fechar</button>
                </div>
            </div>
        </div>
    </div>
</div>
