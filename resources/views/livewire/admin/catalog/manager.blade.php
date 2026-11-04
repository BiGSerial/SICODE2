@php
    $lastLevel = count($definition['levels']) - 1;
@endphp

{{-- Cadastro de categorias e motivos: padrão em docs/admin/CATALOGOS.md (colunas categoria → subcategoria → item). --}}
<div class="closure-dash my-panel ops-list ops-catalog">

    <x-ops.hero
        eyebrow="Administração · Catálogos"
        title="Categorias e motivos"
        subtitle="Cadastre as opções que aparecem nas atividades: escolha a atividade, depois adicione, ordene, ative ou desative sem sair da tela."
        :updated-at="null" />

    <div class="cat-layout {{ $only ? 'is-single' : '' }}">

        @if (!$only)
            <aside class="mp-panel cat-nav" aria-label="Atividades com cadastro">
                @foreach ($groups as $group)
                    <div class="cat-nav-group">
                        <div class="cat-nav-title"><i class="{{ $group['icon'] }}" aria-hidden="true"></i> {{ $group['label'] }}</div>
                        @foreach ($group['catalogs'] as $item)
                            <button type="button" wire:click="pickCatalog('{{ $item['key'] }}')" wire:key="cat-nav-{{ $item['key'] }}" @class(['cat-nav-item', 'is-active' => $item['key'] === $catalog])
                                title="{{ $item['hint'] }}">
                                <i class="{{ $item['icon'] }}" aria-hidden="true"></i>
                                <span class="cat-nav-label">{{ $item['label'] }}</span>
                                <span class="cat-nav-count">{{ $item['count'] }}</span>
                            </button>
                        @endforeach
                    </div>
                @endforeach
            </aside>
        @endif

        <div class="cat-main">
            <section class="mp-panel" aria-label="{{ $definition['label'] }}">
                <div class="mp-panel-head">
                    <div>
                        <h2 class="mp-panel-title">{{ $definition['label'] }}</h2>
                        <div class="mp-panel-sub">{{ $definition['hint'] }}</div>
                    </div>
                    <span class="mp-pill" wire:loading.delay>Atualizando…</span>
                </div>

                <div class="mp-panel-body cat-tools">
                    <x-ops.search wire:model.debounce.500ms="search" placeholder="Filtrar por nome ou código" label="Filtrar este cadastro" />
                    <div class="cat-consumers">
                        <span class="ops-toolbar-label">Usado em</span>
                        @foreach ($definition['consumers'] as $consumer)
                            <x-ops.badge sm tone="muted" :label="$consumer" />
                        @endforeach
                    </div>
                </div>

                @if (($definition['settings'] ?? null) === 'survey_service')
                    <div class="mp-panel-body cat-settings">
                        <label class="ops-toolbar-label" for="cat-survey-service">Serviço de levantamento</label>
                        <select id="cat-survey-service" class="form-select form-select-sm" wire:model="surveyService">
                            <option value="">Não definido</option>
                            @foreach ($services as $service)
                                <option value="{{ $service->uuid }}">{{ $service->service }}</option>
                            @endforeach
                        </select>
                        <span class="cat-hint">Define qual serviço localiza o último levantador nos relatórios da análise.</span>
                    </div>
                @endif

                <div class="cat-columns cat-columns--{{ count($definition['levels']) }}">
                    @foreach ($columns as $column)
                        @php
                            $config  = $column['config'];
                            $level   = $column['level'];
                            $isLast  = $level === $lastLevel;
                            $single  = mb_strtolower($config['label']);
                        @endphp
                        <div class="cat-col" wire:key="cat-col-{{ $catalog }}-{{ $level }}">
                            <header class="cat-col-head">
                                <div>
                                    <h3 class="cat-col-title">{{ $config['plural'] }}</h3>
                                    <span class="cat-col-sub">
                                        @if ($column['rows']->count() !== $column['total']) {{ $column['rows']->count() }} de @endif{{ $column['total'] }}
                                        @if (!empty($config['order'])) · na ordem em que aparecem @else · em ordem alfabética @endif
                                    </span>
                                </div>
                                <button type="button" class="cat-link" wire:click="openBulk({{ $level }})" data-bs-toggle="modal" data-bs-target="#cat_bulk">
                                    <i class="ri-list-check-2" aria-hidden="true"></i> Colar várias
                                </button>
                            </header>

                            <form class="cat-add" wire:submit.prevent="addItem({{ $level }})">
                                <input type="text" class="form-control form-control-sm" maxlength="120" autocomplete="off" placeholder="Nova {{ $single }}… (Enter adiciona)"
                                    aria-label="Nova {{ $single }}" wire:model.defer="add.{{ $level }}">
                                <button type="submit" class="btn btn-primary btn-sm ops-action" wire:loading.attr="disabled" wire:target="addItem({{ $level }})">
                                    <i class="ri-add-line" aria-hidden="true"></i> Adicionar
                                </button>
                            </form>

                            @if ($column['rows']->isEmpty())
                                <x-closure.empty-state
                                    :title="$column['total'] ? 'Nada com esse filtro' : 'Nenhum item ainda'"
                                    :text="$column['total'] ? 'Limpe o filtro para ver todos.' : 'Digite um nome acima e tecle Enter para cadastrar o primeiro.'"
                                    icon="ri-inbox-line" />
                            @else
                                <ul class="cat-list" role="list">
                                    @foreach ($column['rows'] as $row)
                                        @php $isSelected = !$isLast && (int) ($selected[$level] ?? 0) === (int) $row->getKey(); @endphp
                                        <li wire:key="cat-row-{{ $catalog }}-{{ $level }}-{{ $row->getKey() }}"
                                            @class(['cat-row', 'is-selected' => $isSelected, 'is-inactive' => !empty($config['active']) && !$row->active])>
                                            @if ($isLast)
                                                <button type="button" class="cat-row-main" wire:click="edit({{ $level }}, {{ $row->getKey() }})" data-bs-toggle="modal" data-bs-target="#cat_edit" title="Editar">
                                            @else
                                                <button type="button" class="cat-row-main" wire:click="select({{ $level }}, {{ $row->getKey() }})" aria-pressed="{{ $isSelected ? 'true' : 'false' }}">
                                            @endif
                                                <span class="cat-row-name">{{ $row->name }}</span>
                                                <span class="cat-row-meta">
                                                    @if (!empty($config['code']) && $row->code)
                                                        <x-ops.badge sm mono tone="teal" :label="$row->code" hint="Código" />
                                                    @endif
                                                    @if (!empty($config['active']) && !$row->active)
                                                        <x-ops.badge sm tone="muted" label="Inativo" hint="Não aparece mais nas listas das atividades" />
                                                    @endif
                                                    @foreach ($config['usage'] ?? [] as $relation => $usageLabel)
                                                        @php $count = $row->{\Illuminate\Support\Str::snake($relation) . '_count'} ?? 0; @endphp
                                                        @if ($count > 0)
                                                            <span class="cat-use" title="{{ $count }} {{ $usageLabel }}">{{ $count }} {{ $usageLabel }}</span>
                                                        @endif
                                                    @endforeach
                                                </span>
                                            </button>

                                            <span class="cat-row-tools">
                                                @foreach ($config['flags'] ?? [] as $flag => $meta)
                                                    <button type="button" @class(['cat-flag', 'is-on' => $row->{$flag}]) wire:click="toggleFlag({{ $level }}, {{ $row->getKey() }}, '{{ $flag }}')"
                                                        title="{{ $meta['hint'] }} Clique para {{ $row->{$flag} ? 'desligar' : 'ligar' }}." aria-pressed="{{ $row->{$flag} ? 'true' : 'false' }}">
                                                        <i class="{{ $meta['icon'] }}" aria-hidden="true"></i> {{ $meta['label'] }}
                                                    </button>
                                                @endforeach

                                                @if (!empty($config['active']))
                                                    <span class="form-check form-switch cat-switch" title="{{ $row->active ? 'Ativo: aparece nas listas. Clique para desativar.' : 'Inativo: não aparece. Clique para ativar.' }}">
                                                        <input class="form-check-input" type="checkbox" role="switch" @checked($row->active) wire:click="toggleActive({{ $level }}, {{ $row->getKey() }})"
                                                            aria-label="Ativo: {{ $row->name }}">
                                                    </span>
                                                @endif

                                                <div class="dropdown">
                                                    <button type="button" class="btn btn-outline-secondary btn-sm ops-row-menu" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Ações de {{ $row->name }}">
                                                        <i class="ri-more-2-fill" aria-hidden="true"></i>
                                                    </button>
                                                    <ul class="dropdown-menu dropdown-menu-end">
                                                        <li><button type="button" class="dropdown-item" wire:click="edit({{ $level }}, {{ $row->getKey() }})" data-bs-toggle="modal" data-bs-target="#cat_edit"><i class="ri-edit-line" aria-hidden="true"></i> Editar</button></li>
                                                        @if (!empty($config['order']))
                                                            <li><button type="button" class="dropdown-item" wire:click="move({{ $level }}, {{ $row->getKey() }}, 'up')" @disabled($loop->first)><i class="ri-arrow-up-line" aria-hidden="true"></i> Subir</button></li>
                                                            <li><button type="button" class="dropdown-item" wire:click="move({{ $level }}, {{ $row->getKey() }}, 'down')" @disabled($loop->last)><i class="ri-arrow-down-line" aria-hidden="true"></i> Descer</button></li>
                                                        @endif
                                                        <li><hr class="dropdown-divider"></li>
                                                        <li><button type="button" class="dropdown-item text-danger" wire:click="confirmDelete({{ $level }}, {{ $row->getKey() }})" data-bs-toggle="modal" data-bs-target="#cat_delete"><i class="ri-delete-bin-line" aria-hidden="true"></i> Excluir</button></li>
                                                    </ul>
                                                </div>
                                            </span>
                                        </li>
                                    @endforeach
                                </ul>
                            @endif
                        </div>
                    @endforeach

                    @if (count($columns) < count($definition['levels']))
                        <div class="cat-col cat-col--hint">
                            <x-closure.empty-state title="Escolha um item ao lado" text="As opções de dentro dele aparecem aqui." icon="ri-arrow-right-line" />
                        </div>
                    @endif
                </div>
            </section>
        </div>
    </div>

    {{-- MODAIS (uma raiz só: ficam dentro da div do componente) --}}
    <div wire:ignore.self class="modal fade ops-modal" id="cat_edit" tabindex="-1" aria-labelledby="catEditLabel" aria-hidden="true" x-data @cat-modal-close.window="window.bootstrap?.Modal.getInstance($el)?.hide()">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content closure-dash ops-list">
                <div class="modal-header">
                    <div>
                        <span class="modal-eyebrow">{{ $definition['label'] }}</span>
                        <h5 class="modal-title" id="catEditLabel">Editar {{ $editConfig ? mb_strtolower($editConfig['label']) : 'item' }}</h5>
                    </div>
                    <button type="button" class="btn-close btn-close-white ms-auto" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body">
                    @if ($editConfig)
                        <label class="form-label text-uppercase small fw-bold" for="cat-edit-name">Nome</label>
                        <input id="cat-edit-name" type="text" class="form-control" maxlength="120" wire:model.defer="form.name" wire:keydown.enter="saveEdit">

                        @if (!empty($editConfig['code']))
                            <label class="form-label text-uppercase small fw-bold mt-3" for="cat-edit-code">Código <span class="text-muted fw-normal">(opcional, único)</span></label>
                            <input id="cat-edit-code" type="text" class="form-control" maxlength="60" wire:model.defer="form.code">
                        @endif

                        @if (!empty($editConfig['description']))
                            <label class="form-label text-uppercase small fw-bold mt-3" for="cat-edit-description">Descrição <span class="text-muted fw-normal">(opcional)</span></label>
                            <textarea id="cat-edit-description" class="form-control" rows="3" maxlength="1000" wire:model.defer="form.description"></textarea>
                        @endif

                        @if (!empty($editConfig['flags']) || !empty($editConfig['active']))
                            <div class="cat-form-switches">
                                @if (!empty($editConfig['active']))
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" role="switch" id="cat-edit-active" wire:model.defer="form.active">
                                        <label class="form-check-label" for="cat-edit-active">Ativo <span class="cat-hint">aparece nas listas das atividades</span></label>
                                    </div>
                                @endif
                                @foreach ($editConfig['flags'] ?? [] as $flag => $meta)
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" role="switch" id="cat-edit-{{ $flag }}" wire:model.defer="form.{{ $flag }}">
                                        <label class="form-check-label" for="cat-edit-{{ $flag }}">{{ $meta['label'] }} <span class="cat-hint">{{ $meta['hint'] }}</span></label>
                                    </div>
                                @endforeach
                            </div>
                        @endif

                        @foreach ($editConfig['numbers'] ?? [] as $field => $meta)
                            <label class="form-label text-uppercase small fw-bold mt-3" for="cat-edit-{{ $field }}">{{ $meta['label'] }}</label>
                            <input id="cat-edit-{{ $field }}" type="number" min="{{ $meta['min'] }}" class="form-control" wire:model.defer="form.{{ $field }}">
                        @endforeach
                    @endif
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary btn-dash" data-bs-dismiss="modal">Fechar</button>
                    <button type="button" class="btn btn-primary btn-dash" wire:click="saveEdit" wire:loading.attr="disabled" wire:target="saveEdit"><i class="ri-save-line" aria-hidden="true"></i> Salvar</button>
                </div>
            </div>
        </div>
    </div>

    <div wire:ignore.self class="modal fade ops-modal" id="cat_bulk" tabindex="-1" aria-labelledby="catBulkLabel" aria-hidden="true" x-data @cat-modal-close.window="window.bootstrap?.Modal.getInstance($el)?.hide()">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content closure-dash ops-list">
                <div class="modal-header">
                    <div>
                        <span class="modal-eyebrow">{{ $definition['label'] }}</span>
                        <h5 class="modal-title" id="catBulkLabel">Adicionar várias {{ $bulkLevel !== null ? mb_strtolower($definition['levels'][$bulkLevel]['plural']) : '' }}</h5>
                    </div>
                    <button type="button" class="btn-close btn-close-white ms-auto" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body">
                    <p class="ops-modal-note">Cole uma por linha (de uma planilha, por exemplo). Nomes que já existem são ignorados.</p>
                    <textarea class="form-control" rows="9" wire:model.defer="bulkText" aria-label="Nomes, um por linha" placeholder="Primeiro nome&#10;Segundo nome&#10;Terceiro nome"></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary btn-dash" data-bs-dismiss="modal">Fechar</button>
                    <button type="button" class="btn btn-primary btn-dash" wire:click="saveBulk" wire:loading.attr="disabled" wire:target="saveBulk"><i class="ri-add-line" aria-hidden="true"></i> Adicionar todas</button>
                </div>
            </div>
        </div>
    </div>

    <div wire:ignore.self class="modal fade ops-modal" id="cat_delete" tabindex="-1" aria-labelledby="catDeleteLabel" aria-hidden="true" x-data @cat-modal-close.window="window.bootstrap?.Modal.getInstance($el)?.hide()">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content closure-dash ops-list">
                <div class="modal-header">
                    <div>
                        <span class="modal-eyebrow">{{ $definition['label'] }}</span>
                        <h5 class="modal-title" id="catDeleteLabel">Excluir</h5>
                    </div>
                    <button type="button" class="btn-close btn-close-white ms-auto" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-2">Excluir <strong>{{ $confirmName }}</strong>?</p>
                    <p class="ops-modal-note mb-0">Só exclui o que ainda não foi usado. Se já houver registros usando, o sistema avisa e você pode desativar em vez de excluir.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary btn-dash" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-danger btn-dash" wire:click="deleteConfirmed" wire:loading.attr="disabled" wire:target="deleteConfirmed"><i class="ri-delete-bin-line" aria-hidden="true"></i> Excluir</button>
                </div>
            </div>
        </div>
    </div>
</div>
