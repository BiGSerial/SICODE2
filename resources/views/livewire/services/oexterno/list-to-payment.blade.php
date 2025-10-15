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
            'type' => 'multiselect',
            'label' => 'Rubrica',
            'model' => 'rubricas',
            'placeholder' => 'Selecione Rubrica',
            'searchable' => false,
            'source' => [
                'mode' => 'eloquent',
                'model' => \App\Models\Note::class,
                'key' => 'rubrica',
                'label' => 'rubrica',
                'orderBy' => ['rubrica', 'asc'],
            ],
            'option_value' => 'id',
            'option_label' => 'nick',
        ],

        [
            'type' => 'multiselect',
            'label' => 'Entidade',
            'model' => 'entities',
            'placeholder' => 'Selecione Entidade',
            'searchable' => false,
            'source' => [
                'mode' => 'eloquent',
                'model' => \App\Models\Entity::class,
                'key' => 'id',
                'label' => 'name',
                'orderBy' => ['name', 'asc'],
            ],
            'option_value' => 'id',
            'option_label' => 'nick',
        ],

        [
            'type' => 'multiselect',
            'label' => 'Tipo de Entidade',
            'model' => 'types',
            'placeholder' => 'Selecione tipos',
            'searchable' => false,
            'source' => [
                'mode' => 'eloquent',
                'model' => \App\Models\EntityType::class,
                'key' => 'id',
                'label' => 'name',
                'orderBy' => ['name', 'asc'],
            ],
            'option_value' => 'id',
            'option_label' => 'name',
        ],
    ];
@endphp

<div>
    <x-show-loading />

    {{-- TOP: Filtros à ESQ + Uploader à DIR --}}
    <div class="card mb-3 shadow-sm border-0">
        <div class="card-body">
            <div class="row g-3 align-items-start">
                {{-- Coluna ESQUERDA (filtros) --}}
                <div class="col-12 col-lg-7">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label mb-1 d-flex align-items-center gap-2">
                                Buscar
                                <a class="small text-decoration-none" data-bs-toggle="collapse" href="#wildHelp"
                                    role="button" aria-expanded="false" aria-controls="wildHelp">(ajuda)</a>
                            </label>
                            <input type="text" class="form-control" placeholder="Ex.: 12345*  |  *ABC  |  A?C"
                                wire:model.debounce.400ms="search" autocomplete="off">
                            <div class="collapse mt-1 small text-muted" id="wildHelp">
                                <div class="card card-body p-2">
                                    <div><strong>*</strong> = vários caracteres (vira <code>%</code>)</div>
                                    <div><strong>?</strong> = 1 caractere (no seu formatter simples também vira
                                        <code>%</code>)
                                    </div>
                                    <div class="mt-1">Ex.: <code>123*</code> → LIKE <code>123%</code> |
                                        <code>*ABC</code> → LIKE <code>%ABC</code>
                                    </div>
                                    <div>Sem wildcard → igualdade exata (<code>=</code>).</div>
                                </div>
                            </div>
                        </div>

                        {{-- Filtros dinâmicos em 2 colunas --}}
                        <div class="col-12">
                            <x-filters.dynamic :filters="$filtersConfig" applyAction="applyFilters" />
                        </div>
                    </div>
                </div>

                {{-- Coluna DIREITA (uploader compacto com drag&drop + progresso) --}}
                <div class="col-12 col-lg-5">
                    @livewire('services.oexterno.helpers.pool-payment-updater', key('pool-payment-updater'))
                </div>
            </div> {{-- /row --}}
        </div>
    </div>

    {{-- LISTAGEM --}}
    @if ($lists->isNotEmpty())
        <div class="my-1 d-flex justify-content-between align-items-center">
            <div>{{ $lists->links() }}</div>
            <div class="text-muted small">Exibindo {{ $lists->firstItem() }} a {{ $lists->lastItem() }} de
                {{ $lists->total() }} registros</div>
        </div>
    @endif

    <div class="card shadow-sm border-0">
        <div class="card-header d-flex align-items-center justify-content-between">
            <div>
                <h5 class="mb-0">Pendências Aguardando Pagamento</h5>
                <small class="text-muted">Serviço: {{ $service->service ?? '—' }}</small>
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
                    <small>Dica: confirme se o status não é <em>AGUARDANDO_PAGAMENTO/ORGAO OU INDEFINIDO</em> e se
                        <code>completed</code> é <strong>false</strong>.</small>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light position-sticky top-0" style="z-index:1;">
                            <tr>
                                <th class="text-nowrap">Pool ID</th>
                                <th class="text-nowrap">Entidade</th>
                                <th class="text-nowrap">Nota</th>
                                <th class="text-nowrap">Status Pgt</th>
                                <th class="text-nowrap">Confirmação</th>
                                <th class="text-nowrap">Usuario</th>
                                <th class="text-nowrap">Última Interação</th>
                                <th class="text-end">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($lists as $ext)
                                @php
                                    $s =
                                        $selectOptions->firstWhere('value', $ext->status) ??
                                        $selectOptions->firstWhere('value', 'INDEFINIDO');
                                    $payments = $ext->PoolPayments->last() ?? null;
                                    $statusClass = match ($payments->status_pedido ?? 'Novo Pedido') {
                                        'Em Elaboração' => 'text-bg-secondary',
                                        'Concluído', 'Pago' => 'text-bg-success',
                                        'Rejeitado' => 'text-bg-danger',
                                        'Solicitado' => 'text-bg-secondary',
                                        default => 'text-bg-secondary',
                                    };
                                    $createdAt = $ext->last_comment_at
                                        ? \Carbon\Carbon::parse($ext->last_comment_at)
                                        : null;
                                    $daysAgo = $createdAt ? $createdAt->startOfDay()->diffInDays(now()->startOfDay()) : null;
                                    $bgClass = 'bg-light';
                                    if ($daysAgo !== null) {
                                        $bgClass =
                                            $daysAgo > 30
                                                ? 'bg-danger bg-opacity-25'
                                                : ($daysAgo <= 20
                                                    ? 'bg-success bg-opacity-25'
                                                    : 'bg-warning bg-opacity-25');
                                    }
                                @endphp
                                <tr>
                                    <td class="text-nowrap">
                                        <span
                                            class="badge text-bg-light fs-6 fw-bold">{{ $payments->pool_id ?? '—' }}</span>
                                    </td>
                                    <td class="text-nowrap">{{ $ext->entity->name ?? '—' }}</td>
                                    <td class="text-nowrap fw-bold fs-6">{{ $ext->Note->note ?? '—' }}</td>
                                    <td class="text-nowrap"><span
                                            class="badge {{ $statusClass }}">{{ $payments->status_pedido ?? 'Novo Pedido' }}</span>
                                    </td>
                                    <td class="text-nowrap">{{ $payments->fi_fbv0 ?? '—' }}</td>
                                    <td class="text-nowrap">{{ $payments->user->name ?? '—' }}</td>
                                    <td>
                                        <div class="{{ $bgClass }} p-2 rounded">
                                            @if ($daysAgo !== null)
                                                <div class="fw-bold">{{ $daysAgo }}
                                                    {{ $daysAgo == 1 ? 'dia' : 'dias' }}</div>
                                                <small class="text-muted">{{ $createdAt->format('d/m/Y H:i') }}</small>
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="text-end">
                                        <div class="btn-group btn-group-sm" role="group" aria-label="Ações">
                                            <button type="button" class="btn btn-outline-primary"
                                                wire:click.prevent="redirectTo('{{ $ext->Note->note ?? '' }}')"
                                                title="Abrir">
                                                <i class="bi bi-box-arrow-up-right"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>

                        @if ($lists->hasPages())
                            <tfoot>
                                <tr>
                                    <td colspan="11" class="p-3">{{ $lists->onEachSide(1)->links() }}</td>
                                </tr>
                            </tfoot>
                        @endif
                    </table>
                </div>
            @endif
        </div>
    </div>
</div>

{{-- ESTILOS DO DROPZONE COMPACTO (responsivo) --}}
<style>
    .dz-compact .dz-area {
        border: 1px dashed rgba(0, 0, 0, .2);
        border-radius: .75rem;
        min-height: 160px;
        /* compacto */
        background: #0b0f14;
        /* combina com seu tema escuro */
        color: #ced4da;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all .15s ease-in-out;
    }

    .dz-compact .dz-area.is-dragging {
        border-color: #6ea8fe;
        background: rgba(13, 110, 253, .08);
        box-shadow: inset 0 0 0 2px rgba(13, 110, 253, .35);
    }

    .dz-compact .dz-inner .bi {
        opacity: .85
    }

    @media (max-width: 991.98px) {
        .dz-compact .dz-area {
            min-height: 140px;
        }
    }
</style>

{{-- ALPINE CONTROLLER DO DROPZONE --}}
<script>
    function compactDropzone() {
        return {
            drag: false,
            uploading: false,
            progress: 0,
            fileName: '',
            handleDrop(e) {
                this.drag = false;
                const file = e.dataTransfer.files?.[0];
                if (!file) return;
                this.pushFileToLivewire(file);
            },
            handleInput(e) {
                const file = e.target.files?.[0];
                if (!file) return;
                this.pushFileToLivewire(file);
            },
            pushFileToLivewire(file) {
                this.fileName = file.name;
                // Cria um DataTransfer temporário só para preencher o input oculto
                const dt = new DataTransfer();
                dt.items.add(file);
                this.$refs.file.files = dt.files;
                // O wire:model="upload" do input já dispara o upload
            }
        }
    }
</script>
