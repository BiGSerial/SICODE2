@php
    use App\Helpers\FileIcon;
@endphp
<div>
    <x-show-loading />
    <div wire:ignore.self class="modal fade" id="responserPartner" tabindex="-1" aria-labelledby="exampleModalLabel"
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
                                                        <td class="col align-middle align-middle">{{ $note->status }}</td>
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
                                                            @if ($note->Viabilities->first()->approved && !$note->Viabilities->first()->rejected)
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
                                                            @if ($note->Viabilities->first()->User)
                                                                <span
                                                                    class="text-success fw-bold">{{ $note->Viabilities->first()->User->name }}</span>
                                                            @else
                                                                <span class="text-secondary fw-bold">----</span>
                                                            @endif
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td class="col-2 fw-bold align-middle">StS OP010:</td>
                                                        <td class="col align-middle fw-bold">
                                                            {{ $note->Viabilities->first()->Order->Operations->count() ? $note->Viabilities->first()->Order->Operations->Where('operacao', '0010')->first()->status : '---' }}
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td class="col-2 fw-bold align-middle">Dt OP010:</td>
                                                        <td class="col align-middle fw-bold">
                                                            {{ $note->Viabilities->first()->Order->Operations->where('operacao', '0010')->first()->fimReal ? date('d/m/Y H:i:s', strToTime($note->Viabilities->first()->Order->Operations->Where('operacao', '0010')->first()->fimReal)) : '---' }}
                                                        </td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                        {{-- <dl class="row">
                                            <dt class="col-sm-3">Nota/Ov:</dt>
                                            <dd class="col-sm-9">{{ $note->note }}</dd>

                                            <dt class="col-sm-3">Ordems:</dt>
                                            @if ($note->Viabilities->count())
                                                @foreach ($note->Viabilities as $viab)
                                                    <dd class="col-sm-9">{{ $viab->Order->ordem }}</dd>
                                                @endforeach
                                            @endif
                                            <dt class="col-sm-3">Status:</dt>
                                            <dd class="col-sm-9">{{ $note->nstats }}</dd>
                                            <dt class="col-sm-3">Situação:</dt>
                                            <dd class="col-sm-9">{{ $note->status }}</dd>
                                            <dt class="col-sm-3">Municipio:</dt>
                                            <dd class="col-sm-9">{{ $note->lexp }}</dd>
                                            <dt class="col-sm-3">Rubrica:</dt>
                                            <dd class="col-sm-9">{{ $note->rubrica }}</dd>
                                            <dt class="col-sm-3">Material:</dt>
                                            <dd class="col-sm-9">{{ $note->material }}</dd>
                                            <dt class="col-sm-3"></dt>
                                            <dd class="col-sm-9"></dd>
                                            <dt class="col-sm-3">Viabildade:</dt>
                                            <dd class="col-sm-9 align-middle">
                                                @if ($note->Viabilities->first()->approved && !$note->Viabilities->first()->rejected)
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
                                            </dd>
                                            <dt class="col-sm-3">Contratação:</dt>
                                            <dd class="col-sm-9 align-middle">
                                                @if ($note->Viabilities->first()->hired)
                                                    <span class="text-success fw-bold">Obra Contratada</span>
                                                @else
                                                    <span class="text-secondary fw-bold">Obra NÃO Contratada</span>
                                                @endif
                                            </dd>
                                            <dt class="col-sm-3">DtContratação:</dt>
                                            <dd class="col-sm-9 align-middle">
                                                {{ $note->Viabilities->first()->hired ? date('d/m/Y H:i:s', strToTime($note->Viabilities->first()->hired_at)) : '---' }}
                                            </dd>
                                            <dt class="col-sm-3">Contratante:</dt>
                                            <dd class="col-sm-9 align-middle">
                                                @if ($note->Viabilities->first()->User)
                                                    <span
                                                        class="text-success fw-bold">{{ $note->Viabilities->first()->User->name }}</span>
                                                @else
                                                    <span class="text-secondary fw-bold">----</span>
                                                @endif
                                            </dd>
                                            <dt class="col-sm-3">StS OP010:</dt>
                                            <dd class="col-sm-9 align-middle fw-bold">
                                                {{ $note->Viabilities->first()->Order->Operations->count() ? $note->Viabilities->first()->Order->Operations->Where('operacao', '0010')->first()->status : '---' }}
                                            </dd>
                                            <dt class="col-sm-3">Dt OP010:</dt>
                                            <dd class="col-sm-9 align-middle fw-bold">
                                                {{ $note->Viabilities->first()->Order->Operations->where('operacao', '0010')->first()->fimReal ? date('d/m/Y H:i:s', strToTime($note->Viabilities->first()->Order->Operations->Where('operacao', '0010')->first()->fimReal)) : '---' }}
                                            </dd>
                                        </dl> --}}
                                    </div>
                                </div>

                                @if ($note->viabilities->count() && ($form = $note->viabilities->first()->Form))
                                    <div class="card">
                                        <div class="card-header py-1 edp-bg-sprucegreen-70 text-edp-verde">
                                            <h4 class="fs-5 my-0 py-0">Resultado Viabilidade</h4>
                                        </div>
                                        <div class="card-body">
                                            <dt class="col-sm-3">Motivo:</dt>
                                            <dd class="col-sm-9 ps-2"
                                                style="border-left: 3px solid; border-bottom: 1px solid;">
                                                {{ $form->reason }}</dd>
                                            <dt class="col-sm-3">Mudanças Prevista:</dt>
                                            <dd class="col-sm-9 ps-2"
                                                style="border-left: 3px solid; border-bottom: 1px solid;">
                                                {{ $form->changes * 10 }}%</dd>
                                            <dt class="col-sm-3">Descrição:</dt>
                                            <dd class="col-sm-9 ps-2"
                                                style="border-left: 3px solid; border-bottom: 1px solid;">
                                                {{ $form->description }}%</dd>
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
