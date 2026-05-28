@php
    use Carbon\Carbon;
    use App\Helpers\NotifyStatus;
    use App\Support\Notifications\UserNotificationData;
    use Illuminate\Support\Str;

    $unreadCount = $notifies->whereNull('read_at')->count();

    $statusBgCls = [
        'text-danger'    => 'notif-s--danger',
        'text-success'   => 'notif-s--success',
        'text-warning'   => 'notif-s--warning',
        'text-primary'   => 'notif-s--primary',
        'text-info'      => 'notif-s--info',
        'text-secondary' => 'notif-s--secondary',
    ];
@endphp

<li class="nav-item dropdown ms-2 position-relative" id="notification-dropdown" wire:poll.8s>
    <style>
        /* ── Sino ──────────────────────────────────────────── */
        .notif-bell-icon {
            font-size: 1.15rem; color: #28FF52;
            transition: transform 0.2s ease;
        }
        #notification-dropdown:hover .notif-bell-icon { transform: rotate(-15deg); }

        .notif-bell-badge {
            position: absolute; top: 1px; right: -2px;
            min-width: 16px; height: 16px; padding: 0 3px;
            background: #e3342f; color: #fff;
            font-size: 0.6rem; font-weight: 700; border-radius: 8px;
            display: inline-flex; align-items: center; justify-content: center;
            line-height: 1; pointer-events: none;
        }

        /* ── Painel ────────────────────────────────────────── */
        .notif-dropdown {
            width: 360px !important; padding: 0 !important;
            border: 1px solid #c8d2e0 !important;
            border-radius: 0.5rem !important;
            box-shadow: 0 12px 36px rgba(33,46,62,0.24) !important;
            background: #fff !important;
        }

        /* ── Cabeçalho navy ────────────────────────────────── */
        .notif-header {
            display: flex; align-items: center; justify-content: space-between;
            background: #212E3E; color: #fff;
            padding: 0.52rem 0.85rem;
            border-radius: 0.5rem 0.5rem 0 0;
        }
        .notif-header-title {
            font-size: 0.7rem; font-weight: 700;
            letter-spacing: 0.15em; text-transform: uppercase;
        }
        .notif-badge-pill {
            font-size: 0.62rem; font-weight: 700;
            background: #28FF52; color: #212E3E;
            border-radius: 100px; padding: 0.1rem 0.55rem;
        }

        /* ── Marcar tudo ───────────────────────────────────── */
        .notif-action-bar { padding: 0.35rem 0.75rem; border-bottom: 1px solid #eef1f6; }
        .notif-markall-btn {
            width: 100%; padding: 0.28rem 0.6rem;
            background: none; border: 1px solid #263CC8; border-radius: 0.3rem;
            color: #263CC8; font-size: 0.75rem; font-weight: 600; cursor: pointer;
            transition: background 0.12s ease, color 0.12s ease; text-align: center;
        }
        .notif-markall-btn:hover { background: #263CC8; color: #fff; }

        /* ── Lista (scroll interno) ────────────────────────── */
        .notif-list {
            overflow-y: auto; max-height: 400px;
            scrollbar-width: thin; scrollbar-color: #94a3b8 #f1f5f9;
        }
        .notif-list::-webkit-scrollbar       { width: 4px; }
        .notif-list::-webkit-scrollbar-track { background: #f1f5f9; }
        .notif-list::-webkit-scrollbar-thumb { background: #94a3b8; border-radius: 4px; }

        .notif-wrapper {
            border-bottom: 1px solid #eef1f6;
            border-left: 4px solid transparent;
        }
        .notif-wrapper:last-child { border-bottom: none; }
        .notif-wrapper--unread { border-left-color: #263CC8; }
        .notif-wrapper--unread .notif-main-row { background: #eef4ff; }
        .notif-wrapper--unread .notif-main-row:hover { background: #e4edff; }
        .notif-wrapper--read { border-left-color: #dde3ec; opacity: 0.8; }
        .notif-wrapper--read .notif-main-row { background: #f5f6f8; }
        .notif-wrapper--read .notif-main-row:hover { background: #edf2fb; opacity: 1; }
        .notif-wrapper--read:hover { opacity: 1; }

        .notif-main-row {
            display: flex; align-items: flex-start; gap: 0.6rem;
            padding: 0.62rem 0.85rem;
            cursor: pointer; user-select: none;
            transition: background 0.1s ease;
        }
        .notif-icon-wrap {
            flex-shrink: 0; width: 2rem; height: 2rem;
            border-radius: 50%; display: flex; align-items: center; justify-content: center;
            font-size: 0.88rem; margin-top: 0.05rem;
        }
        .notif-s--danger   { background: rgba(220,53,69,0.13); }
        .notif-s--success  { background: rgba(25,135,84,0.13); }
        .notif-s--warning  { background: rgba(255,193,7,0.16); }
        .notif-s--primary  { background: rgba(38,60,200,0.12); }
        .notif-s--info     { background: rgba(13,202,240,0.14); }
        .notif-s--secondary{ background: rgba(108,117,125,0.12); }
        .notif-wrapper--read .notif-icon-wrap { opacity: 0.5; }
        .notif-content { flex: 1; min-width: 0; display: flex; flex-direction: column; gap: 0.1rem; }
        .notif-title {
            font-size: 0.81rem; font-weight: 700; color: #1a2540;
            display: block; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
        }
        .notif-wrapper--read .notif-title { font-weight: 400; color: #64748b; }
        .notif-msg-short {
            font-size: 0.74rem; color: #64748b;
            display: block; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
        }
        .notif-wrapper--read .notif-msg-short { color: #94a3b8; }
        .notif-time {
            font-size: 0.67rem; color: #94a3b8;
            display: flex; align-items: center; gap: 0.25rem; margin-top: 0.08rem;
        }
        .notif-right {
            display: flex; flex-direction: column; align-items: flex-end;
            gap: 0.3rem; flex-shrink: 0; padding-top: 0.05rem;
        }
        .notif-new-badge {
            font-size: 0.58rem; font-weight: 800; letter-spacing: 0.06em;
            background: #263CC8; color: #fff;
            border-radius: 3px; padding: 0.12rem 0.35rem; text-transform: uppercase;
        }
        .notif-read-mark { font-size: 0.75rem; color: #b0bec5; }
        .notif-chevron {
            font-size: 0.78rem; color: #94a3b8;
            transition: color 0.12s ease, transform 0.18s ease;
        }
        .notif-main-row:hover .notif-chevron { color: #263CC8; }
        .notif-expanded-body {
            padding: 0.55rem 0.85rem 0.65rem 3.35rem;
            border-top: 1px dashed #dde3ec;
            background: rgba(38,60,200,0.04);
        }
        .notif-wrapper--read .notif-expanded-body { background: rgba(0,0,0,0.02); }
        .notif-full-msg {
            font-size: 0.79rem; color: #374151; line-height: 1.55;
            margin-bottom: 0.6rem; word-break: break-word;
        }
        .notif-wrapper--read .notif-full-msg { color: #94a3b8; }
        .notif-action-btn {
            display: inline-flex; align-items: center; gap: 0.35rem;
            padding: 0.3rem 0.75rem;
            background: #263CC8; color: #fff;
            border: none; border-radius: 0.3rem;
            font-size: 0.76rem; font-weight: 600; cursor: pointer;
            transition: background 0.12s ease; text-decoration: none;
        }
        .notif-action-btn:hover { background: #1a2db0; color: #fff; }
        .notif-wrapper--read .notif-action-btn {
            background: none; color: #64748b; border: 1px solid #94a3b8;
        }
        .notif-wrapper--read .notif-action-btn:hover { background: #f1f5f9; color: #212E3E; }
        .notif-empty {
            display: flex; flex-direction: column; align-items: center; gap: 0.5rem;
            padding: 2.2rem 1rem; color: #94a3b8; font-size: 0.82rem;
        }
        .notif-empty i { font-size: 1.8rem; opacity: 0.5; }
        .notif-footer { padding: 0.42rem 0.85rem; border-top: 1px solid #eef1f6; text-align: center; }
        .notif-footer a {
            font-size: 0.77rem; font-weight: 600; color: #263CC8; text-decoration: none;
            transition: color 0.12s ease;
        }
        .notif-footer a:hover { color: #1a2db0; text-decoration: none; }
    </style>
    <x-show-loading target="readed" />

        {{--
            data-bs-auto-close="outside" mantém o dropdown aberto ao clicar
            dentro dele — necessário para wire:click e downloads funcionarem
            sem serem interrompidos pelo fechamento automático do Bootstrap.
        --}}
        <a class="nav-link nav-icon position-relative" href="#"
           data-bs-toggle="dropdown"
           data-bs-auto-close="outside"
           id="notification-toggle">
            <i class="bi {{ $unreadCount ? 'bi-bell-fill' : 'bi-bell' }} notif-bell-icon"></i>
            @if ($unreadCount)
                <span class="notif-bell-badge">{{ $unreadCount > 99 ? '99+' : $unreadCount }}</span>
            @endif
        </a>

        <div class="dropdown-menu dropdown-menu-end dropdown-menu-arrow notif-dropdown"
             id="notification-menu"
             @click.stop>

            {{-- Cabeçalho --}}
            <div class="notif-header">
                <span class="notif-header-title"><i class="bi bi-bell me-1"></i> Notificações</span>
                @if ($unreadCount)
                    <span class="notif-badge-pill">{{ $unreadCount }} nova{{ $unreadCount > 1 ? 's' : '' }}</span>
                @endif
            </div>

            {{-- Reconhecer tudo --}}
            @if ($unreadCount)
                <div class="notif-action-bar">
                    <button class="notif-markall-btn" wire:click.prevent="recognize_all" type="button">
                        <i class="bi bi-check2-all me-1"></i> Marcar todas como lidas
                    </button>
                </div>
            @endif

            {{-- Lista --}}
            <div class="notif-list">
                @forelse ($notifies->take($total_notifies) as $notify)
                    @php
                        $payload  = UserNotificationData::fromArray($notify->data);
                        $status   = NotifyStatus::getStatus($payload->status());
                        $isUnread = is_null($notify->read_at);
                        $msgShort = Str::limit(strip_tags($payload->message()), 60);
                        $iconBg   = $statusBgCls[$status->color ?? ''] ?? 'notif-s--secondary';
                    @endphp

                    {{--
                        Cada item é um Alpine x-data independente.
                        open = false  → linha compacta
                        open = true   → expande e mostra mensagem completa + botão de ação
                    --}}
                    <div x-data="{ open: false }"
                         wire:key="{{ $notify->id }}"
                         class="notif-wrapper {{ $isUnread ? 'notif-wrapper--unread' : 'notif-wrapper--read' }}">

                        {{-- Linha principal — clique expande/colapsa --}}
                        <div class="notif-main-row"
                             @click.stop="open = !open">
                            <span class="notif-icon-wrap {{ $iconBg }}">
                                <i class="{{ $status->icon ?? 'bi bi-info-circle' }} {{ $status->color ?? 'text-secondary' }}"></i>
                            </span>

                            <span class="notif-content">
                                <span class="notif-title">{{ $payload->title() }}</span>
                                @if ($msgShort)
                                    <span class="notif-msg-short" x-show="!open">{{ $msgShort }}</span>
                                @endif
                                <span class="notif-time">
                                    <i class="bi bi-clock"></i>
                                    {{ Carbon::parse($notify->created_at)->diffForHumans() }}
                                </span>
                            </span>

                            <span class="notif-right">
                                @if ($isUnread)
                                    <span class="notif-new-badge">nova</span>
                                @else
                                    <i class="notif-read-mark bi bi-envelope-open"></i>
                                @endif
                                <i class="notif-chevron bi"
                                   :class="open ? 'bi-chevron-up' : 'bi-chevron-down'"></i>
                            </span>
                        </div>

                        {{-- Área expandida: mensagem completa + botão de ação --}}
                        <div class="notif-expanded-body"
                             @click.stop
                             x-show="open"
                             x-transition:enter="transition ease-out duration-150"
                             x-transition:enter-start="opacity-0 -translate-y-1"
                             x-transition:enter-end="opacity-100 translate-y-0">

                            <div class="notif-full-msg">{!! $payload->message() !!}</div>

                            {{-- Botão de ação: download, link ou marcar como lida --}}
                            <button type="button"
                                    class="notif-action-btn"
                                    wire:click.stop.prevent="readed('{{ $notify->id }}')">
                                <i class="{{ $payload->actionIcon() }}"></i>
                                {{ $payload->actionLabel() }}
                            </button>
                        </div>
                    </div>
                @empty
                    <div class="notif-empty">
                        <i class="bi bi-bell-slash"></i>
                        <span>Sem notificações</span>
                    </div>
                @endforelse
            </div>

            {{-- Rodapé — Livewire.emitTo direto no JS para não depender do Bootstrap --}}
            <div class="notif-footer">
                <a href="#"
                   onclick="event.preventDefault(); Livewire.emitTo('components.notify.all-notifies', 'openNotifies');">
                    <i class="bi bi-collection me-1"></i>
                    Ver todas as notificações
                </a>
            </div>
        </div>
    </li>
