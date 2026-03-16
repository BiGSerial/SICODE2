<div>
    @php
        $filters = [
            [
                'key' => 'company',
                'label' => 'Empreiteira',
                'type' => 'multi',
                'provider' => [
                    'type' => 'eloquent',
                    'model' => \App\Models\Company::class,
                    'value' => 'id',
                    'label' => 'name',
                    'distinct' => true,
                    'orderBy' => ['name' => 'asc'],
                    'limit' => 300,
                ],
            ],
            [
                'key' => 'type',
                'label' => 'Tipo',
                'type' => 'single',
                'provider' => [
                    'type' => 'static',
                    'options' => [['value' => 2, 'label' => 'OV'], ['value' => 1, 'label' => 'NOTA']],
                ],
            ],
            [
                'key' => 'city',
                'label' => 'Município',
                'type' => 'multi',
                'provider' => [
                    'type' => 'eloquent',
                    'model' => \App\Models\City::class,
                    'value' => 'rdMunicipio',
                    'label' => 'cidade',
                    'distinct' => true,
                    'orderBy' => ['cidade' => 'asc'],
                    'limit' => 300,
                ],
            ],

            // [
            //     'key' => 'search',
            //     'label' => 'Pesquisar Nota',
            //     'type' => 'text',
            //     'placeholder' => 'Nº da Nota...',
            // ],
            [
                'key' => 'desired_between',
                'label' => 'Desejada (de/até)',
                'type' => 'daterange',
                'include_nulls' => false,
                'treat_zero_date_as_null' => false,
            ],
        ];
    @endphp


    {{-- Loading --}}
    <x-show-loading />

    {{-- Top Controls --}}
    <div class="d-flex flex-wrap gap-3 mb-3 align-items-center">
        <div class="flex-grow-1 position-relative">
            <input wire:model.debounce.500ms="search" class="form-control" id="searchInput" placeholder="Buscar..." />
            <button type="button"
                class="btn btn-outline-secondary position-absolute end-0 top-50 translate-middle-y me-2 border-0"
                data-bs-toggle="modal" data-bs-target="#buscarMultiModal" title="Busca múltipla">
                <i class="ri-checkbox-multiple-blank-line"></i>
            </button>
        </div>

        <select class="form-select w-auto" wire:model="perPage">
            <option value="25">25</option>
            <option value="50">50</option>
            <option value="100">100</option>
        </select>
    </div>

    @livewire('components.filters.bar', ['config' => $filters, 'group' => 'payments', 'manualApply' => true], key('filters-bar'))
    {{-- Header da tabela / ações --}}
    <div class="d-flex justify-content-between align-items-center mb-2">
        <h5 class="mb-0 text-uppercase d-flex align-items-center gap-2">
            <i class="ri-alert-line"></i>
            NOTAS D5 AGUARDANDO
        </h5>


    </div>

    @if (!empty($lists) && $lists->count() > 0)
        {{-- Paginação --}}
        <div class="d-flex justify-content-between align-items-center mt-2">
            {{ $lists?->links() }}
            <div class="text-muted small">
                Exibindo {{ $lists->firstItem() ?? 0 }} - {{ $lists->lastItem() ?? 0 }} de {{ $lists->total() }}
                registros
            </div>
        </div>
        {{-- Tabela compacta --}}
        <div class="table-responsive bg-white shadow-sm rounded">
            <table class="table table-sm table-hover table-striped modern-table align-middle mb-0">
                <thead class="table-dark">
                    <tr class="align-middle text-center">
                        <th style="width:15px;"> <input class="form-check-input" type="checkbox" wire:model="selectall"
                                wire:click="setSelectAll" @checked($this->checkAllSelect($lists))></th>
                        <th>Nota D5</th>
                        <th>Nota</th>

                        {{-- <th>Cod</th> --}}
                        <th>Empreiteira</th>
                        <th>Motivo</th>
                        <th>Cod</th>
                        <th>Data Despacho</th>
                        <th>Em Atividade</th>

                        <th></th>

                    </tr>
                </thead>
                <tbody>

                    @forelse ($lists as $list)
                        @php
                            $daysOverdue = $list->dispatch_at?->diffInDays();
                            $badgeClass = 'bg-success';
                            $badgeText = 'Dentro do prazo';

                            if ($daysOverdue > 3 && $daysOverdue <= 5) {
                                $badgeClass = 'bg-warning';
                                $badgeText = 'Atenção';
                            } elseif ($daysOverdue > 5) {
                                $badgeClass = 'bg-danger';
                                $badgeText = 'Atrasado';
                            }

                        @endphp
                        <tr class="text-center {{ $list->is_supervisioned ? 'table-success' : '' }}">
                            <td><input class="form-check-input border border-1 border-primary " type="checkbox"
                                    value="{{ $list->id }}" wire:model.defer="selected">
                            </td>
                            <td>{{ $list->note_d5 }}</td>
                            <td>{{ $list->note->note }}</td>
                            <td class="fw-bold">{{ $list->company?->name }}</td>
                            <td>{{ $list->reason }}</td>
                            <td>{{ $list->codify }}</td>
                            <td>{{ $list->dispatch_at?->format('d/m/Y H:i') }}</td>
                            <td>
                                <span class="badge {{ $badgeClass }}">
                                    <i class="ri-time-line me-1"></i> {{ $list->dispatch_at?->diffInDays() }} dias

                                </span>
                            </td>

                            <td>
                                <button class="btn btn-sm btn-primary p-1"
                                    wire:click="$emitTo('components.d5.d5details', 'openD5Details', {{ $list->note_id }})">
                                    Visualizar
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="11" class="text-center py-5">
                                <i class="ri-inbox-line fs-1 d-block mb-2"></i>
                                Nenhum registro encontrado.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Paginação --}}
        <div class="d-flex justify-content-between align-items-center mt-2">
            {{ $lists->links() }}
            <div class="text-muted small">
                Exibindo {{ $lists->firstItem() ?? 0 }} - {{ $lists->lastItem() ?? 0 }} de {{ $lists->total() }}
                registros
            </div>
        </div>
    @else
        <div class="d-flex justify-content-center align-items-center py-5">
            <div class="text-center">
                <div class="mb-3">
                    <i class="ri-inbox-line text-muted" style="font-size: 4rem; opacity: 0.5;"></i>
                </div>
                <h5 class="text-muted mb-2">Nada para exibir</h5>
                <p class="text-muted small mb-0">Não há registros disponíveis no momento.</p>
            </div>
        </div>
    @endif

    {{-- Drawer lateral de detalhes --}}
    @if ($showDetails && $selected)
        <div class="details-drawer details-drawer--modern shadow">
            <!-- Header -->
            <div class="drawer-header">
                <div class="drawer-title">
                    <div class="drawer-icon">
                        <i class="ri-file-list-3-line"></i>
                    </div>
                    <div>
                        <h5 class="mb-0">Nota #{{ $selected->nota }}</h5>
                        <small class="text-muted">Ficha detalhada</small>
                    </div>
                </div>

                <button class="btn btn-light btn-sm drawer-close" wire:click="closeDetails" aria-label="Fechar">
                    <i class="ri-close-line"></i>
                </button>
            </div>

            <!-- Status Strip -->
            <div class="drawer-strip">
                <span
                    class="badge rounded-pill bg-{{ !$selected->dtConclusaoDesej->isPast() ? 'success' : 'danger' }} me-2">
                    <i
                        class="{{ !$selected->dtConclusaoDesej->isPast() ? 'ri-check-line' : 'ri-error-warning-line' }} me-1"></i>
                    {{ !$selected->dtConclusaoDesej->isPast() ? 'No Prazo' : 'Vencido' }}
                </span>

                <div class="chip">
                    <i class="ri-community-line me-1"></i>{{ $selected->cidade }}
                </div>
                <div class="chip">
                    <i class="ri-price-tag-3-line me-1"></i>{{ $selected->txtGrpCodificacao }}
                </div>
            </div>

            <!-- Content (scrollable) -->
            <div class="drawer-content">
                <div class="info-grid">
                    <div class="info-card">
                        <div class="info-label"><i class="ri-map-pin-line me-1"></i>Município</div>
                        <div class="info-value">{{ $selected->cidade }}</div>
                    </div>
                    <div class="info-card">
                        <div class="info-label"><i class="ri-folder-2-line me-1"></i>Grupo</div>
                        <div class="info-value">{{ $selected->txtGrpCodificacao }}</div>
                    </div>
                    <div class="info-card">
                        <div class="info-label"><i class="ri-time-line me-1"></i>Abertura</div>
                        <div class="info-value">{{ $selected->dtAberturaNota?->format('d/m/Y') }}</div>
                    </div>
                    <div class="info-card">
                        <div class="info-label"><i class="ri-flag-line me-1"></i>Desejada</div>
                        <div class="info-value">{{ $selected->dtConclusaoDesej?->format('d/m/Y') }}</div>
                    </div>
                </div>

                <div class="divider"></div>

                <div class="desc-block">
                    <div class="desc-title">
                        <i class="ri-information-line me-2"></i>Descrição
                    </div>
                    <p class="mb-0 text-secondary">
                        {{ $selected->comments->last()?->message }}
                    </p>
                </div>

                {{-- Timeline opcional (só exibe se tiver datas) --}}
                @php
                    $timeline = [
                        [
                            'icon' => 'ri-file-add-line',
                            'label' => 'Abertura',
                            'date' => $selected->dtAberturaNota?->format('d/m/Y'),
                        ],
                        [
                            'icon' => 'ri-flag-2-line',
                            'label' => 'Desejada',
                            'date' => $selected->dtConclusaoDesej?->format('d/m/Y'),
                        ],
                    ];
                @endphp
                <div class="divider"></div>
                <div class="timeline">
                    @foreach ($timeline as $t)
                        @if (!empty($t['date']))
                            <div class="timeline-item">
                                <div class="timeline-dot"><i class="{{ $t['icon'] }}"></i></div>
                                <div class="timeline-content">
                                    <div class="timeline-label">{{ $t['label'] }}</div>
                                    <div class="timeline-date">{{ $t['date'] }}</div>
                                </div>
                            </div>
                        @endif
                    @endforeach
                </div>
            </div>

            <!-- Footer -->
            <div class="drawer-footer">
                <button class="btn btn-outline-secondary" wire:click="closeDetails">
                    <i class="ri-arrow-go-back-line me-1"></i> Fechar
                </button>
                <button class="btn btn-primary" wire:click="goTo({{ $selected->nota }})">
                    <i class="ri-external-link-line me-1"></i> Abrir Detalhes
                </button>
            </div>
        </div>
        <div class="details-drawer-backdrop" wire:click="closeDetails"></div>
    @endif


    {{-- Modal: Busca Múltipla --}}
    <div wire:ignore.self class="modal fade" id="buscarMultiModal" tabindex="-1" aria-labelledby="buscarMultiLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content shadow">
                <div class="modal-header">
                    <h5 class="modal-title" id="buscarMultiLabel">
                        <i class="ri-search-2-line me-2"></i>
                        Busca Múltipla de Notas
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body">
                    <div class="form-floating">
                        <textarea class="form-control" id="advanceSearch" style="height: 200px;"
                            placeholder="Cole aqui vários valores (vírgula ou quebra de linha)" wire:model.defer="advanceSearch"></textarea>
                        <label for="advanceSearch">Números / valores</label>
                    </div>
                    <div class="form-text">
                        Separe por vírgula <strong>,</strong> ou por quebra de linha.
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button class="btn btn-primary" wire:click="buscarMulti" data-bs-dismiss="modal">
                        <i class="ri-check-line me-1"></i>Aplicar Filtro
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Modals --}}
    @livewire('components.d5.d5details', key('five-note-details'))
    @livewire('components.five-note.manual-create', key('manual-create-five'))
    @livewire('components.five-note.edit-d5', key('edit-five-note'))

    {{-- Estilos customizados --}}

    <style>
        .modern-table th,
        .modern-table td {
            font-size: 0.98em;
            vertical-align: middle;
            padding: .40em .75em !important;
        }

        .modern-table .badge {
            font-size: 1em;
            padding: .36em 1.2em;
            letter-spacing: .03em;
        }

        .details-drawer {
            position: fixed;
            top: 0;
            right: 0;
            height: 100vh;
            width: 400px;
            background: #fff;
            border-left: 1px solid #eee;
            z-index: 1201;
            padding: 2rem 1.5rem 1rem 2rem;
            box-shadow: -2px 0 18px rgba(0, 0, 0, 0.10);
            animation: slideInDrawer .21s cubic-bezier(.6, -0.28, .74, .05);
        }

        /* --- Drawer Moderno --- */
        .details-drawer--modern {
            background: #ffffff;
            border-left: 0;
            width: 460px;
            padding: 0;
            overflow: hidden;
            border-radius: 16px 0 0 16px;
            box-shadow: -8px 0 28px rgba(0, 0, 0, .12);
            backdrop-filter: saturate(1.2) blur(6px);
        }

        @media (max-width: 900px) {
            .details-drawer--modern {
                width: 100vw;
                border-radius: 0;
            }
        }

        /* Header com gradiente e blur */
        .details-drawer--modern .drawer-header {
            position: sticky;
            top: 0;
            z-index: 2;
            background: linear-gradient(135deg, #0d6efd 0%, #4f8cff 100%);
            color: #fff;
            padding: 1rem 1.25rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 4px 16px rgba(13, 110, 253, .2);
        }

        .details-drawer--modern .drawer-title {
            display: flex;
            align-items: center;
            gap: .75rem;
        }

        .details-drawer--modern .drawer-icon {
            width: 40px;
            height: 40px;
            border-radius: 12px;
            background: rgba(255, 255, 255, .15);
            display: grid;
            place-items: center;
            font-size: 1.2rem;
        }

        /* Botão fechar */
        .details-drawer--modern .drawer-close {
            background: rgba(255, 255, 255, .15);
            border: 0;
            color: #fff;
            transition: transform .15s ease, background .15s ease;
        }

        .details-drawer--modern .drawer-close:hover {
            transform: rotate(90deg) scale(1.05);
            background: rgba(255, 255, 255, .25);
        }

        /* Faixa de status + chips */
        .details-drawer--modern .drawer-strip {
            padding: .75rem 1.25rem;
            background: linear-gradient(180deg, rgba(13, 110, 253, .06), rgba(13, 110, 253, 0));
            display: flex;
            align-items: center;
            gap: .5rem;
            flex-wrap: wrap;
        }

        .details-drawer--modern .chip {
            font-size: .82rem;
            background: #f1f5ff;
            color: #2752d3;
            border: 1px solid #e3ebff;
            padding: .25rem .6rem;
            border-radius: 999px;
            display: inline-flex;
            align-items: center;
        }

        /* Conteúdo rolável */
        .details-drawer--modern .drawer-content {
            height: calc(100vh - 176px);
            /* header + strip + footer */
            overflow-y: auto;
            padding: 1.25rem 1.25rem 1rem;
            scrollbar-width: thin;
            scrollbar-color: #b8c9ff transparent;
        }

        .details-drawer--modern .drawer-content::-webkit-scrollbar {
            width: 6px;
        }

        .details-drawer--modern .drawer-content::-webkit-scrollbar-thumb {
            background: #b8c9ff;
            border-radius: 3px;
        }

        /* Grid de infos */
        .details-drawer--modern .info-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: .75rem;
        }

        @media (max-width: 480px) {
            .details-drawer--modern .info-grid {
                grid-template-columns: 1fr;
            }
        }

        .details-drawer--modern .info-card {
            border: 1px solid #eef1f6;
            border-radius: 12px;
            padding: .75rem .9rem;
            background: #fff;
            transition: box-shadow .15s ease, transform .15s ease;
        }

        .details-drawer--modern .info-card:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 18px rgba(0, 0, 0, .06);
        }

        .details-drawer--modern .info-label {
            font-size: .78rem;
            color: #6b7a90;
            text-transform: uppercase;
            letter-spacing: .04em;
        }

        .details-drawer--modern .info-value {
            font-weight: 600;
            color: #2a2f3a;
            margin-top: .15rem;
        }

        /* Descrição */
        .details-drawer--modern .desc-block .desc-title {
            font-weight: 700;
            color: #334155;
            margin-bottom: .4rem;
            display: flex;
            align-items: center;
        }

        .details-drawer--modern .desc-block p {
            background: #f8fafc;
            border: 1px dashed #e5e7eb;
            border-radius: 12px;
            padding: .75rem .9rem;
        }

        /* Timeline */
        .details-drawer--modern .timeline {
            position: relative;
            margin-top: .5rem;
            padding-left: .75rem;
        }

        .details-drawer--modern .timeline:before {
            content: '';
            position: absolute;
            left: 10px;
            top: 6px;
            bottom: 6px;
            width: 2px;
            background: #e6ebff;
        }

        .details-drawer--modern .timeline-item {
            display: flex;
            gap: .75rem;
            position: relative;
            margin-bottom: .75rem;
        }

        .details-drawer--modern .timeline-dot {
            width: 22px;
            height: 22px;
            border-radius: 50%;
            background: #eaf0ff;
            color: #345bff;
            display: grid;
            place-items: center;
            z-index: 1;
            border: 2px solid #fff;
            box-shadow: 0 0 0 2px #e6ebff;
        }

        .details-drawer--modern .timeline-content .timeline-label {
            font-size: .82rem;
            color: #64748b;
            margin-bottom: .1rem;
        }

        .details-drawer--modern .timeline-date {
            font-weight: 600;
            color: #1f2937;
        }

        /* Footer fixo */
        .details-drawer--modern .drawer-footer {
            position: sticky;
            bottom: 0;
            background: linear-gradient(0deg, #ffffff 80%, rgba(255, 255, 255, 0));
            padding: .9rem 1.25rem 1.1rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: .75rem;
            border-top: 1px solid #eef1f6;
        }

        /* Divider suave */
        .details-drawer--modern .divider {
            height: 1px;
            background: linear-gradient(90deg, transparent, #eef1f6, transparent);
            margin: .9rem 0 1rem;
        }
    </style>
</div>
