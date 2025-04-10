@php
    use Carbon\Carbon;
    use App\Custom\Notestatus;
    use App\Custom\WpaStatus;
@endphp
<div>
    <x-show-loading />
    <x-showselected :count="$selected" />

    <div class="row">
        <div class="col-1">
            <label class="form-label">Por Página</label>
            <select wire:model.defer="perPage" class="form-select form-control-sm border border-2 border-secondary">
                <option value="25">25</option>
                <option value="50">50</option>
                <option value="100">100</option>
                <option value="250">250</option>
            </select>
        </div>
        <div class="mb-3 col-md-2">
            <label class="form-label">Buscar</label>
            <div class="input-group">
                <input wire:model.debounce.2s="search" type="search"
                    class="form-control border border-2 border-secondary" placeholder="Buscar">
                <button class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#buscar_multi">
                    <i class="ri-checkbox-multiple-blank-line"></i>
                </button>
            </div>
        </div>
    </div>

    <div class="card">
        @if ($lists->isEmpty())
            <div class="card-body">
                <h4 class="text-center">SEM NOTAS A EXIBIR CONTROLE EM
                    <strong>{{ mb_strtoupper($service->service) }}</strong>
                </h4>
            </div>
        @else
            <div class="card-header text-bg-danger">
                <div class="row align-items-center">
                    <div class="col">
                        <h4 class="my-0">CONTROLE DE {{ mb_strtoupper($service->service) }}</h4>
                    </div>
                    <div class="col-auto d-flex justify-content-end">
                        <button class="btn btn-sm btn-success me-2" data-bs-toggle="modal"
                            data-bs-target="#add_mass_dds">
                            <i class="ri-checkbox-multiple-fill"></i> Att DD
                        </button>

                        <div class="dropdown">
                            <button class="btn btn-sm btn-primary me-2 dropdown-toggle" type="button"
                                data-bs-toggle="dropdown" aria-expanded="false">
                                Ações em Massa
                            </button>
                            <ul class="dropdown-menu">
                                <li>
                                    <a class="dropdown-item" href="#" wire:click.prevent='go_att_mass'>
                                        <i class="ri-user-add-line text-primary"></i> Atribuir em Massa
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="#" wire:click.prevent='go_des_att_mass'>
                                        <i class="ri-user-shared-line text-danger"></i> Desatribuir em Massa
                                    </a>
                                    <div class="form-check ms-3">
                                        <input class="form-check-input border border-1 border-secondary" type="checkbox"
                                            wire:model.defer="forcar">
                                        <label class="form-check-label small">Forçar desatribuição</label>
                                    </div>
                                </li>
                            </ul>
                        </div>

                        <button class="btn btn-sm btn-primary me-2" wire:click.prevent='export_excel'>
                            <i class="ri-file-excel-2-line"></i> Exportar
                        </button>
                        <button class="btn btn-sm btn-primary" wire:click="$refresh">
                            <i class="ri-refresh-line"></i>
                        </button>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm table-striped table-condensed">
                        <thead class="table-dark">
                            <tr>
                                <th><input class="form-check-input" type="checkbox" wire:model="selectall"></th>
                                <th class="fw-bold text-center">Note</th>
                                <th class="fw-bold text-center">DD</th>
                                <th class="fw-bold text-center">stsDD</th>
                                <th class="fw-bold text-center">MMGD</th>
                                <th class="fw-bold text-center">Despachante</th>
                                <th class="fw-bold text-center">Grp2</th>
                                <th class="fw-bold text-center">Rubrica</th>
                                <th class="fw-bold text-center">Municipio</th>
                                <th class="fw-bold text-center">Zona</th>
                                <th class="fw-bold text-center">Descrição</th>
                                <th class="fw-bold text-center">Empresa</th>
                                <th class="fw-bold text-center">Usuário</th>
                                <th class="fw-bold text-center">Dias Despachado</th>
                                <th class="fw-bold text-center">Dias Atribuido</th>
                                <th class="fw-bold text-center">Prazo Real</th>
                                <th class="fw-bold text-center">Mensalização</th>
                                <th class="fw-bold text-center">Status</th>
                                <th class="fw-bold text-center">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($lists as $list)
                                <tr class="align-middle @if ($list->block) table-primary @endif">
                                    <td><input class="form-check-input border border-1 border-primary" type="checkbox"
                                            value="{{ $list->id }}" wire:model.defer="selected"></td>
                                    <td class="fw-bold @if ($list->priority) text-danger fw-bold @endif">
                                        @if ($list->d5)
                                            <span class="badge text-bg-primary fs-6">{{ $list->Note->note }} (RI)</span>
                                        @else
                                            {{ $list->Note->note }}
                                            <span class="copy-text" data-value="{{ $list->Note->note }}"
                                                style="cursor: pointer;">
                                                <i class="ri-file-copy-line"></i>
                                            </span>
                                        @endif
                                    </td>

                                    <td
                                        class="fw-light text-center @if ($list->priority) text-danger fw-bold @endif">
                                        @if ($list->Wpas->count())
                                            <a class="link-primary fw-bold"
                                                href="https://edp-wpa-po.azurewebsites.net/Search?q={{ $list->Wpas()->get()->last()->dd }}">
                                                <span class="text-primary">{{ $list->Wpas()->get()->last()->dd }}</span>
                                            </a>
                                        @else
                                            -----
                                        @endif
                                    </td>
                                    <td
                                        class="fw-light text-center @if ($list->priority) text-danger fw-bold @endif">
                                        @if ($list->wpas->count())
                                            @php
                                                $wpas = $list->wpas->last();
                                                $wpa = WpaStatus::status(
                                                    $wpas->stats,
                                                    $wpas->execstats,
                                                    $wpas->completed_at,
                                                );
                                            @endphp
                                            <i
                                                class="{{ $wpa->icon }} {{ $wpa->color }} fs-3 align-middle my-0"></i><br>
                                            <span class="badge {{ $wpa->bg_color }} my-0">{{ $wpa->info }}</span>
                                            <br>
                                        @else
                                            -----
                                        @endif
                                    </td>
                                    <td class="fw-bold text-danger text-center">{{ $list->Note->mmgd ? 'MMGD' : '' }}
                                    </td>
                                    <td
                                        class="fw-light text-center @if ($list->priority) text-danger fw-bold @endif">
                                        @php
                                            if ($list->Dispatcher) {
                                                $dispatcher = explode(' ', $list->Dispatcher->name);
                                                $dispatcher = $dispatcher[0] . ' ' . substr(end($dispatcher), 0, 1);
                                            } else {
                                                $dispatcher = 'DESCONEHCIDO';
                                            }
                                        @endphp
                                        {{ $dispatcher }}
                                    </td>
                                    <td
                                        class="fw-bold @if ($list->priority) text-danger fw-bold @endif text-center">
                                        {{ $list->Note->group2 ?? '____' }}
                                    </td>
                                    <td
                                        class="fw-light text-center @if ($list->priority) text-danger fw-bold @endif">
                                        {{ $list->Note->rubrica }}</td>
                                    <td
                                        class="fw-light text-center @if ($list->priority) text-danger fw-bold @endif">
                                        {{ $list->Note->lexp }}</td>
                                    <td
                                        class="fw-light text-center @if ($list->priority) text-danger fw-bold @endif">
                                        {{ $list->Note->group1 }}</td>
                                    <td
                                        class="fw-light text-center @if ($list->priority) text-danger fw-bold @endif">
                                        {{ $list->Note->material }}</td>
                                    <td
                                        class="fw-light text-center @if ($list->priority) text-danger fw-bold @endif">
                                        {{ $list->Company ? explode(' ', $list->Company->name)[0] : '-' }}</td>
                                    <td
                                        class="fw-light text-center @if ($list->priority) text-danger fw-bold @endif">
                                        @php
                                            $nome = $list->User ? explode(' ', $list->User->name) : '----';
                                            if (is_array($nome)) {
                                                $nome = $nome[0] . ' ' . substr(end($nome), 0, 1);
                                            }
                                        @endphp
                                        {{ $nome }}
                                    </td>
                                    <td
                                        class="fw-light text-center @if ($list->priority) text-danger fw-bold @endif">
                                        {{ $list->dispatch_at->diffInDays() }}
                                    </td>
                                    <td
                                        class="fw-light text-center @if ($list->priority) text-danger fw-bold @endif">
                                        {{ Carbon::now()->diffInDays(Carbon::parse($list->att_at)->format('Y-m-d')) }}
                                    </td>
                                    <td
                                        class="text-center @if ($list->Note->days_left < 0) text-bg-secondary
                                                             @elseif($list->Note->days_left >= 0 && $list->Note->days_left < 6) table-danger
                                                             @elseif($list->Note->days_left >= 6 && $list->Note->days_left < 10) table-warning
                                                             @else table-success @endif">
                                        {{ 30 - $list->Note->days_left }}
                                    </td>
                                    <td class="fw-light text-center">{{ $list->note->mesalization }}</td>
                                    <td class="fw-light text-center">
                                        @if ($list->transferred && $list->block_wpa)
                                            <span class="badge bg-warning">Aguardando Despacho</span>
                                        @else
                                            <span class="badge {{ Notestatus::status($list->status)->colorbg }}"
                                                wire:click="$emitTo('components.status.show-status', 'showStatus',  {{ $list }}, {{ $list->status }})"
                                                style="cursor: pointer;">{{ Notestatus::status($list->status)->status }}</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <x-production.action-production :production="$list" />
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </div>

    <div class="d-flex justify-content-between align-items-center mt-2">
        {{ $lists->links() }}
        <span>
            Exibindo {{ $lists->firstItem() }} até {{ $lists->lastItem() }} de {{ $lists->total() }} registros.
        </span>
    </div>


    <!-- Modals -->
    <div wire:ignore.self class="modal fade" id="buscar_multi" tabindex="-1"
        aria-labelledby="buscarMultiModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content edp-bg-stategrey-50">
                <div class="modal-header edp-bg-sprucegreen-70 text-edp-verde">
                    <h5 class="modal-title" id="buscarMultiModalLabel">Buscar Multi-Notas</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <textarea class="form-control" rows="10" wire:model.defer="advanceSearch"></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" wire:click="buscarMulti"
                        data-bs-dismiss="modal">OK</button>
                </div>
            </div>
        </div>
    </div>

    <div wire:ignore.self class="modal fade" id="add_mass_notes" tabindex="-1"
        aria-labelledby="addMassNotesModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content edp-bg-stategrey-50">
                <div class="modal-header edp-bg-sprucegreen-70 text-edp-verde">
                    <h5 class="modal-title" id="addMassNotesModalLabel">Despachar {{ $service }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"
                        wire:click.prevent="closeall"></button>
                </div>
                <div class="modal-body">
                    @if ($notes && $notes->count())
                        <div class="mb-3">
                            <label class="form-label">Empresa:</label>
                            <select class="form-select form-select-sm" wire:model="company_s">
                                <option value="" selected>Selecione</option>
                                @if ($company_l && $company_l->count())
                                    @foreach ($company_l as $company)
                                        <option value="{{ $company->id }}">{{ $company->name }}</option>
                                    @endforeach
                                @endif
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Usuário:</label>
                            <select class="form-select form-select-sm" wire:model="user_s">
                                @if ($user_l && $user_l->count())
                                    <option value="" selected>Selecione um Usuário</option>
                                    @foreach ($user_l as $user)
                                        <option value="{{ $user->id }}">{{ $user->name }}</option>
                                    @endforeach
                                @else
                                    <option value="" selected>Escolha uma Empresa Primeiro</option>
                                @endif
                            </select>
                        </div>

                        <div class="mb-2">
                            <label class="form-label">Relacionar DD em MASSA:</label>
                            <textarea class="form-control" rows="3" placeholder="<número OV/NOTA> <número DD> Ex: 4001123232 14034330"
                                wire:model.defer="enter_dd"></textarea>
                        </div>
                        <div class="mb-3">
                            <button class="btn-sm btn btn-primary" wire:click.prevent="add_dd">DD em MASSA</button>
                        </div>

                        <div class="col-12 fw-bold">DESPACHANDO {{ $notes->count() }} OV/NOTA(S)</div>

                        <div class="table-responsive">
                            <table class="table table-sm table-condensed table-striped">
                                <thead>
                                    <tr>
                                        <th scope="col">#</th>
                                        <th scope="col">Note</th>
                                        <th scope="col">Desc</th>
                                        <th scope="col">DD</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($notes as $index => $note)
                                        <tr>
                                            <td scope="col" class="fw-bold">{{ $index + 1 }}</td>
                                            <td>{{ $note->note }}</td>
                                            <td>{{ $note->material }}</td>
                                            <td>
                                                @php
                                                    $this->additionalData[$index] = $note->load('Wpas')
                                                        ? $note->load('Wpas')->Wpas->last()->dd
                                                        : '';
                                                @endphp
                                                <input wire:model.defer="additionalData.{{ $index }}"
                                                    class="form-control form-control-sm" type="text"
                                                    placeholder="Informe a DD">
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
                <div class="modal-footer edp-bg-sprucegreen-70">
                    <button class="btn-sm btn btn-danger" data-bs-dismiss="modal"
                        wire:click.prevent="closeall">Cancelar</button>
                    <button class="btn-sm btn btn-primary" wire:click.prevent="confirm_att"
                        wire:loading.attr="disabled" wire:target="confirm_att">
                        Despachar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div wire:ignore.self class="modal fade" id="add_mass_dds" tabindex="-1" aria-labelledby="addMassDdsModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content edp-bg-stategrey-50">
                <div class="modal-header edp-bg-sprucegreen-70 text-edp-verde">
                    <h5 class="modal-title" id="addMassDdsModalLabel">Atribuir DD em {{ $service->service }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"
                        wire:click.prevent="closeall"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Relacionar DD em MASSA 1:</label>
                        <textarea class="form-control" rows="10" style="resize: none;"
                            placeholder="<número OV/NOTA> <número DD> Ex: 4001123232 14034330" wire:model.defer="enter_dd"></textarea>
                    </div>
                </div>
                <div class="modal-footer edp-bg-sprucegreen-70">
                    <button class="btn-sm btn btn-danger" data-bs-dismiss="modal"
                        wire:click.prevent="closeall">Cancelar</button>
                    <button class="btn-sm btn btn-primary" wire:click.prevent="mass_modal">Atribuir</button>
                </div>
            </div>
        </div>
    </div>
    <!-- End Modals -->

    @livewire('audits.info')
    @livewire('components.status.show-status', key('show_status_note'))

</div>

@push('script')
    <script>
        const copyTextCells = document.querySelectorAll('.copy-text');

        copyTextCells.forEach(cell => {
            cell.addEventListener('click', () => {
                const value = cell.getAttribute('data-value');
                copyToClipboard(value);
                Livewire.emit('getCopy', `Valor "${value}" copiado para a área de transferência.`);
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
