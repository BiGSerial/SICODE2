@php
    use App\Helpers\SelectOptions;

    $selectOptions = collect(SelectOptions::getProtocolReasons());

    $filtersConfig = [
        [
            'type' => 'select',
            'label' => 'Por página',
            'model' => 'perPage',
            'placeholder' => 'Selecione',
            'multiple' => false,
            'size' => 1,
            'source' => [
                'mode' => 'array',
                'data' => [
                    ['value' => 10, 'label' => '10'],
                    ['value' => 25, 'label' => '25'],
                    ['value' => 50, 'label' => '50'],
                    ['value' => 100, 'label' => '100'],
                ],
            ],
            'option_value' => 'value',
            'option_label' => 'label',
        ],

        [
            'type' => 'multiselect', // dropdown com checkboxes
            'label' => 'Rubrica',
            'model' => 'rubricas', // array no componente pai
            'placeholder' => 'Selecione Rubrica',
            'searchable' => false,
            'source' => [
                'mode' => 'eloquent',
                'model' => \App\Models\Note::class,
                'key' => 'rubrica',
                'label' => 'rubrica',
                'orderBy' => ['rubrica', 'asc'],
                // 'where' => [['active','=',1]]
            ],
            'option_value' => 'id', // não se aplica aqui
            'option_label' => 'nick', // não se aplica aqui
        ],

        [
            'type' => 'multiselect', // dropdown com checkboxes
            'label' => 'Entidade',
            'model' => 'entities', // array no componente pai
            'placeholder' => 'Selecione Entidade',
            'searchable' => false,
            'source' => [
                'mode' => 'eloquent',
                'model' => \App\Models\Entity::class,
                'key' => 'id',
                'label' => 'name',
                'orderBy' => ['name', 'asc'],
                // 'where' => [['active','=',1]]
            ],
            'option_value' => 'id', // não se aplica aqui
            'option_label' => 'nick', // não se aplica aqui
        ],

        [
            'type' => 'multiselect', // dropdown com checkboxes
            'label' => 'Tipo de Entidade',
            'model' => 'types', // array no componente pai
            'placeholder' => 'Selecione tipos',
            'searchable' => false,
            'source' => [
                'mode' => 'eloquent',
                'model' => \App\Models\EntityType::class,
                'key' => 'id',
                'label' => 'name',
                'orderBy' => ['name', 'asc'],
                // 'where' => [['active','=',1]]
            ],
            'option_value' => 'id', // não se aplica aqui
            'option_label' => 'name', // não se aplica aqui
        ],
    ];

@endphp

<div>
    <x-show-loading />


    <div class="card mb-3 shadow-sm border-0">
        <div class="card-body">
            <div class="row g-2 align-items-end">

                {{-- Busca com wildcards (usa seu WildcardFormatter: * e ? => %) --}}
                <div class="col-12 col-md-4">
                    <label class="form-label mb-1 d-flex align-items-center gap-2">
                        Buscar
                        <a class="small text-decoration-none" data-bs-toggle="collapse" href="#wildHelp" role="button"
                            aria-expanded="false" aria-controls="wildHelp">
                            (ajuda)
                        </a>
                    </label>
                    <input type="text" class="form-control" placeholder="Ex.: 12345*  |  *ABC  |  A?C"
                        wire:model.debounce.400ms="search" autocomplete="off">
                    <div class="collapse mt-1 small text-muted" id="wildHelp">
                        <div class="card card-body p-2">
                            <div><strong>*</strong> = vários caracteres (vira <code>%</code>)</div>
                            <div><strong>?</strong> = 1 caractere (no seu formatter simples também vira <code>%</code>)
                            </div>
                            <div class="mt-1">Ex.: <code>123*</code> → LIKE <code>123%</code> | <code>*ABC</code> →
                                LIKE <code>%ABC</code></div>
                            <div>Sem wildcard → igualdade exata (<code>=</code>).</div>
                        </div>
                    </div>
                </div>

                <x-filters.dynamic :filters="$filtersConfig" applyAction="applyFilters" class="mb-2" />
            </div>


        </div>
    </div>

    @if ($lists->isNotEmpty())
        <div class="my-1 d-flex justify-content-between align-items-center">
            <div>
                {{ $lists->links() }}
            </div>
            <div class="text-muted small">
                Exibindo {{ $lists->firstItem() }} a {{ $lists->lastItem() }} de {{ $lists->total() }} registros
            </div>

        </div>
    @endif
    <div class="card shadow-sm border-0">
        <div class="card-header d-flex align-items-center justify-content-between">
            <div>
                <h5 class="mb-0">Pendências não classificadas</h5>
                <small class="text-muted">
                    Serviço: {{ $service->service ?? '—' }}
                </small>
            </div>

            <div class="d-flex align-items-center gap-3">
                <div class="d-flex align-items-center">
                    <label for="perPage" class="me-2 text-muted small">Por página</label>
                    <select id="perPage" class="form-select form-select-sm" wire:model="perPage"
                        style="min-width: 88px">
                        <option value="10">10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="card-body p-0 position-relative">

            {{-- Loading overlay --}}
            <div wire:loading.delay.class.remove="d-none" class="position-absolute top-0 start-0 w-100 h-100 d-none"
                style="background: rgba(0,0,0,.03); z-index: 2;">
                <div class="d-flex h-100 align-items-center justify-content-center">
                    <div class="spinner-border" role="status" aria-hidden="true"></div>
                    <span class="ms-2">Carregando…</span>
                </div>
            </div>



            @if ($lists->isEmpty())
                <div class="p-4 text-center text-muted">
                    <div class="mb-2">Nenhum registro encontrado para os critérios atuais.</div>
                    <small>Dica: confirme se o status não é <em>AGUARDANDO_PAGAMENTO/ORGAO/TAXA</em> e se
                        <code>completed</code> é <strong>false</strong>.</small>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="text-nowrap">#</th>
                                <th class="text-nowrap">Nota</th>
                                <th class="text-nowrap">Rubrica</th>
                                <th class="text-nowrap">Files</th>
                                <th class="text-nowrap">Município</th>
                                <th class="text-nowrap">Centro/Status</th>
                                <th class="text-nowrap">Entidade</th>
                                <th class="text-nowrap">Tipo Entidade</th>
                                <th class="text-nowrap">Usuário</th>
                                <th class="text-nowrap">Status</th>
                                <th class="text-nowrap">Ultima Movimentação</th>
                                <th class="text-nowrap text-end">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($lists as $i => $ext)
                                @php
                                    $s =
                                        $selectOptions->firstWhere('value', $ext->status) ??
                                        $selectOptions->firstWhere('value', 'INDEFINIDO');
                                @endphp
                                <tr>
                                    <td class="text-muted">
                                        {{ ($lists->currentPage() - 1) * $lists->perPage() + $i + 1 }}
                                    </td>

                                    {{-- Nota (número / link, se houver rota) --}}
                                    <td class="fw-semibold">
                                        {{ $ext->Note->note ?? '—' }}
                                    </td>

                                    <td class="fw-semibold">
                                        {{ $ext->Note->rubrica ?? '—' }}
                                    </td>

                                    <td class="fw-semibold">
                                        <x-files.select-download-list :files='$ext->Note->Files' />

                                    </td>

                                    {{-- LEXP --}}
                                    <td>
                                        {{ $ext->Note->lexp ?? '—' }}
                                    </td>

                                    {{-- Centro de trabalho --}}
                                    <td>
                                        <span
                                            class="text-muted">{{ $ext->Note->centerjob ?? $ext->Note->nstats }}</span>
                                    </td>

                                    {{-- Entidade (apelido e nome) --}}
                                    <td>
                                        @php
                                            $nick = $ext->Entity->nick ?? null;
                                            $name = $ext->Entity->name ?? null;
                                        @endphp
                                        @if ($nick || $name)
                                            <div class="d-flex flex-column">
                                                <span class="fw-semibold">{{ $nick ?? $name }}</span>
                                                @if ($nick && $name && $nick !== $name)
                                                    <small class="text-muted">{{ $name }}</small>
                                                @endif
                                            </div>
                                        @else
                                            —
                                        @endif
                                    </td>

                                    {{-- Tipo da Entidade --}}
                                    <td>
                                        <span
                                            class="badge bg-secondary-subtle text-secondary-emphasis border border-secondary-subtle">
                                            {{ $ext->Entity->Type->name ?? '—' }}
                                        </span>
                                    </td>

                                    {{-- Usuário responsável --}}
                                    <td>
                                        <div class="d-flex flex-column">
                                            <span>{{ $ext->User->name ?? '—' }}</span>
                                            @if (optional($ext->User)->Company)
                                                <small class="text-muted">{{ $ext->User->Company->name }}</small>
                                            @endif
                                        </div>
                                    </td>

                                    {{-- Status --}}
                                    <td>
                                        <span class="badge {{ $s->colorbg }} }}">{{ $s->value }}</span>
                                        @if (!$ext->status)
                                            <p class="my-0 py-0"><span class="badge text-bg-danger">Old:
                                                    {{ $ext->comments?->last()?->title ?? 'DESCONEHCIDO' }}</span></p>
                                        @endif

                                    </td>

                                    {{-- Completed (sempre false no filtro, mas exibimos para consistência visual) --}}
                                    {{-- <td>
                                    @if ($ext->completed)
                                        <span class="badge bg-success">Sim</span>
                                    @else
                                        <span class="badge bg-outline-secondary border">Não</span>
                                    @endif
                                </td> --}}

                                    {{-- Criado em --}}
                                    <td>
                                        @php
                                            $createdAt = $ext->last_comment_at
                                                ? \Carbon\Carbon::parse($ext->last_comment_at)
                                                : null;
                                            $daysAgo = $createdAt ? $createdAt->diffInDays(now()) : null;

                                            // Determina a cor da célula baseado nos dias
                                            $bgClass = 'bg-light';
                                            if ($daysAgo !== null) {
                                                if ($daysAgo > 30) {
                                                    $bgClass = 'bg-danger bg-opacity-25';
                                                } elseif ($daysAgo <= 20) {
                                                    $bgClass = 'bg-success bg-opacity-25';
                                                } else {
                                                    $bgClass = 'bg-warning bg-opacity-25';
                                                }
                                            }
                                        @endphp
                                        <div class="{{ $bgClass }} p-2 rounded">
                                            @if ($daysAgo !== null)
                                                <div class="fw-bold">
                                                    {{ $daysAgo }} {{ $daysAgo == 1 ? 'dia' : 'dias' }}
                                                </div>
                                                <small class="text-muted">
                                                    {{ $createdAt->format('d/m/Y H:i') }}
                                                </small>
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </div>
                                    </td>

                                    {{-- Ações (ajuste as rotas conforme seu projeto) --}}
                                    <td class="text-end">

                                        <div class="btn-group btn-group-sm" role="group" aria-label="Ações">
                                            <button type="button" class="btn btn-outline-primary"
                                                wire:click.prevent="redirectTo('{{ $ext->Note->note ?? '' }}')"
                                                title="Abrir (implemente a ação)">
                                                <i class="bi bi-box-arrow-up-right"></i>
                                            </button>
                                            {{-- <button type="button" class="btn btn-outline-secondary" disabled
                                            title="Marcar como revisto (implemente a ação)">
                                            <i class="bi bi-check2-square"></i>
                                        </button> --}}
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        @if ($lists->hasPages())
                            <tfoot>
                                <tr>
                                    <td colspan="11" class="p-3">
                                        {{ $lists->onEachSide(1)->links() }}
                                    </td>
                                </tr>
                            </tfoot>
                        @endif
                    </table>
                </div>
            @endif
        </div>
    </div>
</div>
