@php
    use App\Custom\Notestatus;

    $me = auth()->id();

    // Situação da transferência → tom do selo (mesmos tons semânticos das listas operacionais).
    $statusTone = fn ($status) => match ((int) $status) {
        19      => 'blue',
        20      => 'red',
        21      => 'green',
        default => 'amber',
    };
@endphp

{{-- Transferências de produção do usuário (enviadas e recebidas): padrão em docs/PADRAO_LISTAS_OPERACIONAIS.md. --}}
<div class="closure-dash my-panel ops-list">
<section class="mp-panel" aria-label="Transferências de produção">
    <div class="mp-panel-head">
        <div>
            <h2 class="mp-panel-title">Transferências</h2>
            <div class="mp-panel-sub">
                {{ number_format($lists->count(), 0, ',', '.') }} {{ $lists->count() === 1 ? 'registro' : 'registros' }} · solicitações enviadas e recebidas neste serviço
            </div>
        </div>
        <span class="mp-pill" wire:loading.delay>Atualizando…</span>
    </div>

    @if ($lists->isEmpty())
        <div class="mp-panel-body">
            <x-closure.empty-state
                title="Nenhuma transferência pendente"
                text="Quando você transferir uma nota, ou alguém transferir uma para você, ela aparece aqui."
                icon="ri-arrow-left-right-line" />
        </div>
    @else
        <div class="ops-table-wrap">
            <table class="table ops-table">
                <colgroup>
                    @foreach ([260, 170, 150, 340, 120, 220] as $w)
                        <col style="width: {{ $w }}px">
                    @endforeach
                </colgroup>
                <thead>
                    <tr>
                        <th class="is-left">Fluxo</th>
                        <th>Nota / SAP</th>
                        <th>Situação</th>
                        <th class="is-left">Motivo</th>
                        <th>Atualização</th>
                        <th><span class="visually-hidden">Ações</span></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($lists as $list)
                        @php
                            $fromMe           = $list->from === $me;
                            $toMe             = $list->to === $me;
                            $isPending        = (int) $list->status === 19;
                            $hasUnreadOutcome = ($fromMe && !$list->read_from) || ($toMe && !$list->read_to);
                            $statusLabel      = Notestatus::status($list->status)->status;
                        @endphp
                        <tr wire:key="production-transfer-{{ $list->id }}">
                            <td class="is-left">
                                <div class="ops-cell-stack">
                                    <span><span class="ops-empty">De</span> <span class="fw-semibold" title="{{ $list->From->name }}">{{ $fromMe ? 'Você' : $list->From->name }}</span></span>
                                    <span><span class="ops-empty">Para</span> <span class="fw-semibold" title="{{ $list->To->name }}">{{ $toMe ? 'Você' : $list->To->name }}</span></span>
                                </div>
                            </td>

                            <td>
                                <div class="ops-cell-stack is-center">
                                    <x-ops.note :note="$list->Production->Note->note" />
                                    <span class="ops-empty">SAP {{ $list->Production->status_note }}/{{ $list->Production->Note->nstats }}</span>
                                </div>
                            </td>

                            <td><x-ops.badge :tone="$statusTone($list->status)" :label="$statusLabel" :hint="'Situação da transferência: ' . $statusLabel" /></td>

                            <td class="is-left">
                                @if (filled($list->info))
                                    <span class="d-block text-truncate" title="{{ $list->info }}">{{ $list->info }}</span>
                                @else
                                    <span class="ops-empty">Sem motivo informado</span>
                                @endif
                            </td>

                            <td><x-ops.date :date="$list->updated_at" time hint="Última atualização da transferência" /></td>

                            <td>
                                <div class="d-inline-flex gap-1">
                                    @if ($toMe && !$list->read_to && $isPending)
                                        <button type="button" class="btn btn-success btn-sm ops-action" title="Aceitar a transferência" wire:click.prevent="to_accept({{ $list->id }})">
                                            <i class="ri-checkbox-circle-line" aria-hidden="true"></i> Aceitar
                                        </button>
                                        <button type="button" class="btn btn-outline-danger btn-sm ops-action" title="Rejeitar a transferência" wire:click.prevent="to_rejectt({{ $list->id }})">
                                            <i class="ri-close-circle-line" aria-hidden="true"></i> Rejeitar
                                        </button>
                                    @elseif ($fromMe && $isPending)
                                        <button type="button" class="btn btn-outline-danger btn-sm ops-action" title="Cancelar a transferência" wire:click.prevent="to_cancel({{ $list->id }})">
                                            <i class="ri-close-circle-line" aria-hidden="true"></i> Cancelar
                                        </button>
                                    @elseif ($hasUnreadOutcome)
                                        <button type="button" class="btn btn-primary btn-sm ops-action" title="Confirmar ciência" wire:click.prevent="to_ok({{ $list->id }})">
                                            <i class="ri-check-line" aria-hidden="true"></i> OK
                                        </button>
                                    @else
                                        <span class="ops-empty">—</span>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</section>
</div>
