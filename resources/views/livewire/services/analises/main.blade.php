@php
    use Carbon\Carbon;
    use App\Custom\Notestatus;
@endphp
<div class="oexterno-page">
    <x-show-loading />

    <style>
        .oexterno-page {
            --oe-bg: #f6f7fb;
            --oe-surface: #ffffff;
            --oe-ink: #1f2933;
            --oe-muted: #6b7280;
            --oe-border: #e5e7eb;
            background: radial-gradient(circle at 10% 0%, #eef2ff, transparent 40%),
                radial-gradient(circle at 90% 10%, #ecfeff, transparent 35%),
                var(--oe-bg);
            padding: 1.25rem 0;
        }

        .oexterno-header {
            background: linear-gradient(120deg, #0f172a, #0f766e 70%);
            color: #f8fafc;
            border-radius: .5rem;
            padding: 1.25rem 1.5rem;
            box-shadow: 0 16px 40px rgba(15, 23, 42, 0.2);
            margin-bottom: 1rem;
        }

        .filter-card,
        .summary-bar,
        .table-card {
            background: var(--oe-surface);
            border: 1px solid var(--oe-border);
            box-shadow: 0 10px 24px rgba(15, 23, 42, 0.05);
        }

        .filter-card {
            border-radius: .4rem;
            padding: .9rem 1rem;
            height: 100%;
        }

        .summary-bar {
            border-radius: .3rem;
            padding: .7rem 1rem;
        }

        .table-card {
            border-radius: .15rem;
            overflow: hidden;
        }

        .table-card .table thead th {
            font-size: .75rem;
            text-transform: uppercase;
            letter-spacing: .04em;
            white-space: nowrap;
        }
    </style>

    <div class="container-fluid">
        <div class="oexterno-header d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3">
            <div>
                <h2 class="mb-0">{{ mb_strtoupper($service->service) }}</h2>
                <div class="small">Gestão de atribuições</div>
            </div>
            <div class="text-lg-end small">
                @if ($last_update)
                    Última Atualização: <strong>{{ Carbon::parse($last_update)->diffForHumans() }}</strong>
                @endif
            </div>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-12 col-lg-5">
                <div class="filter-card">
                    <div class="row g-2">
                        <div class="col-12 col-md-4">
                            <div class="form-floating">
                                <select class="form-select border border-secondary" wire:model="perPage" id="perPage">
                                    <option value="25">25</option>
                                    <option value="50">50</option>
                                    <option value="100">100</option>
                                    <option value="200">200</option>
                                    <option value="500">500</option>
                                </select>
                                <label for="perPage">Registros/página</label>
                            </div>
                        </div>
                        <div class="col-12 col-md-8">
                            <div class="form-floating position-relative">
                                <input wire:model.bounce.2s="search" type="text" class="form-control border border-secondary"
                                    id="search" placeholder="Buscar">
                                <label for="search">Buscar</label>
                                <button class="btn btn-outline-secondary position-absolute end-0 top-50 translate-middle-y me-2"
                                    data-bs-toggle="modal" data-bs-target="#buscar_multi">
                                    <i class="ri-checkbox-multiple-blank-line"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-lg-4">
                <div class="filter-card">
                    <div class="dropdown mb-2">
                        <button class="btn btn-outline-secondary dropdown-toggle w-100" type="button" data-bs-toggle="dropdown"
                            aria-expanded="false">
                            Rubrica
                            @if (count($rubrica_s))
                                <span class="badge text-bg-light">{{ count($rubrica_s) }}</span>
                            @endif
                        </button>
                        <div class="dropdown-menu w-100" style="max-height: 350px; overflow-y: auto;">
                            <form wire:submit.prevent="filter_save">
                                @if (isset($rubrica_l) && $rubrica_l->count() > 0)
                                    @foreach ($rubrica_l as $rubrica)
                                        @if ($rubrica->rubrica)
                                            <div class="dropdown-item">
                                                <input type="checkbox" wire:model.defer="rubrica_s"
                                                    wire:key="{{ $rubrica->rubrica }}" value="{{ $rubrica->rubrica }}">
                                                <label>{{ $rubrica->rubrica }}</label>
                                            </div>
                                        @endif
                                    @endforeach
                                @endif
                            </form>
                        </div>
                    </div>
                    <div class="btn-group w-100">
                        <button class="btn btn-primary" wire:click.prevent="filter_save"><i class="ri-filter-fill"></i> Aplicar</button>
                        <button class="btn btn-primary" wire:click.prevent="filter_clean"><i class="ri-filter-off-fill"></i> Limpar</button>
                    </div>
                </div>
            </div>

            <div class="col-12 col-lg-3">
                <div class="filter-card d-flex flex-column gap-2">
                    <button type="button" class="btn btn-{{ Notestatus::status(1)->color }}" wire:click.prevent="filterStatus()">
                        {{ Notestatus::status(1)->status }}
                        @if ($not_assigned)
                            <span class="badge text-bg-success">ON</span>
                        @else
                            <span class="badge text-bg-danger">OFF</span>
                        @endif
                    </button>

                    <button class="btn btn-outline-success" wire:click.prevent="go_att_mass" @disabled(!count($selected))>
                        <i class="ri-user-add-line"></i> Atribuir selecionados ({{ count($selected) }})
                    </button>
                </div>
            </div>
        </div>

        @if ($lists->count())
            <div class="summary-bar mb-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>{{ $lists->links() }}</div>
                <div>
                    Exibindo <strong>{{ $lists->firstItem() }}</strong> até <strong>{{ $lists->lastItem() }}</strong> de
                    <strong>{{ $lists->total() }}</strong> registros.
                </div>
            </div>
        @endif

        <div class="table-card">
            @if (!$lists->count())
                <div class="card-body">
                    <h4 class="text-center">SEM NOTAS PARA EXIBIR EM {{ $service->service }}</h4>
                </div>
            @else
                <h4 class="card-header fw-bold text-bg-secondary mb-0">LISTA PARA {{ mb_strtoupper($service->service) }}
                    @if ($service->Status->count())
                        @foreach ($service->Status->where('exclusion', false)->unique('value') as $sts)
                            ({{ $sts->value }})
                        @endforeach
                    @endif
                </h4>

                <div class="table-responsive">
                    <table class="table table-sm table-striped table-condensed mb-0">
                        <thead class="table-dark">
                            <tr class="text-center">
                                <th>
                                    <input class="form-check-input" type="checkbox" wire:model="selectAll"
                                        wire:click="setSelectAllFiltered" @checked($this->checkAllSelect())>
                                </th>
                                <th scope="col" class="fw-bold">Note</th>
                                <th scope="col" class="fw-bold">Criado Em</th>
                                <th scope="col" class="fw-bold">numPedido</th>
                                <th scope="col" class="fw-bold">Rubrica</th>
                                <th scope="col" class="fw-bold">Municipio</th>
                                @can('management')
                                    <th scope="col" class="fw-bold">Grp1</th>
                                @endcan
                                <th scope="col" class="fw-bold">Grp2</th>
                                @can('management')
                                    <th scope="col" class="fw-bold">Descrição</th>
                                @endcan
                                <th scope="col" class="fw-bold">Status</th>
                                <th scope="col" class="fw-bold">Data</th>
                                <th scope="col" class="fw-bold">Prazo Real</th>
                                <th scope="col" class="fw-bold">Situação</th>
                                <th scope="col" class="fw-bold"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($lists as $list)
                                @php
                                    $block = 0;
                                    $productions = $list->Productions->where('service_id', $this->service->uuid);
                                    $lastProduction = $productions->last();
                                    $user = [];

                                    $getUserInfo = function ($production) use ($productions) {
                                        $fullName = $production->User->name ?? 'Desconhecido';
                                        $company = $production->Company->name ?? 'Desconhecido';
                                        $nameParts = explode(' ', $fullName);
                                        $shortName = count($nameParts) > 1 ? $nameParts[0] . ' ' . end($nameParts) : $nameParts[0];

                                        return [
                                            'lastUser' => $shortName,
                                            'countProd' => $productions->count(),
                                            'status' => $production->status ?? 'Desconhecido',
                                            'company' => explode(' ', $company)[0],
                                        ];
                                    };

                                    if ($lastProduction && $lastProduction->dt_note != $list->dt_status) {
                                        $user = $getUserInfo($lastProduction);
                                    } elseif ($lastProduction && !$lastProduction->completed && !$lastProduction->confirmed) {
                                        $block = 1;
                                        $user = $getUserInfo($lastProduction);
                                    } elseif ($lastProduction && $lastProduction->completed && !$lastProduction->confirmed) {
                                        $block = 2;
                                        $user = $getUserInfo($lastProduction);
                                    } elseif ($lastProduction && $lastProduction->completed && $lastProduction->confirmed) {
                                        $block = 3;
                                        $user = $getUserInfo($lastProduction);
                                    } elseif ($lastProduction && $lastProduction->dt_note === $list->dt_status) {
                                        $block = 3;
                                        $user = $getUserInfo($lastProduction);
                                    }
                                @endphp

                                <tr class="align-middle
                                    @if ($block == 1 && $user['lastUser'] != 'Desconhecido') table-primary
                                    @elseif($block == 1 && $user['lastUser'] == 'Desconhecido') table-warning
                                    @elseif($block == 2) table-success
                                    @elseif($block == 3) table-danger @endif">
                                    <td class="text-center">
                                        <input class="form-check-input border border-1 border-primary" type="checkbox"
                                            value="{{ $list->id }}" wire:model.defer="selected">
                                    </td>

                                    @if (Auth()->User()->management || Auth()->User()->superadm || ($lastProduction && $lastProduction->user_id === Auth()->User()->id))
                                        <td class="fw-bold copy-text" data-value="{{ $list->note }}">{{ $list->note }}</td>
                                    @else
                                        <td></td>
                                    @endif

                                    <td class="fw-light">{{ date('d/m/Y', strToTime($list->dt_created)) }}</td>
                                    <td class="fw-light">{{ mb_strtoupper($list->numPedido) }}</td>
                                    <td class="fw-light">{{ $list->rubrica }}</td>
                                    <td class="fw-light">{{ $list->lexp }}</td>
                                    @can('management')
                                        <td class="fw-light">{{ $list->group1 }}</td>
                                    @endcan
                                    <td class="fw-light">{{ $list->group2 }}</td>
                                    @can('management')
                                        <td class="fw-light">{{ $list->material }}</td>
                                    @endcan
                                    <td class="fw-light">{{ $list->nstats }}</td>
                                    <td class="fw-light">{{ date('d/m/Y H:i:s', strToTime($list->dt_status)) }}</td>
                                    <td class="text-center
                                        @if ($list->days_left < 0) text-bg-secondary
                                        @elseif($list->days_left >= 0 && $list->days_left < 6) table-danger
                                        @elseif($list->days_left >= 6 && $list->days_left < 10) table-warning
                                        @else table-success @endif">
                                        {{ 30 - $list->days_left }}
                                    </td>
                                    <td class="fw-light">
                                        @if ($list->pze_parecer === 'Vencido')
                                            <span class="badge text-bg-danger">VENCIDO</span>
                                        @elseif ($list->pze_parecer === 'Não vencido')
                                            <span class="badge text-bg-success">EM PRAZO</span>
                                        @else
                                            <span class="badge text-bg-secondary">DESCONHECIDO</span>
                                        @endif
                                    </td>

                                    <td class="fw-bold text-center">
                                        @if (!$block)
                                            <i class="ri-play-circle-line my-0 align-middle text-success fs-4"
                                                style="cursor: pointer;" wire:click.prevent="to_accompany({{ $list->id }})"
                                                data-bs-toggle="tooltip" data-bs-placement="top"
                                                data-bs-title="Enviar para Acompanhamento"></i>
                                        @else
                                            <span style="font-size: 11px">{{ $user['lastUser'] }}</span>
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
            <div class="summary-bar mt-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>{{ $lists->links() }}</div>
                <div>
                    Exibindo <strong>{{ $lists->firstItem() }}</strong> até <strong>{{ $lists->lastItem() }}</strong> de
                    <strong>{{ $lists->total() }}</strong> registros.
                </div>
            </div>
        @endif

        <div wire:ignore.self class="modal fade" id="buscar_multi" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content edp-bg-stategrey-50">
                    <div class="modal-header edp-bg-sprucegreen-70 text-edp-verde">Buscar Multi-Notas</div>
                    <div>
                        <textarea class="form-control" name="advanceSearch" id="advanceSearch" cols="50" rows="10"
                            wire:model.defer="advanceSearch"></textarea>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-primary" wire:click="buscarMulti">OK</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('script')
    <script>
        const copyTextCells = document.querySelectorAll('.copy-text');

        copyTextCells.forEach(cell => {
            cell.addEventListener('click', () => {
                const value = cell.getAttribute('data-value');
                copyToClipboard(value);
                livewire.emit('getCopy', `Valor "${value}" copiado para a área de transferência.`);
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
    </script>
@endpush
