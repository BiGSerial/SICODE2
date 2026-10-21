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

    $groupLabel = function ($date) {
        $date = \Carbon\Carbon::parse($date);

        return match (true) {
            $date->isToday()                          => 'Hoje',
            $date->isYesterday()                      => 'Ontem',
            $date->gte(now()->subDays(7)->startOfDay()) => 'Últimos 7 dias',
            $date->gte(now()->subDays(30)->startOfDay()) => 'Últimos 30 dias',
            default                                   => 'Mais antigas',
        };
    };
    $groups = $notifies->getCollection()->groupBy(fn ($n) => $groupLabel($n->created_at));
@endphp

<div wire:poll.5s="pollForToasts">
<style>
    #notificationsModal .modal-content { border: none; border-radius: 1rem; overflow: hidden; box-shadow: 0 24px 64px rgba(15,23,42,.35); }
    #notificationsModal .modal-header { background: linear-gradient(135deg, #1b2736, #243447); border-bottom: none; padding: .9rem 1.25rem; }
    #notificationsModal .modal-title { font-size: .95rem; font-weight: 700; color: #fff; display: flex; align-items: center; gap: .55rem; }
    #notificationsModal .modal-title .nm-count { font-size: .7rem; font-weight: 700; background: rgba(255,255,255,.14); border-radius: 999px; padding: .15rem .6rem; }
    #notificationsModal .modal-body { background: #f4f6fa; padding: 0; }
    #notificationsModal .modal-footer { background: #fff; border-top: 1px solid #e5e9f0; padding: .55rem 1.1rem; justify-content: space-between; }

    /* Barra fixa: filtro (segmentado) + ações */
    .nm-bar { position: sticky; top: 0; z-index: 3; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: .6rem; padding: .7rem 1.1rem; background: rgba(255,255,255,.96); backdrop-filter: blur(6px); border-bottom: 1px solid #e5e9f0; }
    .nm-seg { display: inline-flex; background: #eef1f6; border-radius: 999px; padding: 3px; }
    .nm-seg button { border: 0; background: transparent; border-radius: 999px; padding: .3rem .9rem; font-size: .78rem; font-weight: 600; color: #64748b; transition: all .15s; display: inline-flex; align-items: center; gap: .4rem; }
    .nm-seg button:hover { color: #1e293b; }
    .nm-seg button.is-on { background: #fff; color: #1b2736; box-shadow: 0 1px 4px rgba(15,23,42,.14); }
    .nm-seg .nm-pill { background: #ef4444; color: #fff; border-radius: 999px; font-size: .64rem; padding: 0 .4rem; line-height: 1.35; }
    .nm-actions { display: flex; align-items: center; gap: .25rem; }
    .nm-link { border: 0; background: transparent; font-size: .76rem; font-weight: 600; padding: .3rem .6rem; border-radius: 8px; color: #475569; display: inline-flex; align-items: center; gap: .35rem; transition: background .15s; }
    .nm-link:hover { background: #eef1f6; color: #1b2736; }
    .nm-link:disabled { opacity: .4; pointer-events: none; }
    .nm-link--danger { color: #dc2626; }
    .nm-link--danger:hover { background: #fef2f2; color: #b91c1c; }

    /* Lista agrupada por data */
    .nm-list { padding: .4rem 1.1rem 1rem; }
    .nm-group-title { position: sticky; top: 53px; z-index: 2; margin: 0 -1.1rem; padding: .85rem 1.1rem .35rem; background: linear-gradient(#f4f6fa 70%, rgba(244,246,250,0)); font-size: .66rem; font-weight: 800; letter-spacing: .1em; text-transform: uppercase; color: #8492a6; }
    .nm-item { position: relative; display: flex; gap: .85rem; align-items: flex-start; padding: .8rem .95rem; margin-bottom: .45rem; background: #fff; border: 1px solid #e5e9f0; border-radius: .75rem; transition: box-shadow .15s, transform .15s, border-color .15s; cursor: default; }
    .nm-item:hover { box-shadow: 0 6px 18px rgba(15,23,42,.08); border-color: #d5dbe6; }
    .nm-item.is-unread { border-color: #cdd7ff; background: linear-gradient(90deg, #f5f7ff, #fff 40%); cursor: pointer; }
    .nm-dot { position: absolute; left: -4px; top: 50%; width: 8px; height: 8px; margin-top: -4px; border-radius: 50%; background: #263cc8; box-shadow: 0 0 0 3px rgba(38,60,200,.18); }
    .nm-ico { flex: 0 0 2.5rem; height: 2.5rem; border-radius: .7rem; display: grid; place-items: center; font-size: 1.05rem; }
    .nm-main { flex: 1; min-width: 0; }
    .nm-top { display: flex; justify-content: space-between; gap: .75rem; align-items: baseline; }
    .nm-title { font-size: .86rem; font-weight: 500; color: #475569; line-height: 1.3; }
    .is-unread .nm-title { font-weight: 700; color: #111c33; }
    .nm-time { font-size: .7rem; color: #94a3b8; white-space: nowrap; display: inline-flex; align-items: center; gap: .25rem; }
    .nm-msg { font-size: .8rem; line-height: 1.5; color: #64748b; margin-top: .15rem; display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden; }
    .is-unread .nm-msg { color: #334155; }
    .nm-foot { display: flex; align-items: center; gap: .4rem; margin-top: .6rem; }
    .nm-btn { border: 1px solid #d5dbe6; background: #fff; color: #1b2736; border-radius: 8px; padding: .25rem .75rem; font-size: .76rem; font-weight: 600; display: inline-flex; align-items: center; gap: .35rem; transition: all .15s; }
    .nm-btn:hover { background: #1b2736; border-color: #1b2736; color: #fff; }
    .is-unread .nm-btn--primary { background: #263cc8; border-color: #263cc8; color: #fff; }
    .is-unread .nm-btn--primary:hover { background: #1d2fa3; border-color: #1d2fa3; }
    .nm-btn--ghost { border-color: transparent; background: transparent; color: #94a3b8; padding: .25rem .5rem; }
    .nm-btn--ghost:hover { background: #fef2f2; border-color: transparent; color: #dc2626; }

    .nm-empty { display: flex; flex-direction: column; align-items: center; gap: .5rem; padding: 4rem 1rem; color: #94a3b8; text-align: center; }
    .nm-empty i { font-size: 2.8rem; opacity: .45; }
    .nm-empty strong { color: #475569; font-size: .95rem; }
    .nm-pager { padding: .25rem 0 0; }
    @media (max-width: 576px) { .nm-time { display: none; } .nm-item { padding: .7rem; } }
</style>
    <div class="modal fade" id="notificationsModal" tabindex="-1" aria-hidden="true" wire:ignore.self>
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">

                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bi bi-bell"></i> Central de notificações
                        <span class="nm-count">{{ $notifies->total() }}</span>
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>

                <div class="modal-body">
                    <div class="nm-bar">
                        <div class="nm-seg" role="tablist">
                            <button type="button" class="{{ !$onlyUnread ? 'is-on' : '' }}" wire:click.stop="$set('onlyUnread', false)">Todas</button>
                            <button type="button" class="{{ $onlyUnread ? 'is-on' : '' }}" wire:click.stop="$set('onlyUnread', true)">
                                Não lidas @if ($unreadTotal)<span class="nm-pill">{{ $unreadTotal }}</span>@endif
                            </button>
                        </div>
                        <div class="nm-actions">
                            <button type="button" class="nm-link" wire:click.stop="recognize_all" @disabled(!$unreadTotal)>
                                <i class="bi bi-check2-all"></i> Marcar todas como lidas
                            </button>
                            <button type="button" class="nm-link nm-link--danger" wire:click.stop="delete_all" @disabled(!$notifies->total())
                                    onclick="return confirm('Apagar TODAS as notificações?') || event.stopImmediatePropagation()">
                                <i class="bi bi-trash"></i> Apagar todas
                            </button>
                        </div>
                    </div>

                    <div class="nm-list">
                        @forelse ($groups as $label => $items)
                            <div class="nm-group-title">{{ $label }}</div>
                            @foreach ($items as $notify)
                                @php
                                    $payload  = \App\Support\Notifications\UserNotificationData::fromArray($notify->data);
                                    $status   = \App\Helpers\NotifyStatus::getStatus($payload->status());
                                    $isUnread = is_null($notify->read_at);
                                    $iconBg   = $statusBgColor[$status->color ?? ''] ?? 'rgba(108,117,125,0.10)';
                                    $created  = \Carbon\Carbon::parse($notify->created_at);
                                @endphp
                                <div x-data="{ wasUnread: {{ $isUnread ? 'true' : 'false' }} }"
                                     @click="if (wasUnread) { $wire.markAsRead('{{ $notify->id }}'); wasUnread = false; }"
                                     class="nm-item {{ $isUnread ? 'is-unread' : '' }}"
                                     wire:key="item-{{ $notify->id }}">
                                    @if ($isUnread)<span class="nm-dot" title="Não lida"></span>@endif
                                    <div class="nm-ico" style="background: {{ $iconBg }};">
                                        <i class="{{ $status->icon ?? 'bi bi-info-circle' }} {{ $status->color ?? 'text-secondary' }}"></i>
                                    </div>
                                    <div class="nm-main">
                                        <div class="nm-top">
                                            <span class="nm-title">{{ $payload->title() }}</span>
                                            <span class="nm-time" title="{{ $created->format('d/m/Y H:i') }}"><i class="bi bi-clock"></i>{{ $created->diffForHumans() }}</span>
                                        </div>
                                        <div class="nm-msg">{!! $payload->message() !!}</div>
                                        <div class="nm-foot">
                                            <button type="button" class="nm-btn nm-btn--primary" wire:click.stop="open('{{ $notify->id }}')">
                                                <i class="{{ $payload->actionIcon() ?: ($isUnread ? 'bi bi-check2' : 'bi bi-eye') }}"></i> {{ $payload->actionLabel() }}
                                            </button>
                                            <button type="button" class="nm-btn nm-btn--ghost ms-auto" title="Apagar" aria-label="Apagar notificação"
                                                    wire:click.stop="delete('{{ $notify->id }}')"
                                                    onclick="return confirm('Apagar esta notificação?') || event.stopImmediatePropagation()">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        @empty
                            <div class="nm-empty">
                                <i class="bi {{ $onlyUnread ? 'bi-check2-circle' : 'bi-bell-slash' }}"></i>
                                <strong>{{ $onlyUnread ? 'Tudo em dia!' : 'Sem notificações' }}</strong>
                                <span class="small">{{ $onlyUnread ? 'Você não tem notificações não lidas.' : 'Quando algo novo chegar, aparece aqui.' }}</span>
                            </div>
                        @endforelse

                        <div class="nm-pager">{{ $notifies->onEachSide(1)->links() }}</div>
                    </div>
                </div>

                <div class="modal-footer">
                    <span class="small text-muted">Clique em uma notificação não lida para marcá-la como lida.</span>
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Fechar</button>
                </div>
            </div>
        </div>
    </div>
</div>
