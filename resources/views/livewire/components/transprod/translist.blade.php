@php
    use Carbon\Carbon;
    use App\Custom\Notestatus;
@endphp

<div class="production-transfer-list">
    <style>
        .production-transfer-list {
            --transfer-border: #e5e7eb;
            --transfer-ink: #1f2937;
            --transfer-muted: #64748b;
        }

        .production-transfer-list .transfer-card {
            background: #ffffff;
            border: 1px solid var(--transfer-border);
            border-radius: .9rem;
            box-shadow: 0 14px 30px rgba(15, 23, 42, .08);
            overflow: hidden;
        }

        .production-transfer-list .transfer-header {
            align-items: center;
            background: var(--bs-primary);
            color: #ffffff;
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            justify-content: space-between;
            padding: 1rem 1.25rem;
        }

        .production-transfer-list .transfer-title {
            font-size: 1.25rem;
            font-weight: 600;
            line-height: 1.2;
            margin: 0;
        }

        .production-transfer-list .transfer-subtitle {
            color: rgba(255, 255, 255, .82);
            font-size: .82rem;
            margin-top: .2rem;
        }

        .production-transfer-list .transfer-count {
            background: rgba(255, 255, 255, .16);
            border: 1px solid rgba(255, 255, 255, .3);
            border-radius: .65rem;
            padding: .45rem .7rem;
        }

        .production-transfer-list .table {
            margin-bottom: 0;
        }

        .production-transfer-list .table thead th {
            background: #1e293b;
            border-color: #334155;
            color: #ffffff;
            font-size: .75rem;
            letter-spacing: .06em;
            padding: .7rem .65rem;
            text-transform: uppercase;
            white-space: nowrap;
        }

        .production-transfer-list .table tbody td {
            border-color: #e8edf3;
            padding: .8rem .65rem;
            vertical-align: middle;
        }

        .production-transfer-list .transfer-flow {
            align-items: center;
            display: grid;
            gap: .5rem;
            grid-template-columns: minmax(90px, 1fr) 28px minmax(90px, 1fr);
            min-width: 245px;
        }

        .production-transfer-list .transfer-person {
            min-width: 0;
        }

        .production-transfer-list .transfer-person strong {
            color: var(--transfer-ink);
            display: block;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .production-transfer-list .transfer-person small,
        .production-transfer-list .transfer-meta {
            color: var(--transfer-muted);
            font-size: .75rem;
        }

        .production-transfer-list .transfer-arrow {
            align-items: center;
            background: #eff6ff;
            border-radius: 999px;
            color: var(--bs-primary);
            display: inline-flex;
            height: 28px;
            justify-content: center;
            width: 28px;
        }

        .production-transfer-list .transfer-note {
            color: var(--transfer-ink);
            font-weight: 700;
            white-space: nowrap;
        }

        .production-transfer-list .transfer-reason {
            max-width: 340px;
            min-width: 180px;
        }

        .production-transfer-list .transfer-reason span {
            display: -webkit-box;
            overflow: hidden;
            -webkit-box-orient: vertical;
            -webkit-line-clamp: 2;
        }

        .production-transfer-list .transfer-actions {
            display: flex;
            gap: .35rem;
            justify-content: center;
            white-space: nowrap;
        }

        .production-transfer-list .transfer-actions .btn {
            align-items: center;
            display: inline-flex;
            height: 34px;
            justify-content: center;
            min-width: 34px;
            padding: 0 .55rem;
        }

        .production-transfer-list .transfer-empty {
            background: #ffffff;
            border: 1px solid var(--transfer-border);
            border-radius: .9rem;
            box-shadow: 0 12px 26px rgba(15, 23, 42, .06);
            padding: 3rem 1rem;
            text-align: center;
        }
    </style>

    @if (!$lists->count())
        <div class="transfer-empty">
            <i class="ri-arrow-left-right-line display-5 text-muted"></i>
            <h5 class="mt-3 mb-1">Nenhuma transferência pendente</h5>
            <p class="text-muted mb-0">Não existem intenções de transferência para este serviço.</p>
        </div>
    @else
        <div class="transfer-card">
            <div class="transfer-header">
                <div>
                    <h5 class="transfer-title">
                        <i class="ri-arrow-left-right-line me-1"></i>Transferências de produção
                    </h5>
                    <div class="transfer-subtitle">Solicitações enviadas e recebidas para este serviço</div>
                </div>
                <div class="transfer-count">
                    <strong>{{ $lists->count() }}</strong>
                    <span class="small">registro{{ $lists->count() === 1 ? '' : 's' }}</span>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle">
                    <thead>
                        <tr>
                            <th scope="col">Fluxo</th>
                            <th scope="col">Nota / SAP</th>
                            <th scope="col">Situação</th>
                            <th scope="col">Motivo</th>
                            <th scope="col">Atualização</th>
                            <th scope="col" class="text-center">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($lists as $list)
                            @php
                                $fromCurrentUser = $list->from === Auth()->User()->id;
                                $toCurrentUser = $list->to === Auth()->User()->id;
                            @endphp
                            <tr wire:key="production-transfer-{{ $list->id }}">
                                <td>
                                    <div class="transfer-flow">
                                        <div class="transfer-person">
                                            <small>De</small>
                                            <strong title="{{ $list->From->name }}">
                                                {{ $fromCurrentUser ? 'Você' : $list->From->name }}
                                            </strong>
                                        </div>
                                        <span class="transfer-arrow">
                                            <i class="ri-arrow-right-line"></i>
                                        </span>
                                        <div class="transfer-person">
                                            <small>Para</small>
                                            <strong title="{{ $list->To->name }}">
                                                {{ $toCurrentUser ? 'Você' : $list->To->name }}
                                            </strong>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="transfer-note">{{ $list->Production->Note->note }}</div>
                                    <div class="transfer-meta">
                                        SAP {{ $list->Production->status_note }}/{{ $list->Production->Note->nstats }}
                                    </div>
                                </td>
                                <td>
                                    <span class="badge {{ Notestatus::status($list->status)->colorbg }}">
                                        {{ Notestatus::status($list->status)->status }}
                                    </span>
                                </td>
                                <td class="transfer-reason" title="{{ $list->info }}">
                                    <span>{{ $list->info ?: 'Sem motivo informado' }}</span>
                                </td>
                                <td>
                                    <div>{{ Carbon::parse($list->updated_at)->format('d/m/Y') }}</div>
                                    <div class="transfer-meta">{{ Carbon::parse($list->updated_at)->format('H:i') }}</div>
                                </td>
                                <td>
                                    <div class="transfer-actions">
                                        @if ($toCurrentUser && !$list->read_to)
                                            <button type="button" class="btn btn-sm btn-outline-success"
                                                title="Aceitar transferência"
                                                wire:click.prevent="to_accept({{ $list->id }})">
                                                <i class="ri-checkbox-circle-line"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline-danger"
                                                title="Rejeitar transferência"
                                                wire:click.prevent="to_rejectt({{ $list->id }})">
                                                <i class="ri-close-circle-line"></i>
                                            </button>
                                        @elseif ($fromCurrentUser && !$list->read_from)
                                            <button type="button" class="btn btn-sm btn-outline-primary"
                                                title="Confirmar ciência"
                                                wire:click.prevent="to_ok({{ $list->id }})">
                                                <i class="ri-check-line me-1"></i>OK
                                            </button>
                                        @else
                                            <span class="text-muted small">Sem ações</span>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>
