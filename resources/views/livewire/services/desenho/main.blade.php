@php
    use Carbon\Carbon;
    use App\Custom\Notestatus;
@endphp
<div class="desenho-page">
    {{-- Carrega o Loading da página --}}
    <x-show-loading />

    <style>
        .desenho-page {
            --des-bg: #f6f7fb;
            --des-surface: #ffffff;
            --des-ink: #1f2933;
            --des-muted: #6b7280;
            --des-accent: #0f766e;
            --des-border: #e5e7eb;
            background: radial-gradient(circle at 8% 0%, #ecfeff, transparent 38%),
                radial-gradient(circle at 92% 8%, #e0f2fe, transparent 30%),
                var(--des-bg);
            padding: 1rem 0 1.5rem;
        }

        .desenho-header {
            background: linear-gradient(120deg, #0f172a, #0f766e 72%);
            color: #f8fafc;
            border-radius: 0.55rem;
            padding: 1rem 1.25rem;
            box-shadow: 0 14px 28px rgba(15, 23, 42, 0.18);
            margin-bottom: 1rem;
        }

        .desenho-header h5 {
            margin: 0;
            font-weight: 700;
            letter-spacing: 0.02em;
        }

        .desenho-header .meta {
            color: rgba(248, 250, 252, 0.8);
            font-size: 0.9rem;
        }

        .filter-strip {
            background: var(--des-surface);
            border: 1px solid var(--des-border);
            border-radius: 0.5rem;
            padding: 1rem;
            box-shadow: 0 10px 24px rgba(15, 23, 42, 0.06);
            margin-bottom: 1rem;
        }

        .table-card {
            background: var(--des-surface);
            border: 1px solid var(--des-border);
            border-radius: 0.45rem;
            box-shadow: 0 14px 28px rgba(15, 23, 42, 0.07);
            overflow: hidden;
        }

        .table-card .table thead th {
            font-size: 0.75rem;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            white-space: nowrap;
        }

        .desenho-tabs .nav-link {
            border-radius: 0.4rem 0.4rem 0 0;
        }
    </style>

    <div class="container-fluid">
        <div class="desenho-header d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-2">
            <div>
                <h5>{{ mb_strtoupper($service->service) }}</h5>
                <div class="meta">Acompanhamento de producao</div>
            </div>
            <div class="meta">
                Ordenacao: Prioridade, D5, Tipo, Prazo Real, ID
            </div>
        </div>

        <div class="filter-strip">
            <div class="row justify-content-between align-items-end g-2">
                <div class="col-12 col-md-4 col-lg-3">
                    <label for="search" class="form-label mb-1">Buscar</label>
                    <input wire:model.bounce.2s="search" type="text" class="form-control border border-secondary"
                        id="search" placeholder="Buscar por nota, pedido, rubrica...">
                </div>
                <div class="col-12 col-md-auto">
                    <div class="btn-group" role="group" aria-label="Tipo da nota">
                        <input class="btn-check" type="radio" name="note_type" wire:model="note_type" value="1"
                            id="note-type-1">
                        <label class="btn btn-outline-primary" for="note-type-1">Nota</label>

                        <input class="btn-check" type="radio" name="note_type" wire:model="note_type" value="2"
                            id="note-type-2">
                        <label class="btn btn-outline-primary" for="note-type-2">OV</label>

                        <input class="btn-check" type="radio" name="note_type" wire:model="note_type" value=""
                            id="note-type-all">
                        <label class="btn btn-outline-primary" for="note-type-all">Ambos</label>
                    </div>
                </div>
            </div>

            @if (count($statusFilterOptions))
                <div class="mt-3">
                    <div class="btn-group flex-wrap" role="group" aria-label="Filtro de status">
                        @foreach ($statusFilterOptions as $statusOption)
                            @php
                                $isActiveStatusFilter = (string) ($statusFilter ?? '') === (string) ($statusOption['value'] ?? '');
                            @endphp
                            <button type="button"
                                class="btn {{ $statusOption['colorbg'] ?? 'text-bg-secondary' }} {{ $isActiveStatusFilter ? '' : 'opacity-75' }}"
                                wire:click.prevent="setStatusFilter('{{ $statusOption['value'] }}')">
                                {{ $statusOption['label'] }}
                                <span class="badge text-bg-light ms-1">
                                    {{ $statusOption['count'] }}
                                </span>
                            </button>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
        {{-- <div class="btn-group mb-3">
            <div class="dropdown mx-1">
                <button class="btn btn-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown"
                    aria-expanded="false">
                    Rubrica
                    @if (count($rubrica_s))
                        <span class="badge text-bg-light">{{ count($rubrica_s) }}</span>
                    @endif

                </button>

                <div class="dropdown-menu" style="max-height: 350px; overflow-y: auto;">
                    <form wire:submit.prevent="filter_save">
                        @if (isset($rubrica_l) && $rubrica_l->count() > 0)
                            @foreach ($rubrica_l as $rubrica)
                                @if ($rubrica->rubrica)
                                    <div class="dropdown-item">
                                        <input type="checkbox" wire:model.defer="rubrica_s"
                                            wire:key="{{ $rubrica->rubrica }}" value="{{ $rubrica->rubrica }}">
                                        <label for="opcao1">{{ $rubrica->rubrica }}</label>
                                    </div>
                                @endif
                            @endforeach

                        @endif


                    </form>
                </div>

                <div class="btn-group">
                    <button class="btn btn-primary mx-1" wire:click.prevent="filter_save"><i class="ri-filter-fill"></i>
                        Aplicar Filtro</button>
                    <button class="btn btn-primary mx-1" wire:click.prevent="filter_clean"><i
                            class="ri-filter-off-fill"></i> Limpar Filtro</button>

                </div>
            </div>
        </div> --}}
    </div>

    {{-- @can('superadm')
        <div class="row justify-content-start">
            <div class="col-2">
                <input wire:model.bounce.2s="user_search" type="email"
                    class="form-control border border-2 border-secondary" id="search" placeholder="Buscar">
            </div>

            <div class="col-3 mb-3">
                <div class="input-group">
                    <select class="form-select border border-2 border-secondary" aria-label="Default select example"
                        wire:model.defer="user_s">
                        @if ($user_l->count())
                            <option value="">Selecione Usuario</option>
                            @foreach ($user_l as $user)
                                <option value="{{ $user->id }}">{{ $user->name }}</option>
                            @endforeach
                        @endif
                    </select>


                    <button class="btn btn-primary " wire:click.prevent="visualizar" type="button">
                        Visualizar</button>
                </div>
            </div>
        </div>
    @endcan --}}

    <nav class="desenho-tabs mb-2">
        <div class="nav nav-tabs border-0" id="nav-tab" role="tablist">
            <button class="nav-link active" id="nav-production-tab" data-bs-toggle="tab" data-bs-target="#my_production"
                type="button" role="tab" aria-controls="nav-home" aria-selected="true"
                wire:click.prevent="$emit('refresh_accomany')">Produção</button>
            <button class="nav-link" id="nav-transfer-tab" data-bs-toggle="tab" data-bs-target="#transfer"
                type="button" role="tab" aria-controls="nav-profile" aria-selected="false"
                wire:click.prevent="$emit('refresh_translist')">Transferências @livewire('components.transprod.count', ['service_id' => $service->uuid], key('transfer_count'))</button>

        </div>
    </nav>

    <div class="tab-content" id="nav-tabContent">
        <div class="tab-pane fade show active" id="my_production" role="tabpanel" aria-labelledby="nav-home-tab"
            tabindex="0">
            @if ($lists->count())
                <div class="row">
                    <div class="col-6">
                        {{ $lists->links() }}
                    </div>
                    <div class="col-6 d-flex justify-content-end align-middle">
                        <span class="align-middle"> Exibindo {{ $lists->firstItem() }} até
                            {{ $lists->lastItem() }}
                            de {{ $lists->total() }}
                            registros.</span>
                    </div>
                </div>
            @endif
            <div class="table-card card border-0">

                @if (!$lists->count())
                    <div class="card-body">
                        <h4 class="text-center">VOCÊ NAO TEM TAREFA ATRIBUÍDA
                            <strong>{{ mb_strtoupper($service->service) }}</strong>
                            @if ($service->Status->count())
                                @foreach ($service->Status->where('exclusion', false)->unique('value') as $sts)
                                    ({{ $sts->value }})
                                @endforeach
                            @endif
                        </h4>
                    </div>
                @else
                    <h4 class="card-header fw-bold text-bg-dark">ACOMPANHAMENTO -
                        {{ mb_strtoupper($service->service) }} - @if ($service->Status->count())
                            @foreach ($service->Status->where('exclusion', false)->unique('value') as $sts)
                                ({{ $sts->value }})
                            @endforeach
                        @endif
                    </h4>

                    <div class="table-responsive">
                        <table class="table table-sm table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th scope="col" class="fw-bold">Note</th>
                                    <th scope="col" class="fw-bold">Files</th>
                                    <th scope="col" class="fw-bold">DOE</th>
                                    <th scope="col" class="fw-bold">GRP2</th>
                                    <th scope="col" class="fw-bold">numPedido</th>
                                    <th scope="col" class="fw-bold">Rubrica</th>
                                    <th scope="col" class="fw-bold">Municipio</th>
                                    <th scope="col" class="fw-bold">Zona</th>
                                    <th scope="col" class="fw-bold">Descrição</th>
                                    <th scope="col" class="fw-bold">Postes_L</th>
                                    <th scope="col" class="fw-bold">DStatus</th>
                                    <th scope="col" class="fw-bold">Dias Despachado</th>
                                    <th scope="col" class="fw-bold">Dias Atribuido</th>
                                    <th scope="col" class="fw-bold">Prazo Real</th>
                                    <th scope="col" class="fw-bold">Status</th>
                                    <th scope="col" class="fw-bold"></th>
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    $getDaysStatus = static function ($list): array {
                                        $days = $list->dt_status->diffInDays(now());

                                        if ($days > 6) {
                                            $bgColor = 'text-bg-danger';
                                        } elseif ($days < 4) {
                                            $bgColor = 'text-bg-success';
                                        } else {
                                            $bgColor = 'text-bg-warning';
                                        }

                                        return [
                                            'days' => $days,
                                            'bgColor' => $bgColor,
                                        ];
                                    };

                                @endphp
                                @foreach ($lists as $list)
                                    @php
                                        $dstatus = $getDaysStatus($list->note);
                                        $isProjectReviewTracked =
                                            in_array((int) $list->status, [30, 31, 32], true) &&
                                            (bool) ($list->has_project_review_cycle ?? false);

                                        $tableRowClass = '';
                                        if ($list->priority) {
                                            $tableRowClass = 'table-danger';
                                        }
                                    @endphp
                                    <tr class="align-middle">
                                        <td class="fw-bold {{ $tableRowClass }}">

                                            @if ($isProjectReviewTracked && (int) $list->status === 30)
                                                <span class="badge text-bg-warning fs-6" style="cursor: pointer;"
                                                    wire:click="openProjectReviewReadonly({{ $list->id }}, {{ $list->Note->id }})"
                                                    data-bs-toggle="tooltip" data-bs-placement="top"
                                                    title="Análise de projeto em andamento">
                                                    {{ $list->Note->note }}
                                                </span>
                                            @elseif ($isProjectReviewTracked && (int) $list->status === 31)
                                                <span class="badge text-bg-danger fs-6" style="cursor: pointer;"
                                                    wire:click="openProjectReviewReadonly({{ $list->id }}, {{ $list->Note->id }})"
                                                    data-bs-toggle="tooltip" data-bs-placement="top"
                                                    title="Abrir análise de projeto">
                                                    {{ $list->Note->note }}
                                                </span>
                                            @elseif ($isProjectReviewTracked && (int) $list->status === 32)
                                                <span class="badge text-bg-success fs-6" style="cursor: pointer;"
                                                    wire:click="openProjectReviewReadonly({{ $list->id }}, {{ $list->Note->id }})"
                                                    data-bs-toggle="tooltip" data-bs-placement="top"
                                                    title="Abrir análise de projeto">
                                                    {{ $list->Note->note }}
                                                </span>
                                            @elseif ($list->d5)
                                                <span class="badge text-bg-primary fs-6" style="cursor: pointer;"
                                                    wire:click="$emitTo('services.desenho.actions.responserinfo', 'getInfoResponse', {{ $list }})">
                                                    {{ $list->Note->note }}
                                                </span>
                                            @else
                                                {{ $list->Note->note }}
                                            @endif

                                            @if ($list->Note->pze == '25')
                                                <span tabindex="0" data-bs-toggle="popover"
                                                    data-bs-trigger="hover focus" data-bs-placement="top"
                                                    data-bs-title="NOTA EXPRESSA"
                                                    data-bs-content="Nota com prazo de execução de {{ $list->Note->pze }} dias"
                                                    style="z-index: 9999;" data-bs-toggle="tooltip"
                                                    data-bs-placement="top">
                                                    <i class="ri-fire-line text-danger fw-bold"></i>
                                                </span>
                                            @endif

                                            <span class="copy-text" data-value="{{ $list->Note->note }}"
                                                style="cursor: pointer;" tabindex="0" data-bs-toggle="popover"
                                                data-bs-trigger="hover focus" data-bs-placement="top"
                                                data-bs-content="Copiar Número da Nota"> <i
                                                    class="ri-file-copy-line"></i></span>

                                            @if ($list->priority)
                                                <i class="ri-alert-fill align-middle"
                                                    wire:click.prevent="$emit('infoPriority', '{{ $list->id }}')"
                                                    style="cursor: pointer;" tabindex="0" data-bs-toggle="popover"
                                                    data-bs-trigger="hover focus" data-bs-placement="top"
                                                    data-bs-title="Exibir Prioridade"
                                                    data-bs-content="Clique para visualizar a informação da prioridade desta nota/ov."></i>
                                            @endif
                                        </td>
                                        <td class="align-middle {{ $tableRowClass }}">
                                            {{-- Componente para gerar a lista de arquivos, precisa do array de Arquivos --}}
                                            <x-files.select-download-list :files='$list->Note->Files' />

                                        </td>
                                        <td class="fw-bold text-success text-center {{ $tableRowClass }}">
                                            @if ($list->Note->doe)
                                                <i class="ri-checkbox-circle-line"></i>
                                            @endif
                                        </td>
                                        <td class="fw-light {{ $tableRowClass }}">{{ $list->Note->group2 }}</td>
                                        <td class="fw-light {{ $tableRowClass }}">{{ $list->Note->numPedido }}</td>
                                        <td class="fw-light {{ $tableRowClass }}">{{ $list->Note->rubrica }}</td>
                                        <td class="fw-light {{ $tableRowClass }}">{{ $list->Note->lexp }}</td>
                                        <td class="fw-light {{ $tableRowClass }}">{{ $list->Note->group1 }}</td>
                                        <td class="fw-light {{ $tableRowClass }}">{{ $list->Note->material }}</td>
                                        <td class="fw-light {{ $tableRowClass }} text-center">
                                            {{ $list->note->postes ?? '---' }}
                                        </td>
                                        <td class="fw-light text-center {{ $dstatus['bgColor'] }}" tabindex="0"
                                            data-bs-toggle="popover" data-bs-trigger="hover focus"
                                            data-bs-placement="top" data-bs-title="Dias no Status"
                                            data-bs-content="
                                    <p>OBS: Os prazos para Nota não seguem com precisão, os prazos regulatórios como as OVs e deverão ser avaliados caso a caso.</p>
                                    <span class='fs-4 text-success'>&#9632;</span> < 4 NO PRAZO <br>
                                    <span class='fs-4 text-warning'>&#9632;</span> >= 4 VENCENDO <br>
                                    <span class='fs-4 text-danger'>&#9632;</span> > 6 VENCIDO <br>
                                    {{-- <span class='fs-4 text-secondary'>&#9632;</span> VENCIDO <br> --}}
                                    ">
                                            {{ $dstatus['days'] }}
                                        </td>
                                        <td class="fw-light text-center">
                                            {{ $list->dispatch_at ? Carbon::now()->diffInDays(Carbon::parse($list->dispatch_at)->format('Y-m-d')) : '---' }}
                                        </td>
                                        <td class="fw-light text-center">
                                            {{ Carbon::now()->diffInDays(Carbon::parse($list->att_at)->format('Y-m-d')) }}
                                        </td>

                                        <td scope="col"
                                            class="text-center fw-light @if (isset($list->Note->days_left) && $list->Note->days_left < 0) text-bg-secondary @elseif($list->Note->days_left >= 0 && $list->Note->days_left < 6) table-danger @elseif($list->Note->days_left >= 6 && $list->Note->days_left < 10) table-warning @else table-success @endif"
                                            tabindex="0" data-bs-toggle="popover" data-bs-trigger="hover focus"
                                            data-bs-placement="top" data-bs-title="Prazo Real"
                                            data-bs-content="
                                <p>Os prazos contados já foram expurgado os tempos em status não contabilizáveis.</p>
                                <span class='fs-4 text-success'>&#9632;</span> 10> DIAS PARA VENCER <br>
                                <span class='fs-4 text-warning'>&#9632;</span> 10< DIAS PARA VENCER <br>
                                <span class='fs-4 text-danger'>&#9632;</span> 5< DIAS PARA VENCER <br>
                                <span class='fs-4 text-secondary'>&#9632;</span> VENCIDO <br>
                                ">
                                            {{ isset($list->Note->days_left) ? 30 - $list->Note->days_left : '---' }}
                                        </td>
                                        {{-- <td class="fw-light">
                                                {{ Carbon::now()->diffInDays(Carbon::parse($list->Note->dt_status)->format('Y-m-d')) }}
                                            </td> --}}

                                        <td class="fw-light text-center {{ $tableRowClass }}">

                                            <span class="badge {{ Notestatus::status($list->status)->colorbg }}"
                                                wire:click="$emitTo('components.status.show-status', 'showStatus',  {{ $list }}, {{ $list->status }})"
                                                style="cursor: pointer;">{{ Notestatus::status($list->status)->status }}</span>
                                        </td>
                                        <td class="fw-bold fs-5 {{ $tableRowClass }}">
                                            @if (!$list->block)
                                                @if (!$list->completed || $isProjectReviewTracked)
                                                    @if ($isProjectReviewTracked && (int) $list->status === 30)
                                                        {{-- Em Análise de Projeto: sem ícones de ação para evitar reencerramento por engano. --}}
                                                    @else
                                                        <span class="d-inline-block" data-bs-toggle="tooltip"
                                                            data-bs-placement="top" data-bs-custom-class="custom-tooltip"
                                                            data-bs-title="Iniciar.">
                                                            <i class="ri-play-circle-line text-success m-0 align-middle"
                                                                style="cursor: pointer;"
                                                                wire:click.prevent="getAnalise({{ $list->id }}, {{ $list->Note->id }})"></i>
                                                        </span>
                                                    @endif
                                                    @if (!$isProjectReviewTracked)
                                                        <span class="d-inline-block" data-bs-toggle="tooltip"
                                                            data-bs-placement="top" data-bs-custom-class="custom-tooltip"
                                                            data-bs-title="Transferir.">
                                                            <i class="ri-exchange-fill m-0 align-middle text-primary"
                                                                style="cursor: pointer;" {{-- data-bs-toggle="modal" data-bs-target="#analise_form" --}}
                                                                wire:click.prevent="goTransferProd({{ $list->id }})"></i>
                                                        </span>
                                                    @endif
                                                @endif
                                            @endif
                                        </td>


                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                @endif


            </div>
            @if ($lists->count())
                <div class="row">
                    <div class="col-6">
                        {{ $lists->links() }}
                    </div>
                    <div class="col-6 d-flex justify-content-end align-middle">
                        <span class="align-middle"> Exibindo {{ $lists->firstItem() }} até
                            {{ $lists->lastItem() }}
                            de {{ $lists->total() }}
                            registros.</span>
                    </div>
                </div>
            @endif
        </div>


        <div class="tab-pane fade" id="transfer" role="tabpanel" aria-labelledby="nav-profile-tab" tabindex="0">
            @livewire('components.transprod.translist', ['service' => $service->id])
        </div>
    </div>


    </div>

    <!-- Modal -->
    <div wire:ignore.self class="modal fade" id="analise_form" data-bs-backdrop="static" data-bs-keyboard="false"
        tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
        <div class="modal-dialog modal-fullscreen modal-dialog-scrollable">
            <div class="modal-content h-100">
                <div class="modal-header text-bg-success">
                    <h1 class="modal-title fs-5 text-center" id="staticBackdropLabel">
                        {{ mb_strtoupper($service->service) }}
                    </h1>
                </div>
                <div class="modal-body">
                    @livewire('services.desenho.forms.analise', ['modalContext' => 'finish'], key('desenho-form-finish'))
                </div>
            </div>
        </div>
    </div>

    <div wire:ignore.self class="modal fade" id="analise_review_form" data-bs-backdrop="static" data-bs-keyboard="false"
        tabindex="-1" aria-labelledby="reviewStaticBackdropLabel" aria-hidden="true">
        <div class="modal-dialog modal-fullscreen modal-dialog-scrollable">
            <div class="modal-content h-100">
                <div class="modal-header text-bg-success">
                    <h1 class="modal-title fs-5 text-center" id="reviewStaticBackdropLabel">
                        {{ mb_strtoupper($service->service) }}
                    </h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    @livewire('services.desenho.forms.analise', ['modalContext' => 'review'], key('desenho-form-review'))
                </div>
                <div class="modal-footer">
                    @if ($reviewCanFinish)
                        <button type="button" class="btn btn-primary" id="go-finish-from-review-btn">
                            Ir para encerramento
                        </button>
                    @endif
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        Fechar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal -->
    <div wire:ignore.self class="modal fade" id="pause_note" data-bs-backdrop="static" data-bs-keyboard="false"
        tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content h-100">
                <div class="modal-header text-bg-warning">
                    <h1 class="modal-title fs-5 text-center" id="staticBackdropLabel">
                        PARAR {{ mb_strtoupper($service->service) }}
                    </h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    @livewire('components.pausenote.pausenote')
                </div>
                {{-- <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"
                        wire:click.prevent="$emit('analise_clean')">Close</button>
                    <button type="button" class="btn btn-primary">Understood</button>
                </div> --}}
            </div>
        </div>
    </div>

    {{-- LIVEWIRE COMPONENTS --}}
    @livewire('components.transprod.transprod', key('Transfer_production'))
    @livewire('services.desenho.actions.responserinfo', key('responser_info_return'))
    @livewire('components.status.show-status', key('show_status_note'))

    <div wire:init="checkOpen"></div>

</div>


@push('script')
    <script>
        window.__skipAnaliseClean = false;

        const copyTextCells = document.querySelectorAll('.copy-text');

        copyTextCells.forEach(cell => {
            cell.addEventListener('click', () => {
                const value = cell.getAttribute('data-value');
                copyToClipboard(value);
                livewire.emit('getCopy',
                    `Valor "${value}" copiado para a área de transferência.`);
                // alert(`Valor "${value}" copiado para a área de transferência.`);
            });
        });

        function copyToClipboard(text) {
            const textArea = document.createElement('textarea');
            textArea.value = text;
            document.body.appendChild(textArea);
            textArea.select();
            document.execCommand('copy');
            document.body.removeChild(textArea);
        }

        window.addEventListener("showModal2", function(e) {
            alert('Funciona')
            const myModal = new bootstrap.Modal(document.getElementById(e.detail.id))
            myModal.show();
        })

        window.addEventListener("hideModal2", function(e) {
            const myModal = new bootstrap.Modal(document.getElementById(e.detail.id))
            myModal.hide();
        })

        function bindAnaliseModalClean(modalId) {
            const modalEl = document.getElementById(modalId);
            if (!modalEl) return;
            modalEl.addEventListener('hidden.bs.modal', function() {
                if (window.__skipAnaliseClean) {
                    window.__skipAnaliseClean = false;
                    return;
                }

                Livewire.emitTo('services.desenho.forms.analise', 'analise_clean');
                cleanupModalArtifacts();
                clearProjectReviewQueryParams();
            });
        }

        function cleanupModalArtifacts() {
            const hasVisibleModal = !!document.querySelector('.modal.show');
            if (hasVisibleModal) return;

            document.body.classList.remove('modal-open');
            document.body.style.removeProperty('overflow');
            document.body.style.removeProperty('padding-right');
            document.querySelectorAll('.modal-backdrop').forEach(el => el.remove());
        }

        function clearProjectReviewQueryParams() {
            const params = new URLSearchParams(window.location.search);
            let changed = false;

            ['open_project_review', 'production', 'note', 'focus'].forEach(function(key) {
                if (params.has(key)) {
                    params.delete(key);
                    changed = true;
                }
            });

            if (!changed) return;

            const nextQuery = params.toString();
            const nextUrl = `${window.location.pathname}${nextQuery ? '?' + nextQuery : ''}${window.location.hash || ''}`;
            window.history.replaceState({}, document.title, nextUrl);
        }

        bindAnaliseModalClean('analise_form');
        bindAnaliseModalClean('analise_review_form');

        document.addEventListener('livewire:load', function() {
            // Reforço de retomada após refresh completo da página.
            setTimeout(function() {
                Livewire.emitTo('services.desenho.main', 'force_check_open');
            }, 350);

            const params = new URLSearchParams(window.location.search);
            const shouldOpenReview = params.get('open_project_review');
            const productionId = parseInt(params.get('production') || '', 10);
            const noteId = parseInt(params.get('note') || '', 10);

            if (shouldOpenReview && Number.isInteger(productionId) && productionId > 0) {
                const safeNoteId = Number.isInteger(noteId) && noteId > 0 ? noteId : 0;
                const openFromNotification = function() {
                    Livewire.emitTo('services.desenho.main', 'openProjectReviewFromNotification', productionId, safeNoteId);
                };

                openFromNotification();
                setTimeout(openFromNotification, 350);
            }

            clearProjectReviewQueryParams();
        });

        window.addEventListener('openProjectReviewModalFromServer', function(e) {
            const payload = e.detail?.payload;
            if (!payload || !payload.productionId) return;

            const openReviewModal = function() {
                Livewire.emitTo('services.desenho.forms.analise', 'open_analise_draw', payload);
                const modalEl = document.getElementById('analise_review_form');
                if (!modalEl) return;
                const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
                modal.show();
            };

            openReviewModal();
            setTimeout(openReviewModal, 250);
            setTimeout(openReviewModal, 700);
        });

        document.addEventListener('click', function(e) {
            const trigger = e.target.closest('#go-finish-from-review-btn');
            if (!trigger) return;

            const reviewModalEl = document.getElementById('analise_review_form');
            const finishModalEl = document.getElementById('analise_form');
            if (!reviewModalEl || !finishModalEl) return;

            // Fecha o modal atual e reabre no modo de encerramento.
            window.__skipAnaliseClean = true;

            const onHidden = function() {
                reviewModalEl.removeEventListener('hidden.bs.modal', onHidden);

                Livewire.emitTo('services.desenho.forms.analise', 'goToFinishFlow');

                setTimeout(function() {
                    const reopen = bootstrap.Modal.getOrCreateInstance(finishModalEl);
                    reopen.show();
                }, 180);
            };

            reviewModalEl.addEventListener('hidden.bs.modal', onHidden);
            const modal = bootstrap.Modal.getOrCreateInstance(reviewModalEl);
            modal.hide();
        });

        window.addEventListener('hideModal', function() {
            cleanupModalArtifacts();
            clearProjectReviewQueryParams();
        });
    </script>
@endpush
