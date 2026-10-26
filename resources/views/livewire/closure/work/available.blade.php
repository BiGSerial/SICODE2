<div class="closure-dash">
    @include('livewire.closure._styles')
    @include('livewire.closure.work._confirm-script')

    <div class="dash-hero mb-3">
        <div class="d-flex flex-wrap align-items-end justify-content-between gap-3">
            <div>
                <h2 class="dash-title">Atividade Encerramento</h2>
                <div class="dash-subtitle">Ordens disponíveis para você assumir.</div>
            </div>
            <div class="d-flex align-items-center gap-2">
                <a href="{{ \App\Services\Closure\ClosureServiceLocator::url('services.main', ['aba' => 'minhas']) }}" class="btn btn-light btn-sm">Minha atividade</a>
                @if ($enabled)
                    <span class="badge bg-light text-dark px-3 py-2">{{ count($selectedStateIds) }} selecionada(s)</span>
                @endif
            </div>
        </div>
    </div>

    @if (!$enabled)
        <div class="table-card p-4" id="closure-available-disabled">
            <h3 class="chart-title mb-2">Escolha de atividades desabilitada</h3>
            <p class="mb-3">O despachante ainda não liberou a escolha de atividades de Encerramento. As Ordens chegam até você por despacho. Você será levado para <strong>Minha atividade</strong> em instantes.</p>
            <a href="{{ \App\Services\Closure\ClosureServiceLocator::url('services.main', ['aba' => 'minhas']) }}" class="btn btn-dash text-white" style="background: var(--dash-teal);">Ir para Minha atividade agora</a>
        </div>
        <script>
            setTimeout(function () { window.location.href = @json(\App\Services\Closure\ClosureServiceLocator::url('services.main', ['aba' => 'minhas'])); }, 5000);
        </script>
    @else
        <div class="table-card filter-bar p-3 mb-3">
            <x-closure.bulk-search-result :report="$this->bulkReport()" class="mb-3" />
            <div class="row g-3 align-items-end">
                @include('livewire.closure.work._filters')
                <div class="col-12 col-md-6 d-flex flex-wrap align-items-center gap-2 justify-content-md-end">
                    <span class="small text-muted">Tipos liberados:</span>
                    @foreach ($allowedTypes as $allowed)
                        {!! \App\Services\Closure\ClosureMetaClassifier::badge($allowed) !!}
                    @endforeach
                    <button type="button" class="btn btn-outline-secondary btn-sm" wire:click="clearFilters">Limpar filtros</button>
                </div>
            </div>
        </div>

        <div class="table-card">
            <div class="chart-head">
                <div>
                    <h3 class="chart-title">Ordens disponíveis</h3>
                    <div class="chart-subtitle">Ao assumir, a Ordem vai para a sua Minha atividade e sai desta lista para os demais.</div>
                </div>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-outline-success btn-sm" wire:click="exportList" wire:loading.attr="disabled" wire:target="exportList"><i class="ri-file-excel-2-line"></i> Exportar Excel</button>
                    <button type="button" class="btn btn-outline-secondary btn-sm btn-dash" wire:click="toggleVisibleStates">Selecionar/limpar visíveis</button>
                    <button type="button" class="btn btn-dash btn-sm text-white" style="background: var(--dash-green);" wire:click="askClaim" @disabled(count($selectedStateIds) === 0)>Assumir selecionadas</button>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="ps-3" style="width:48px"></th>
                            @include('livewire.closure.work._order-head')
                            <th>Prioridade</th>
                            <th class="pe-3">Tempo na pilha</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $informValues = $this->informValues($states); @endphp
@forelse ($states as $state)
                            <tr wire:key="closure-available-{{ $state->id }}">
                                <td class="ps-3"><input type="checkbox" class="form-check-input" @checked(in_array($state->id, $selectedStateIds)) wire:click="toggleState({{ $state->id }})"></td>
                                @include('livewire.closure.work._order-cells')
                                <td>@if ($state->priority)<span class="badge bg-danger">Prioritária</span>@else<span class="text-muted small">Normal</span>@endif</td>
                                <td class="pe-3 small text-muted">{{ $state->entered_current_status_at?->diffForHumans(null, true) ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="12" class="text-center text-muted py-4">Não há Ordens disponíveis para você neste momento.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if ($receipt)
            <div class="table-card mt-3" id="closure-available-receipt">
                <div class="chart-head">
                    <div>
                        <h3 class="chart-title">Resultado da atribuição</h3>
                    </div>
                    <span class="badge bg-secondary">{{ count($receipt['successes']) }} assumida(s), {{ count($receipt['refusals']) }} recusada(s)</span>
                </div>
                <div class="row g-0">
                    <div class="col-12 col-lg-6 p-3 border-end">
                        <h4 class="h6">Assumidas</h4>
                        <ul class="mb-0 ps-3">
                            @forelse ($receipt['successes'] as $order)
                                <li>{{ $order }}</li>
                            @empty
                                <li class="text-muted">Nenhuma.</li>
                            @endforelse
                        </ul>
                    </div>
                    <div class="col-12 col-lg-6 p-3">
                        <h4 class="h6">Recusadas</h4>
                        <ul class="mb-0 ps-3">
                            @forelse ($receipt['refusals'] as $refusal)
                                <li><strong>{{ $refusal['order'] }}</strong>: {{ $refusal['reason'] }}</li>
                            @empty
                                <li class="text-muted">Nenhuma.</li>
                            @endforelse
                        </ul>
                    </div>
                </div>
            </div>
        @endif
    @endif
</div>
