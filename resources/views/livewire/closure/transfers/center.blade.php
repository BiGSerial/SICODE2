<div class="closure-dash">
    <div class="dash-hero mb-3">
        <h2 class="dash-title">Transferências de atividade</h2>
        <div class="dash-subtitle">Passe Ordens para outro usuário do Encerramento. Não precisa de aprovação da gestão: quem recebe aceita ou rejeita, e quem enviou pode cancelar enquanto estiver pendente.</div>
    </div>

    <x-closure.inbox-tabs class="mb-3" :active="$tab" :tabs="[
        ['key' => 'received', 'label' => 'Recebidas', 'count' => $received->count(), 'tone' => 'red', 'icon' => 'ri-inbox-archive-line'],
        ['key' => 'sent', 'label' => 'Enviadas', 'count' => $sent->count(), 'tone' => 'blue', 'icon' => 'ri-send-plane-line'],
        ['key' => 'history', 'label' => 'Histórico', 'count' => $history->count(), 'tone' => 'muted', 'icon' => 'ri-history-line'],
    ]" />

    @php
        $cards = $tab === 'received' ? $received : ($tab === 'sent' ? $sent : $history);
        $labels = ['ACCEPTED' => ['Aceita', 'success'], 'REJECTED' => ['Rejeitada', 'danger'], 'CANCELLED' => ['Cancelada', 'secondary'], 'PENDING' => ['Pendente', 'warning']];
    @endphp

    <div class="row g-3 mt-1">
        @forelse ($cards as $request)
            <div class="col-12 col-xl-6" wire:key="transfer-{{ $tab }}-{{ $request->id }}">
                <div class="table-card h-100 p-3 transfer-card">
                    <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                        <div>
                            <div class="fw-bold">{{ $request->From?->name }} <i class="ri-arrow-right-line text-muted"></i> {{ $request->To?->name }}</div>
                            <div class="small text-muted"><x-closure.age-chip :since="$request->created_at" /> · {{ $request->created_at->format('d/m/Y H:i') }}</div>
                        </div>
                        <span class="badge text-bg-{{ $labels[$request->status][1] }}">{{ $labels[$request->status][0] }}</span>
                    </div>
                    <div class="small mb-2"><strong>Motivo:</strong> <span class="pre-line">{{ $request->reason }}</span></div>
                    @if ($request->decision_note)
                        <div class="small mb-2"><strong>Resposta:</strong> {{ $request->decision_note }}</div>
                    @endif
                    <div class="mb-2">
                        @foreach ($request->Items as $item)
                            <span class="badge bg-light text-dark border me-1 mb-1" wire:key="transfer-item-{{ $item->id }}"
                                  title="{{ $item->result === 'SKIPPED' ? $item->result_reason : 'Nota ' . $item->State?->Order?->Note?->note }}">
                                {{ $item->State?->Order?->ordem }}
                                @if ($item->result === 'MOVED') <i class="ri-check-line text-success"></i> @elseif ($item->result === 'SKIPPED') <i class="ri-error-warning-line text-danger"></i> @endif
                            </span>
                        @endforeach
                    </div>
                    @foreach ($request->Items->where('result', 'SKIPPED') as $item)
                        <div class="small text-danger">{{ $item->State?->Order?->ordem }}: {{ $item->result_reason }}</div>
                    @endforeach

                    @if ($request->isPending() && $tab === 'received')
                        @if ($rejectingId === $request->id)
                            <textarea class="form-control form-control-sm my-2" rows="2" wire:model.defer="rejectNote" placeholder="Motivo da rejeição (opcional)"></textarea>
                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-danger btn-sm" wire:click="reject">Confirmar rejeição</button>
                                <button type="button" class="btn btn-outline-secondary btn-sm" wire:click="cancelReject">Voltar</button>
                            </div>
                        @else
                            <div class="d-flex gap-2 mt-2">
                                <button type="button" class="btn btn-success btn-sm fw-semibold" wire:click="accept({{ $request->id }})" wire:loading.attr="disabled" wire:target="accept"><i class="ri-check-line"></i> Aceitar</button>
                                <button type="button" class="btn btn-outline-danger btn-sm" wire:click="askReject({{ $request->id }})"><i class="ri-close-line"></i> Rejeitar</button>
                            </div>
                        @endif
                    @elseif ($request->isPending() && $tab === 'sent')
                        <div class="mt-2"><button type="button" class="btn btn-outline-danger btn-sm" wire:click="cancel({{ $request->id }})" wire:loading.attr="disabled" wire:target="cancel"><i class="ri-close-circle-line"></i> Cancelar transferência</button></div>
                    @endif
                </div>
            </div>
        @empty
            <div class="col-12">
                <div class="table-card p-4">
                    <x-closure.empty-state icon="ri-exchange-line"
                        :title="$tab === 'received' ? 'Nada esperando a sua resposta' : ($tab === 'sent' ? 'Você não tem transferências pendentes' : 'Sem histórico ainda')"
                        :text="$tab === 'history' ? 'Transferências aceitas, rejeitadas e canceladas aparecem aqui.' : 'Para transferir, use Ações › Transferir em Minha atividade.'" />
                </div>
            </div>
        @endforelse
    </div>
</div>
