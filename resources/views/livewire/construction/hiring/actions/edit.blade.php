<div>
    <x-show-loading />
    <div wire:ignore.self class="modal fade" id="modal_edit_hiring" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content edp-bg-stategrey-50">
                <div class="modal-header edp-bg-sprucegreen-70 text-edp-verde">
                    @if ($isBulk)
                        <h4 class="modal-title fs-5">Edição em Massa ({{ count($ids) }} itens)</h4>
                    @elseif ($viability)
                        <h4 class="modal-title fs-5">Informação de {{ $viability->Note->note }}</h4>
                    @else
                        <h4 class="modal-title fs-5">Editar Viabilidade</h4>
                    @endif
                </div>

                <div class="container-fluid my-3">
                    <div class="col-md-12">
                        {{-- Dados da nota: só no modo individual --}}
                        @if (!$isBulk && $viability)
                            <div class="card">
                                <div class="card-header py-1 edp-bg-sprucegreen-70 text-edp-verde">
                                    <h4 class="fs-6 my-0 py-0">Dados da Nota</h4>
                                </div>
                                <div class="card-body py-1 my-0">
                                    <div class="table-responsive">
                                        <table class="table table-sm">
                                            <tbody>
                                                <tr>
                                                    <td class="col-2 fw-bold align-middle">Nota/OV:</td>
                                                    <td class="col align-middle">{{ $viability->Note->note }}</td>
                                                </tr>
                                                <tr>
                                                    <td class="col-2 fw-bold align-middle">Status:</td>
                                                    <td class="col align-middle">{{ $viability->Note->nstats }}</td>
                                                </tr>
                                                <tr>
                                                    <td class="col-2 fw-bold align-middle">Situação:</td>
                                                    <td class="col align-middle">{{ $viability->Note->status }}</td>
                                                </tr>
                                                <tr>
                                                    <td class="col-2 fw-bold align-middle">Município:</td>
                                                    <td class="col align-middle">{{ $viability->Note->lexp }}</td>
                                                </tr>
                                                <tr>
                                                    <td class="col-2 fw-bold align-middle">Rubrica:</td>
                                                    <td class="col align-middle">{{ $viability->Note->rubrica }}</td>
                                                </tr>
                                                <tr>
                                                    <td class="col-2 fw-bold align-middle">Material:</td>
                                                    <td class="col align-middle">{{ $viability->Note->material }}</td>
                                                </tr>
                                                <tr>
                                                    <td class="col-2 fw-bold align-middle">Responsável Atual:</td>
                                                    <td class="col align-middle">
                                                        {{ optional($viability->Engineer)->name ? $viability->Engineer->name . " ({$viability->Engineer->email})" : '---' }}
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td class="col-2 fw-bold align-middle">Parceira Atual:</td>
                                                    <td class="col align-middle">
                                                        {{ optional($viability->Company)->name ?? '---' }}
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        @endif

                        {{-- Formulário de alteração (individual e massa) --}}
                        <div class="card mt-3">
                            <h5 class="card-header py-1 my-0 edp-bg-sprucegreen-70 text-edp-verde">
                                {{ $isBulk ? 'APLICAR PARA TODOS SELECIONADOS' : ($rehiring ? 'RECONTRATAR PARA' : 'ALTERAR PARA') }}
                            </h5>

                            <div class="card-body">
                                <div class="mb-3 col-6">
                                    <label class="form-label">Parceira:</label>
                                    <select class="form-select form-select-sm border-secondary"
                                        aria-label="Small select example" wire:model="companyS">
                                        <option value=""> --- </option>
                                        @foreach ($companies as $company)
                                            <option value="{{ $company->id }}">{{ $company->name }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="mb-3 col-6">
                                    <label class="form-label">Responsável:</label>
                                    <select class="form-select form-select-sm border-secondary"
                                        aria-label="Small select example" wire:model.defer="user_s">
                                        <option value=""> --- </option>
                                        @foreach ($users as $user)
                                            <option value="{{ $user->id }}">{{ $user->name }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="form-check form-switch mb-3">
                                    <input class="form-check-input" type="checkbox" role="switch" id="switchNewSend"
                                        wire:model.defer="newsend">
                                    <label class="form-check-label" for="switchNewSend">
                                        ENVIAR COMO NOVA VIABILIDADE
                                    </label>
                                </div>

                                @if (!$isBulk)
                                    <div class="mb-3">
                                        <button class="btn btn-sm btn-primary" wire:click.prevent="toAlterViability()">
                                            SALVAR
                                        </button>
                                    </div>
                                @else
                                    <div class="mb-3 d-flex align-items-center gap-2">
                                        <span class="hx-muted small">Itens selecionados: {{ count($ids) }}</span>
                                        <button class="btn btn-sm btn-primary" wire:click.prevent="toAlterViability()">
                                            APLICAR EM MASSA
                                        </button>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Fechar</button>
                </div>
            </div>
        </div>
    </div>
</div>
