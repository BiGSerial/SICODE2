{{-- Configurações do Encerramento (Gestão do ciclo). Padrão: docs/PADRAO_LISTAS_OPERACIONAIS.md (hero + painéis). --}}
<div class="closure-dash my-panel ops-list">
    <x-ops.hero eyebrow="Gestão do ciclo · Encerramento" title="Configurações do Encerramento" subtitle="Defina quem pode se atribuir Ordens, quando uma meta futura pode ser finalizada e quais rubricas ficam fora do Encerramento." />

    <div class="ops-settings">
        <section class="mp-panel" aria-labelledby="cfg-self">
            <div class="mp-panel-head">
                <div>
                    <h2 class="mp-panel-title" id="cfg-self">Auto-atribuição pelo usuário</h2>
                    <div class="mp-panel-sub">O usuário do Serviço pode assumir sozinho uma Ordem que ainda não foi despachada, sem esperar o despachante.</div>
                </div>
                <x-ops.badge :tone="$selfAssignEnabled ? 'green' : 'muted'" :label="$selfAssignEnabled ? 'Ligada' : 'Desligada'" />
            </div>
            <div class="mp-panel-body">
                <div class="form-check form-switch mb-3">
                    <input class="form-check-input" type="checkbox" role="switch" id="cfg-self-assign" wire:model="selfAssignEnabled">
                    <label class="form-check-label" for="cfg-self-assign">Permitir que o usuário se atribua Ordens <span class="d-block small text-muted">Desligada: só o despachante atribui. A lista "Pegar atividades" some do Serviço.</span></label>
                </div>
                <div class="ops-settings-types @if (!$selfAssignEnabled) is-off @endif">
                    <div class="fw-semibold mb-1">Tipos que o usuário pode assumir</div>
                    @foreach ($types as $type)
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="cfg-type-{{ $type }}" value="{{ $type }}" wire:model="selfAssignTypes" @disabled(!$selfAssignEnabled)>
                            <label class="form-check-label" for="cfg-type-{{ $type }}"><x-ops.badge sm mono :tone="['P' => 'red', 'M' => 'green', 'F' => 'blue'][$type]" :label="$type" /> {{ \App\Services\Closure\ClosureMetaClassifier::label($type) }}</label>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        <section class="mp-panel" aria-labelledby="cfg-future">
            <div class="mp-panel-head">
                <div>
                    <h2 class="mp-panel-title" id="cfg-future">Meta futura</h2>
                    <div class="mp-panel-sub">Ordens de meta futura vão para Aguardando até o mês da meta.</div>
                </div>
            </div>
            <div class="mp-panel-body">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" role="switch" id="cfg-future-toggle" wire:model="allowFutureCompletion">
                    <label class="form-check-label" for="cfg-future-toggle">Permitir concluir obras de Meta Futura <span class="d-block small text-muted">Elas ficam em Aguardando até a meta chegar e são finalizadas sozinhas.</span></label>
                </div>
            </div>
        </section>

        <section class="mp-panel" aria-labelledby="cfg-rubric" x-data="{ q: '' }">
            <div class="mp-panel-head">
                <div>
                    <h2 class="mp-panel-title" id="cfg-rubric">Rubricas fora do Encerramento</h2>
                    <div class="mp-panel-sub">Notas com estas rubricas não aparecem como candidatas na Lista de despacho. Ordens que já foram despachadas continuam visíveis no Controle. Padrão: <strong>{{ implode(', ', \App\Services\Closure\ClosureSettings::DEFAULT_EXCLUDED_RUBRICS) }}</strong>.</div>
                </div>
                <button type="button" class="btn btn-outline-secondary btn-sm btn-dash" wire:click="restoreDefaultRubrics"><i class="ri-restart-line" aria-hidden="true"></i> Voltar ao padrão</button>
            </div>
            <div class="mp-panel-body">
                <input type="search" class="form-control form-control-sm mb-2" placeholder="Buscar rubrica…" aria-label="Buscar rubrica" x-model="q">
                <div class="ops-settings-list">
                    @forelse ($rubrics as $rubric)
                        <label class="ops-settings-item" wire:key="rubric-{{ md5($rubric) }}" x-show="q === '' || @js(mb_strtolower($rubric)).includes(q.toLowerCase())">
                            <input class="form-check-input mt-0" type="checkbox" value="{{ $rubric }}" wire:model="excludedRubrics">
                            <span class="flex-grow-1">{{ $rubric }}</span>
                            @if (in_array($rubric, $excludedRubrics, true))
                                <x-ops.badge sm tone="amber" label="Fora do Encerramento" />
                            @endif
                        </label>
                    @empty
                        <x-closure.empty-state title="Nenhuma rubrica cadastrada" text="As rubricas vêm do campo Rubrica das Notas." icon="ri-price-tag-3-line" />
                    @endforelse
                </div>
            </div>
        </section>

        <section class="mp-panel" aria-labelledby="cfg-map">
            <div class="mp-panel-head">
                <div>
                    <h2 class="mp-panel-title" id="cfg-map">Caixas das atividades</h2>
                    <div class="mp-panel-sub">Escolha quais status do usuário (SAP) aparecem na caixa de Medição e na de Fiscalização.</div>
                </div>
                <a href="{{ route('closure.status_map') }}" class="btn btn-outline-secondary btn-sm btn-dash"><i class="ri-route-line" aria-hidden="true"></i> Mapa de status por serviço</a>
            </div>
        </section>
    </div>

    <div class="ops-settings-actions">
        <button type="button" class="btn btn-primary btn-dash" wire:click="save" wire:loading.attr="disabled" wire:target="save"><i class="ri-save-line" aria-hidden="true"></i> Salvar configurações</button>
    </div>
</div>
