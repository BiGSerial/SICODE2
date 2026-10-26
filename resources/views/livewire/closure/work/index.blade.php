<div class="closure-dash">
    @include('livewire.closure._styles')
    @include('livewire.closure.work._confirm-script')

    <div class="dash-hero mb-3">
        <div class="d-flex flex-wrap align-items-end justify-content-between gap-3">
            <div>
                @if ($controlMode ?? false)
                    <h2 class="dash-title">Controle de Encerramento</h2>
                    <div class="dash-subtitle">Ordens já despachadas, de todos os usuários e empresas: acompanhe, priorize, atribua e transfira.</div>
                @else
                    <h2 class="dash-title">Minha Atividade — Encerramento</h2>
                    <div class="dash-subtitle">Ordens atribuídas a você{{ auth()->user()->can('closure.manage') ? ' (como gestor, você vê as de todos)' : '' }}.</div>
                @endif
            </div>
            <div class="d-flex align-items-center gap-2">
                @if ($selfAssignEnabled && !($controlMode ?? false))
                    <a href="{{ \App\Services\Closure\ClosureServiceLocator::url('services.main', ['aba' => 'disponiveis']) }}" class="btn btn-light btn-sm">Pegar atividades</a>
                @endif
                @unless ($controlMode ?? false)
                    <a href="{{ \App\Services\Closure\ClosureServiceLocator::url('services.transfers') }}" class="btn btn-light btn-sm"><i class="ri-exchange-line"></i> Transferências</a>
                    <button type="button" class="btn btn-light btn-sm" wire:click="transferSelected" @disabled(count($selectedStateIds) === 0)><i class="ri-user-shared-line"></i> Transferir selecionadas</button>
                @endunless
                <span class="badge bg-light text-dark px-3 py-2">{{ count($selectedStateIds) }} selecionada(s)</span>
            </div>
        </div>
    </div>

    @include('livewire.closure.dispatch._control-top')

    <div class="table-card">
        <div class="chart-head">
            <div>
                <h3 class="chart-title">Fila operacional</h3>
                <div class="chart-subtitle">O status SAP é informativo; as ações abaixo não o alteram.</div>
            </div>
            <div class="d-flex flex-wrap align-items-center gap-2">
                <button type="button" class="btn btn-outline-success btn-sm" wire:click="exportList" wire:loading.attr="disabled" wire:target="exportList"><i class="ri-file-excel-2-line"></i> Exportar Excel</button>
                <div class="d-flex align-items-center gap-1 small text-muted">
                    <label for="closure-work-per-page-top" class="mb-0">Itens por página</label>
                    <select id="closure-work-per-page-top" class="form-select form-select-sm w-auto" wire:model="perPage" aria-label="Itens por página">
                        @foreach (\App\Http\Livewire\Closure\Work\Index::PER_PAGE_OPTIONS as $option)
                            <option value="{{ $option }}">{{ $option }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>
        <div class="table-responsive dash-scroll">
            @include('livewire.closure.dispatch._control-table')
        </div>
        <div class="p-3 border-top d-flex flex-wrap align-items-center justify-content-between gap-2">
            <div class="d-flex flex-wrap align-items-center gap-3 small text-muted">
                <span class="d-inline-flex align-items-center gap-2">
                    <label for="closure-work-per-page" class="mb-0">Itens por página</label>
                    <select id="closure-work-per-page" class="form-select form-select-sm w-auto" wire:model="perPage">
                        @foreach (\App\Http\Livewire\Closure\Work\Index::PER_PAGE_OPTIONS as $option)
                            <option value="{{ $option }}">{{ $option }}</option>
                        @endforeach
                    </select>
                </span>
                <span>Exibindo {{ number_format((int) $states->firstItem(), 0, ',', '.') }}–{{ number_format((int) $states->lastItem(), 0, ',', '.') }} de {{ number_format($states->total(), 0, ',', '.') }}</span>
            </div>
            <div>{{ $states->links() }}</div>
        </div>
    </div>

    @include('livewire.closure.dispatch._control-modals')

    @if ($batchPreview)
        <div class="table-card mt-3" id="closure-work-batch-preview">
            <div class="chart-head">
                <div>
                    <h3 class="chart-title">Prévia da ação em massa</h3>
                    <div class="chart-subtitle">A confirmação fará uma nova verificação antes de alterar cada Ordem.</div>
                </div>
                <span class="badge bg-secondary">{{ count($batchPreview['eligible']) }} pronta(s), {{ count($batchPreview['refusals']) }} recusada(s)</span>
            </div>
            <div class="row g-0">
                <div class="col-12 col-lg-6 p-3 border-end">
                    <h4 class="h6">Prontas para a ação</h4>
                    <ul class="mb-0 ps-3">
                        @forelse ($batchPreview['eligible'] as $order)
                            <li>{{ $order }}</li>
                        @empty
                            <li class="text-muted">Nenhuma Ordem pronta.</li>
                        @endforelse
                    </ul>
                </div>
                <div class="col-12 col-lg-6 p-3">
                    <h4 class="h6">Recusadas na prévia</h4>
                    <ul class="mb-0 ps-3">
                        @forelse ($batchPreview['refusals'] as $refusal)
                            <li><strong>{{ $refusal['order'] }}</strong>: {{ $refusal['reason'] }}</li>
                        @empty
                            <li class="text-muted">Nenhuma recusa.</li>
                        @endforelse
                    </ul>
                </div>
            </div>
            <div class="p-3 border-top d-flex justify-content-end">
                <button type="button" class="btn btn-dash text-white" style="background: var(--dash-green);" wire:click="confirmBatchAction">Confirmar ação em massa</button>
            </div>
        </div>
    @endif

    @if ($batchReceipt)
        <div class="table-card mt-3" id="closure-work-receipt">
            <div class="chart-head">
                <div>
                    <h3 class="chart-title">Recibo da ação em massa</h3>
                    <div class="chart-subtitle">Identificador: {{ $batchReceipt['batchId'] }}</div>
                </div>
                <span class="badge bg-secondary">{{ count($batchReceipt['successes']) }} sucesso(s), {{ count($batchReceipt['refusals']) }} recusa(s)</span>
            </div>
            <div class="row g-0">
                <div class="col-12 col-lg-6 p-3 border-end">
                    <h4 class="h6">Processadas</h4>
                    <ul class="mb-0 ps-3">
                        @forelse ($batchReceipt['successes'] as $order)
                            <li>{{ $order }}</li>
                        @empty
                            <li class="text-muted">Nenhuma Ordem processada.</li>
                        @endforelse
                    </ul>
                </div>
                <div class="col-12 col-lg-6 p-3">
                    <h4 class="h6">Recusadas</h4>
                    <ul class="mb-0 ps-3">
                        @forelse ($batchReceipt['refusals'] as $refusal)
                            <li><strong>{{ $refusal['order'] }}</strong>: {{ $refusal['reason'] }}</li>
                        @empty
                            <li class="text-muted">Nenhuma recusa.</li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>
    @endif
    @livewire('closure.internal-returns.modal', [], key('closure-ri-modal'))
    @unless ($controlMode ?? false)
        @livewire('closure.transfers.send-modal', [], key('closure-transfer-modal'))
    @endunless
</div>
