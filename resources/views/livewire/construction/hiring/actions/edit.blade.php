<div>
    <div wire:ignore.self class="modal fade" id="modal_edit_hiring" tabindex="-1" aria-labelledby="exampleModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content  edp-bg-stategrey-50">
                @if ($note)
                    <div class="modal-header edp-bg-sprucegreen-70 text-edp-verde">
                        <h4 class="modal-title fs-5">Informação de {{ $note->note }}</h4>
                    </div>
                    <div class="container-fluid my-3">

                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-header py-1 edp-bg-sprucegreen-70 text-edp-verde">
                                    <h4 class="fs-5 my-0 py-0">Dados da Nota</h4>
                                </div>
                                <div class="card-body py-1 my-0">
                                    <div class="table-responsive">
                                        <table class="table table-sm">
                                            <tbody>
                                                <tr>
                                                    <td class="col-2 fw-bold align-middle">Nota/OV:</td>
                                                    <td class="col  align-middle">{{ $note->note }}</td>
                                                </tr>
                                                <tr>
                                                    <td class="col-2 fw-bold align-middle">Ordems:</td>
                                                    <td class="col align-middle">
                                                        @if ($note->Viabilities->count())
                                                            @foreach ($note->Viabilities as $viab)
                                                                <p class="my-1 py-0">{{ $viab->Order->ordem }}</p>
                                                            @endforeach
                                                        @endif
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td class="col-2 fw-bold  align-middle">Status:</td>
                                                    <td class="col  align-middle">{{ $note->nstats }}</td>
                                                </tr>
                                                <tr>
                                                    <td class="col-2 fw-bold align-middle">Situação:</td>
                                                    <td class="col align-middle align-middle">{{ $note->status }}
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td class="col-2 fw-bold align-middle">Municipio:</td>
                                                    <td class="col align-middle">{{ $note->lexp }}</td>
                                                </tr>
                                                <tr>
                                                    <td class="col-2 fw-bold align-middle">Rubrica:</td>
                                                    <td class="col align-middle">{{ $note->rubrica }}</td>
                                                </tr>
                                                <tr>
                                                    <td class="col-2 fw-bold align-middle">Material:</td>
                                                    <td class="col align-middle">{{ $note->material }}</td>
                                                </tr>
                                                <tr>
                                                    <td class="col-2 fw-bold align-middle">Viabilidade:</td>
                                                    <td class="col align-middle align-middle">
                                                        @if ($note->Viabilities->first()->tacit && $note->Viabilities->first()->approved)
                                                            <span class="text-warning fw-bold">Aprovado
                                                                Tácitamente</span>
                                                        @elseif ($note->Viabilities->first()->approved && !$note->Viabilities->first()->rejected)
                                                            <span class="text-success fw-bold">Aprovado</span>
                                                        @elseif(!$note->Viabilities->first()->approved && $note->Viabilities->first()->rejected)
                                                            <span class="text-danger fw-bold">Rejeitado</span>
                                                        @elseif(
                                                            !$note->Viabilities->first()->approved &&
                                                                !$note->Viabilities->first()->rejected &&
                                                                !$note->Viabilities->first()->completed)
                                                            <span class="text-primary fw-bold">Viabilidade</span>
                                                        @else
                                                            <span class="text-secondary fw-bold">Desconhecido</span>
                                                        @endif
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td class="col-2 fw-bold align-middle">Contratação:</td>
                                                    <td class="col align-middle align-middle">
                                                        @if ($note->Viabilities->first()->hired)
                                                            <span class="text-success fw-bold">Obra
                                                                Contratada</span>
                                                        @else
                                                            <span class="text-secondary fw-bold">Obra NÃO
                                                                Contratada</span>
                                                        @endif
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td class="col-2 fw-bold align-middle">DtContratação:</td>
                                                    <td class="col align-middle align-middle">
                                                        {{ $note->Viabilities->first()->hired ? date('d/m/Y H:i:s', strToTime($note->Viabilities->first()->hired_at)) : '---' }}
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td class="col-2 fw-bold align-middle">Contratante:</td>
                                                    <td class="col align-middle align-middle">
                                                        @if ($note->Viabilities->last()->User)
                                                            <span
                                                                class="text-success fw-bold">{{ $note->Viabilities->last()->User->name }}</span>
                                                        @else
                                                            <span class="text-secondary fw-bold">----</span>
                                                        @endif
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td class="col-2 fw-bold align-middle">StS OP010:</td>
                                                    <td class="col align-middle fw-bold">
                                                        {{ $note->Viabilities->last()->Order->Operations->count() ? $note->Viabilities->last()->Order->Operations->Where('operacao', '0010')->last()->status : '---' }}
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td class="col-2 fw-bold align-middle">Dt OP010:</td>
                                                    <td class="col align-middle fw-bold">
                                                        {{ isset($note->Viabilities->last()->Order->Operations->where('operacao', '0010')->last()->fimReal) ? date('d/m/Y H:i:s', strToTime($note->Viabilities->last()->Order->Operations->Where('operacao', '0010')->last()->fimReal)) : '---' }}
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td class="col-2 fw-bold align-middle">centroTrabalho:</td>
                                                    <td class="col align-middle fw-bold">
                                                        {{ isset($note->Viabilities->last()->Order->Operations->where('operacao', '0010')->last()->cenTrab) ? $note->Viabilities->last()->Order->Operations->where('operacao', '0010')->last()->cenTrab : '---' }}
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td class="col-2 fw-bold align-middle">Responsável:</td>
                                                    <td class="col align-middle fw-bold">
                                                        {{ isset($note->Viabilities->last()->Engineer->name) ? $note->Viabilities->last()->Engineer->name . " ( {$note->Viabilities->last()->Engineer->email} )" : '---' }}
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>

                            <div class="card">
                                <h5 class="card-header py-1 my-0 edp-bg-sprucegreen-70 text-edp-verde">ALTERAR
                                    INFORMAÇÕES VIABILIDADE</h5>

                                @if (
                                    !$note->Viabilities->last()->approved &&
                                        !$note->Viabilities->last()->rejected &&
                                        !$note->Viabilities->last()->completed)
                                    <div class="table-responsive">


                                        <table class="table table-sm table-condensed table-striped-columns">
                                            <tbody>
                                                <tr>
                                                    <td class="fw-bold col-2 align-middle">PARCEIRA:</td>
                                                    <td class="align-middle fw-bold">
                                                        {{ isset($note->Viabilities->last()->Company->name) ? $note->Viabilities->last()->Company->name : '---' }}
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td class="fw-bold col-3 align-middle">RESPONSÁVEL:</td>
                                                    <td class="align-middle">
                                                        {{ isset($note->Viabilities->last()->Engineer->name) ? $note->Viabilities->last()->Engineer->name : '---' }}
                                                    </td>
                                                </tr>

                                            </tbody>
                                        </table>
                                    </div>
                                    <h5 class="card-header py-1 my-0 edp-bg-sprucegreen-70 text-edp-verde">ALTERAR
                                        PARA</h5>
                                    <div class="card-body">
                                        <div class="mb-3 col-6">
                                            <label for="exampleFormControlInput1" class="form-label">Parceira:</label>
                                            <select class="form-select form-select-sm border-secondary"
                                                aria-label="Small select example" wire:model.defer="company_s">
                                                @if ($companies->count())
                                                    @foreach ($companies as $company)
                                                        <option value="{{ $company->id }}">{{ $company->name }}
                                                        </option>
                                                    @endforeach
                                                @endif

                                            </select>
                                        </div>
                                        <div class="mb-3 col-6">
                                            <label for="exampleFormControlInput1"
                                                class="form-label">Responsável:</label>
                                            <select class="form-select form-select-sm border-secondary"
                                                aria-label="Small select example" wire:model.defer="user_s">
                                                @if ($users->count())
                                                    @foreach ($users as $user)
                                                        <option value="{{ $user->id }}">{{ $user->name }}
                                                        </option>
                                                    @endforeach
                                                @endif

                                            </select>
                                        </div>

                                        <div class="mb-3">
                                            <button class="btn btn-sm btn-primary"
                                                wire:click.prevent="toAlterViability()">SALVAR</button>
                                        </div>
                                    </div>
                                @else
                                    <div class="card-body">
                                        <h4 class="text-center fw-bold">ALTERAÇÃO DA VIABILIDADE INDISPONÍVEL</h4>
                                        <p class="text-center">A EMPRESA DE DESTINO JÁ EFETUOU O RETORNO DA VIABILDIADE.
                                        </p>
                                    </div>
                                @endif
                            </div>
                        </div>


                    </div>

                @endif

                <div class="modal-footer">
                    <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Fechar</button>

                </div>

            </div>
        </div>
    </div>
</div>
