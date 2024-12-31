@php

    use Carbon\Carbon;
@endphp

<div>

    <div wire:ignore.self class="modal fade" id="workRejectedViewCategory" tabindex="-1"
        aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog  modal-dialog-centered">
            <div class="modal-content">
                <div class="card mb-3">
                    <h5 class="card-header py-0 my-0 edp-bg-sprucegreen-70 text-edp-verde">Motivo
                        Retorno
                    </h5>

                    @if ($workReport && $workReport->Returnwork->count())
                        <table class="table table-condensed table-sm table-striped-columns">
                            <tbody>
                                <tr>
                                    <td class="align-middle text-end" style="width: 150px;">Motivo</td>
                                    <td class="align-middle text-primary">
                                        {{ $workReport->Returnwork[$pag]->category }}</td>
                                </tr>
                                <tr>
                                    <td class="align-middle text-end" style="width: 150px;">Descrição
                                    </td>
                                    <td class="align-middle">
                                        <p class="my-0 py-0">
                                            {{ $workReport->Returnwork[$pag]->text_obs }}
                                        </p>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="align-middle text-end" style="width: 150px;">Responsável
                                    </td>
                                    <td class="align-middle">
                                        {{ $workReport->Returnwork[$pag]->User->name }}
                                        ({{ $workReport->Returnwork[$pag]->User->email }})</td>
                                </tr>
                                <tr>
                                    <td class="align-middle text-end" style="width: 150px;">Data
                                    </td>
                                    <td class="align-middle">
                                        {{ Carbon::parse($workReport->Returnwork[$pag]->created_at)->format('d/m/Y H:i:s') }}
                                    </td>
                                </tr>

                            </tbody>
                        </table>
                    @endif

                    @if ($workReport && $workReport->Returnwork->count() > 1)
                        <div class="card-footer">
                            <div class="col-auto my-0">
                                <nav aria-label="Page navigation">
                                    <ul class="pagination pagination-sm justify-content-end">
                                        <li class="page-item @disabled($pag == 0)"><a class="page-link"
                                                href="#" wire:click="previousPage">Anterior</i></a></li>
                                        <li class="page-item"><a class="page-link"
                                                href="#">{{ $pag + 1 }}/{{ $workReport->Returnwork->count() }}</a>
                                        </li>

                                        <li class="page-item @disabled($pag == $workReport->Returnwork->count() - 1)"><a class="page-link"
                                                href="#" wire:click="nextPage">Proximo</a>
                                        </li>
                                    </ul>
                                </nav>
                            </div>
                        </div>
                    @endif
                </div>

                <div class="modal-footer edp-bg-sprucegreen-100 edp-text-verde-dark">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                </div>
            </div>


        </div>
    </div>





</div>
