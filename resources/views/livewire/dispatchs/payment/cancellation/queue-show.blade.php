<div class="container-fluid">
    <div class="card mb-3">
        <div class="card-header d-flex align-items-center">
            <strong class="me-auto">Solicitação #{{ $request->id }}</strong>
            @if($request->status === 'SUBMITTED' && !$request->assigned_to)
                <button class="btn btn-sm btn-outline-success" wire:click="claim">Assumir</button>
            @endif
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <div class="border rounded p-3 h-100">
                        <h6>Nota</h6>
                        <p class="mb-1"><strong>Número:</strong> {{ $request->Note->note ?? '-' }}</p>
                        <p class="mb-1"><strong>Cliente:</strong> {{ $request->Note->client ?? '-' }}</p>
                        <p class="mb-1"><strong>Status:</strong> {{ $request->Note->status ?? '-' }}</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="border rounded p-3 h-100">
                        <h6>Solicitação</h6>
                        <p class="mb-1"><strong>Categoria:</strong> {{ $request->Category->name ?? '-' }}</p>
                        <p class="mb-1"><strong>Escopo:</strong> {{ $request->scope }}</p>
                        <p class="mb-1"><strong>Status:</strong> {{ $request->status }}</p>
                        <p class="mb-1"><strong>Solicitante:</strong> {{ $request->Requester->name ?? '-' }}</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="border rounded p-3 h-100">
                        <h6>Execução</h6>
                        <p class="mb-1"><strong>Assumido:</strong> {{ $request->Assignee->name ?? '-' }}</p>
                        <p class="mb-1"><strong>Assumido em:</strong> {{ optional($request->assigned_at)->format('d/m/Y H:i') }}</p>
                        <p class="mb-1"><strong>Encerrado em:</strong> {{ optional($request->closed_at)->format('d/m/Y H:i') }}</p>
                    </div>
                </div>
            </div>

            <div class="row mt-3">
                <div class="col-12">
                    <h6>Ordens</h6>
                    <div class="table-responsive">
                        <table class="table table-sm table-striped">
                            <thead>
                                <tr>
                                    <th>Ordem</th>
                                    <th>Status</th>
                                    <th>Cancelada</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($request->Orders as $order)
                                    <tr>
                                        <td>{{ $order->ordem }}</td>
                                        <td>{{ $order->statusUser ?? $order->statusSist ?? '-' }}</td>
                                        <td>{{ $order->canceled ? 'Sim' : 'Não' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="row mt-3">
                <div class="col-12">
                    <h6>Descrição</h6>
                    <p class="mb-0">{{ $request->description ?? '-' }}</p>
                </div>
            </div>

            <div class="row mt-3">
                <div class="col-md-6">
                    <h6>Anexos</h6>
                    <ul class="list-group">
                        @forelse($request->EvidenceFiles as $file)
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <span>{{ $file->original_name }}</span>
                                <button class="btn btn-sm btn-outline-primary" wire:click="downloadEvidence({{ $file->id }})">Baixar</button>
                            </li>
                        @empty
                            <li class="list-group-item">Nenhum anexo.</li>
                        @endforelse
                    </ul>
                </div>
                <div class="col-md-6">
                    <h6>Linha do tempo</h6>
                    <ul class="list-group">
                        @forelse($request->Events as $event)
                            <li class="list-group-item">
                                <strong>{{ strtoupper($event->type) }}</strong>
                                <div class="small text-muted">{{ optional($event->created_at)->format('d/m/Y H:i') }} - {{ $event->Actor->name ?? 'Sistema' }}</div>
                                @if(!empty($event->meta))
                                    <div class="small">{{ json_encode($event->meta) }}</div>
                                @endif
                            </li>
                        @empty
                            <li class="list-group-item">Sem eventos.</li>
                        @endforelse
                    </ul>
                </div>
            </div>

            @if(in_array($request->status, ['SUBMITTED', 'ASSIGNED'], true))
                <div class="row mt-4">
                    <div class="col-md-6">
                        <label class="form-label">Ação</label>
                        <select class="form-select" wire:model="action">
                            <option value="DONE">Concluir (Cancelar)</option>
                            <option value="REJECTED">Rejeitar</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Observação final</label>
                        <input type="text" class="form-control" wire:model.defer="closureNote" />
                        @error('closureNote')<span class="text-danger small">{{ $message }}</span>@enderror
                    </div>
                    <div class="col-12 mt-3">
                        <button class="btn btn-success" wire:click="finalize">Finalizar</button>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
