<div class="oexterno-page">
    <div class="container-fluid">
        <x-show-loading />
        <style>
            .oexterno-page { background: #f6f7fb; padding: 1.5rem 0; }
            .oexterno-header { background: linear-gradient(120deg, #0f172a, #0f766e 70%); color: #f8fafc; border-radius: 1rem; padding: 1.5rem 2rem; box-shadow: 0 16px 40px rgba(15, 23, 42, .2); margin-bottom: 1.5rem; }
            .oexterno-card { background: #fff; border: 1px solid #e5e7eb; border-radius: .9rem; box-shadow: 0 12px 24px rgba(15, 23, 42, .06); }
        </style>

        <div class="oexterno-header d-flex flex-column flex-lg-row justify-content-between gap-2">
            <div>
                <h2 class="mb-0">Descancelamento #{{ $uncancellationRequest->id }}</h2>
                <span class="text-white-50">Nota/OV {{ $uncancellationRequest->Note->note ?? '-' }}</span>
            </div>
            <span class="badge {{ $uncancellationRequest->status?->badgeClass() ?? 'bg-secondary' }} align-self-start">
                {{ $uncancellationRequest->status?->label() ?? '-' }}
            </span>
        </div>

        <div class="row g-3">
            <div class="col-lg-8">
                <div class="oexterno-card p-3 mb-3">
                    <h5>Dados da Solicitação</h5>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <div class="text-muted small">Escopo</div>
                            <div class="fw-semibold">{{ $uncancellationRequest->scope?->label() ?? '-' }}</div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-muted small">Solicitante</div>
                            <div class="fw-semibold">{{ $uncancellationRequest->Requester->name ?? '-' }}</div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-muted small">Solicitado em</div>
                            <div class="fw-semibold">{{ optional($uncancellationRequest->submitted_at)->format('d/m/Y H:i') }}</div>
                        </div>
                        <div class="col-12">
                            <div class="text-muted small">Justificativa</div>
                            <div>{{ $uncancellationRequest->description ?: '-' }}</div>
                        </div>
                    </div>
                </div>

                <div class="oexterno-card p-3">
                    <h5>Alvos</h5>
                    <div class="mb-2">
                        <span class="badge {{ $uncancellationRequest->Note->canceled ? 'bg-danger' : 'bg-success' }}">
                            Nota {{ $uncancellationRequest->Note->canceled ? 'cancelada' : 'ativa' }}
                        </span>
                        @foreach ($uncancellationRequest->Note->WorkFormsAny as $workForm)
                            <span class="badge {{ $workForm->canceled ? 'bg-danger' : 'bg-success' }}">
                                Informe #{{ $workForm->id }} {{ $workForm->canceled ? 'cancelado' : 'ativo' }}
                            </span>
                        @endforeach
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm table-striped mb-0">
                            <thead>
                                <tr>
                                    <th>Ordem</th>
                                    <th>Status</th>
                                    <th>Cancelada</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($uncancellationRequest->Orders as $order)
                                    <tr>
                                        <td>{{ $order->ordem }}</td>
                                        <td>{{ $order->statusUser ?: $order->statusSist ?: '-' }}</td>
                                        <td>{{ $order->canceled ? 'Sim' : 'Não' }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="3" class="text-center">Sem ordens vinculadas.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="oexterno-card p-3">
                    <h5>Ação</h5>
                    @if ($isClosed)
                        <div class="alert alert-secondary">Solicitação já finalizada.</div>

                        @if ($canRevert)
                            <div class="border-top pt-3">
                                <label class="form-label">Motivo para desfazer</label>
                                <textarea class="form-control mb-2" rows="4" wire:model.defer="reversalNote"></textarea>
                                @error('reversalNote')<span class="text-danger small">{{ $message }}</span>@enderror

                                <button class="btn btn-outline-danger w-100 mt-2" wire:click="revertAction">
                                    <i class="ri-arrow-go-back-line me-1"></i>Desfazer descancelamento
                                </button>
                            </div>
                        @endif
                    @else
                        <label class="form-label">Resultado</label>
                        <select class="form-select mb-3" wire:model="action">
                            <option value="DONE">Concluir descancelamento</option>
                            <option value="REJECTED">Rejeitar solicitação</option>
                        </select>

                        @if ($action === 'REJECTED')
                            <label class="form-label">Motivo</label>
                            <textarea class="form-control mb-2" rows="4" wire:model.defer="closureNote"></textarea>
                            @error('closureNote')<span class="text-danger small">{{ $message }}</span>@enderror
                        @endif

                        <button class="btn btn-success w-100" wire:click="runAction">
                            <i class="ri-check-line me-1"></i>Confirmar
                        </button>
                    @endif
                </div>

                <div class="oexterno-card p-3 mt-3">
                    <h5>Eventos</h5>
                    @forelse ($uncancellationRequest->Events as $event)
                        <div class="border-bottom py-2">
                            <div class="fw-semibold">{{ $event->event }}</div>
                            <div class="small text-muted">{{ optional($event->created_at)->format('d/m/Y H:i') }} • {{ $event->User->name ?? '-' }}</div>
                        </div>
                    @empty
                        <div class="text-muted small">Nenhum evento registrado.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
