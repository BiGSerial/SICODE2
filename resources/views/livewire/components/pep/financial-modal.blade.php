<div>
    <div wire:ignore.self class="modal fade" id="pepFinancialModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header text-bg-primary">
                    <h5 class="modal-title">Consulta financeira de PEPs</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-2 mb-3">
                        <div class="col-md-4">
                            <input wire:model.debounce.400ms="search" class="form-control" placeholder="Buscar PEP">
                        </div>
                        <div class="col-md-2">
                            <select wire:model="region" class="form-select">
                                <option value="">Todas regiões</option>
                                @foreach ($regions as $item)
                                    <option value="{{ $item }}">{{ $item }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <select wire:model="year" class="form-select">
                                <option value="">Todos os anos</option>
                                @foreach ($years as $item)
                                    <option value="{{ $item }}">{{ $item }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select wire:model="availability" class="form-select">
                                <option value="">Todos os saldos</option>
                                <option value="positive">Com recursos (positivo)</option>
                                <option value="zero">Saldo zerado</option>
                                <option value="negative">Saldo negativo</option>
                            </select>
                        </div>
                        <div class="col-md-1 d-grid">
                            <button class="btn btn-success" wire:click="export" title="Exportar resultado filtrado">
                                <i class="ri-file-excel-2-line"></i>
                            </button>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-sm table-striped align-middle mb-0">
                            <thead class="table-dark">
                                <tr>
                                    <th>PEP</th><th>Ano</th><th>Orçamento</th><th>Real</th><th>Compromisso</th>
                                    <th>Plano Res. Ordem</th><th>Disposto</th><th>Disponível</th><th>Região</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($peps as $pep)
                                    @php $available = $pep->disponivel === null ? null : (float) $pep->disponivel; @endphp
                                    <tr>
                                        <td class="fw-bold">{{ $pep->objeto }}</td>
                                        <td>{{ $pep->ano ?? '---' }}</td>
                                        <td>{{ $pep->orcamento === null ? '---' : 'R$ '.number_format((float) $pep->orcamento, 2, ',', '.') }}</td>
                                        <td>{{ $pep->real === null ? '---' : 'R$ '.number_format((float) $pep->real, 2, ',', '.') }}</td>
                                        <td>{{ $pep->compromisso === null ? '---' : 'R$ '.number_format((float) $pep->compromisso, 2, ',', '.') }}</td>
                                        <td>{{ $pep->plan_res_ord === null ? '---' : 'R$ '.number_format((float) $pep->plan_res_ord, 2, ',', '.') }}</td>
                                        <td>{{ $pep->disposto === null ? '---' : 'R$ '.number_format((float) $pep->disposto, 2, ',', '.') }}</td>
                                        <td class="fw-bold {{ $available !== null && $available < 0 ? 'text-danger' : ($available !== null && $available > 0 ? 'text-success' : '') }}">{{ $available === null ? '---' : 'R$ '.number_format($available, 2, ',', '.') }}</td>
                                        <td>{{ $pep->regiao ?? '---' }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="9" class="text-center py-4">Nenhum PEP encontrado.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-3">{{ $peps->links() }}</div>
                </div>
            </div>
        </div>
    </div>
</div>
