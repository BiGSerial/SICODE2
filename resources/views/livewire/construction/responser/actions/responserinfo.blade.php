@php
    use App\Helpers\FileIcon;
@endphp
<div>
    <x-show-loading />
    <div wire:ignore.self class="modal fade" id="responserInfo" tabindex="-1" aria-labelledby="exampleModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content  edp-bg-stategrey-50">
                @if ($note)
                    <div class="modal-header edp-bg-sprucegreen-70 text-edp-verde">
                        <h4 class="modal-title fs-5">Informação de {{ $note->note }}</h4>
                    </div>
                    <div class="container-fluid my-3">
                        <div class="row">
                            <div class="col-md-6">
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
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>

                                @if ($note->Viabilities->count() && $note->Viabilities->last()->Form)
                                    @php
                                        $form = $note->Viabilities->last()->Form;
                                    @endphp
                                    <div class="card">
                                        <h5 class="card-header py-1 my-0 edp-bg-sprucegreen-70 text-edp-verde">RETORNO
                                            VIABILIDADE</h5>
                                        <div class="table-responsive">
                                            <table class="table table-sm table-condensed table-striped-columns">
                                                <tbody>
                                                    <tr>
                                                        <td class="fw-bold col-2 align-middle">MOTIVO:</td>
                                                        <td class="align-middle fw-bold">{{ $form->reason }}</td>
                                                    </tr>
                                                    <tr>
                                                        <td class="fw-bold col-2 align-middle">IMPACTO:</td>
                                                        <td class="align-middle">
                                                            {{ $form->changes * 10 }}%
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td class="fw-bold col-2 align-middle">RESPONSÁVEL:</td>
                                                        <td class="align-middle text-uppercase">
                                                            {{ $form->responsible }}</td>
                                                    </tr>
                                                    <tr>
                                                        <td class="fw-bold col-2 align-middle">DESCRIÇÃO:</td>
                                                        <td class="align-middle">{{ $form->description }}</td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                @endif

                            </div>
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header py-1 edp-bg-sprucegreen-70 text-edp-verde">
                                        <h4 class="fs-5 my-0 py-0">Arquivos</h4>
                                    </div>
                                    <div class="card-body py-1 my-0">
                                        @if ($note->Files->count())
                                            <table class="table table-sm table-condensed table-striped table-hover">
                                                <thead class="">
                                                    <th class="text-center">
                                                        {{-- <input class="form-check-input border border-1 border-secondary"
                                                            type="checkbox"></td> --}}
                                                    </th>
                                                    <th class="text-center col-1">Serviço</th>
                                                    <th class="text-center">Tipo</th>
                                                    <th class="text-center">Arquivo</th>
                                                </thead>
                                                <tbody>
                                                    @foreach ($note->Files->sortBy('file_name') as $file)
                                                        {{-- @dump($file->ext) --}}
                                                        <tr>
                                                            <td class="text-center align-middle"><input
                                                                    class="form-check-input border border-1 border-secondary"
                                                                    type="checkbox" value="{{ $file->id }}"
                                                                    wire:model.defer="selectedFiles"></td>
                                                            <td class="text-center align-middle">
                                                                {{ isset($file->Service->service) ? $file->Service->service : '' }}
                                                            </td>
                                                            <td class="text-center align-middle"><i
                                                                    class="{{ FileIcon::getIcon($file->ext)->icon }} fs-4 align-middle"></i>
                                                            </td>
                                                            <td class="text-center align-middle"><span
                                                                    wire:click.prenvet="downloadFile({{ $file->id }})"
                                                                    style="cursor: pointer;">{{ $file->file_name }}</span>
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>

                                            </table>
                                            <button class="btn btn-sm btn-primary" wire:click.prevent="zipFiles"><i
                                                    class="bx bxs-cloud-download"></i> Baixar
                                                Selecionados</button>
                                        @else
                                            <div class="card">
                                                <div class="card-body">
                                                    <h4 class="text-center">SEM ARQUIVOS</h4>
                                                </div>
                                            </div>
                                        @endif


                                    </div>
                                </div>

                                @if ($note->Viabilities->count() && $note->Viabilities->last()->Comments->count())

                                    <div class="card">
                                        <h5 class="card-header py-1 my-0 edp-bg-sprucegreen-70 text-edp-verde">
                                            COMENTÁRIOS</h5>
                                        <div class="table-responsive">
                                            <table class="table table-sm table-condensed table-striped-columns">
                                                <tbody>

                                                    @foreach ($note->Viabilities->last()->Comments as $comment)
                                                        <tr>
                                                            <td class="col-2">
                                                                {{ date('d/m/Y H:i', strToTime($comment->created_at)) }}
                                                            </td>
                                                            <td class="fw-bold col-2">{{ $comment->User->name }}
                                                            </td>
                                                            <td class="col">{{ $comment->message }}
                                                            </td>
                                                        </tr>
                                                    @endforeach


                                                </tbody>
                                            </table>
                                        </div>
                                    </div>

                                @endif

                            </div>
                        </div>

                        <div class="row">
                            <div class="col-6">
                                @if (!$note->Viabilities->last()->completed && $note->Viabilities->last()->status == 1)
                                    <div class="card">
                                        <h5 class="card-header py-1 my-0 edp-bg-sprucegreen-70 text-edp-verde">
                                            CONTROLE</h5>

                                        <div class="card-body">
                                            <div class="row">
                                                <div class="col mb-3">
                                                    <label for="" class="form-label">
                                                        <p class="my-0 py-1">
                                                            <strong>ADICIONAR
                                                                PRAZO:</strong>
                                                        </p>
                                                        {{ $note->Viabilities->last()->Days->sum('days') }}/15 dias
                                                        Disponível
                                                    </label>
                                                    <input class="form-control border border-secondary" type="number"
                                                        id="inputDays" max="15" min="-15"
                                                        wire:model.defer="setDays" style="max-width: 100px;">
                                                </div>
                                                <div class="col-2 align-middle p-1">
                                                    <button class="btn btn-primary"
                                                        wire:click="addDays">SALVAR</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            </div>

                            <div class="col-6">

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

    <script>
        window.getElementById('inputDays').addEventListener('keyUp', function(event) {
            alert('teste');
            const input = envet.target;
            const value - parseFloat(input.value);

            if (value < -15) {
                input.value = -15;
            } else if (value > 15) {
                input.value = 15;
            }
        });
    </script>
</div>
