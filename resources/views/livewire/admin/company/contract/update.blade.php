<div>
    <x-show-loading />

    @if ($show_update)
        <form>
            <div class="row g-3">
                <div class="col-lg-7">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-header bg-white d-flex align-items-center justify-content-between">
                            <strong>Dados do contrato</strong>
                            <span class="badge text-bg-light">Edicao</span>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label">Empresa</label>
                                <input wire:model.defer="company" type="text" class="form-control" disabled>
                            </div>

                            <div class="row g-3">
                                <div class="col-md-7">
                                    <label class="form-label">Numero do contrato</label>
                                    <input wire:model.defer="number" type="text" class="form-control">
                                </div>
                                <div class="col-md-5">
                                    <label class="form-label">Validade</label>
                                    <input wire:model.defer="date_end" type="date" class="form-control">
                                </div>
                            </div>

                            <div class="mt-4">
                                <label class="form-label d-block">Tipo de contrato</label>
                                <div class="d-flex flex-wrap gap-3">
                                    <div class="form-check form-switch">
                                        <input wire:model="service" class="form-check-input" type="checkbox" id="service_update">
                                        <label class="form-check-label" for="service_update">Serviços</label>
                                    </div>
                                    <div class="form-check form-switch">
                                        <input wire:model="construction" class="form-check-input" type="checkbox" id="construction_update">
                                        <label class="form-check-label" for="construction_update">Construção</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-5">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-header bg-white">
                            <strong>Resumo</strong>
                        </div>
                        <div class="card-body">
                            <div class="d-flex justify-content-between border-bottom py-2">
                                <span class="text-muted">Atividades selecionadas</span>
                                <strong>{{ count($selectedServices) }}</strong>
                            </div>
                            <div class="d-flex justify-content-between border-bottom py-2">
                                <span class="text-muted">Com despacho</span>
                                <strong>{{ collect($serviceDispatch)->filter()->count() }}</strong>
                            </div>
                            <div class="small text-muted mt-3">
                                Alterar as atividades do contrato muda a base disponivel para novos cadastros deste contrato.
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-12">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white d-flex flex-wrap gap-2 align-items-center justify-content-between">
                            <strong>Atividades liberadas</strong>
                            <input wire:model.debounce.300ms="activitySearch" type="search" class="form-control form-control-sm" style="max-width: 260px;" placeholder="Buscar atividade">
                        </div>
                        <div class="card-body">
                            <div class="row g-2">
                                @forelse ($services_l as $activity)
                                    <div class="col-md-6 col-xl-4" wire:key="contract_update_activity_{{ $activity->id }}">
                                        <div class="border rounded-2 p-3 h-100 bg-white">
                                            <div class="d-flex align-items-start gap-2">
                                                <input class="form-check-input mt-1" type="checkbox" value="{{ $activity->id }}" wire:model="selectedServices" id="activity_update_{{ $activity->id }}">
                                                <div class="flex-grow-1">
                                                    <label class="fw-bold mb-1" for="activity_update_{{ $activity->id }}">{{ $activity->service }}</label>
                                                    <div class="d-flex flex-wrap gap-1">
                                                        @if ($activity->project)
                                                            <span class="badge text-bg-primary">Projeto</span>
                                                        @endif
                                                        @if ($activity->construction)
                                                            <span class="badge text-bg-secondary">Construcao</span>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="form-check form-switch mt-3">
                                                <input class="form-check-input" type="checkbox" wire:model.defer="serviceDispatch.{{ $activity->id }}" id="dispatch_update_{{ $activity->id }}" @disabled(!in_array((string) $activity->id, $selectedServices, true) && !in_array($activity->id, $selectedServices, true))>
                                                <label class="form-check-label" for="dispatch_update_{{ $activity->id }}">Permitir despacho</label>
                                            </div>
                                        </div>
                                    </div>
                                @empty
                                    <div class="col-12 text-center text-muted py-4">Nenhuma atividade encontrada.</div>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    @endif
</div>
