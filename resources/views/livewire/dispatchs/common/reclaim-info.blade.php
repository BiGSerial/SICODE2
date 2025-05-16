@php
    use App\Helpers\FileIcon;
@endphp
<div>
    <x-show-loading />
    <div wire:ignore.self class="modal fade" id="responserInfo" tabindex="-1" aria-labelledby="exampleModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content  edp-bg-stategrey-50">
                @if ($reclaim)
                    <div class="modal-header edp-bg-sprucegreen-70 text-edp-verde">
                        <h4 class="modal-title fs-5">Informação de {{ $reclaim->Note->note }}</h4>
                    </div>
                    <div class="container-fluid my-3">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header py-1 edp-bg-sprucegreen-70 text-edp-verde">
                                        <h4 class="fs-5 my-0 py-0">Dados da Nota</h4>
                                    </div>

                                    <div class="table-responsive">
                                        <table class="table table-sm table-striped-columns">
                                            <tbody>
                                                <tr>
                                                    <td class="col-2 fw-bold align-middle text-end">Nota/OV:</td>
                                                    <td class="col  align-middle">{{ $reclaim->Note->note }}</td>
                                                </tr>
                                                <tr>
                                                    <td class="col-2 fw-bold align-middle text-end">Ordens:</td>
                                                    <td class="col align-middle">
                                                        @if ($reclaim->Note->Viabilities->isNotEmpty())
                                                            @foreach ($reclaim->Note->Viabilities as $viab)
                                                                @if ($viab->Orders->isNotEmpty())
                                                                    @foreach ($viab->Orders as $order)
                                                                        <p class="my-1 py-0">{{ $order->ordem }}</p>
                                                                    @endforeach
                                                                @endif
                                                            @endforeach
                                                        @elseif ($reclaim->Note->Orders->isNotEmpty())
                                                            @foreach ($reclaim->Note->Orders as $order)
                                                                <p class="my-1 py-0">{{ $order->ordem }}</p>
                                                            @endforeach
                                                        @endif
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td class="col-2 fw-bold  align-middle text-end">Status:</td>
                                                    <td class="col  align-middle">{{ $reclaim->Note->nstats }}
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td class="col-2 fw-bold align-middle text-end">Situação:</td>
                                                    <td class="col align-middle align-middle">
                                                        {{ $reclaim->Note->status }}
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td class="col-2 fw-bold align-middle text-end">Municipio:</td>
                                                    <td class="col align-middle">{{ $reclaim->Note->lexp }}</td>
                                                </tr>
                                                <tr>
                                                    <td class="col-2 fw-bold align-middle text-end">Rubrica:</td>
                                                    <td class="col align-middle">{{ $reclaim->Note->rubrica }}
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td class="col-2 fw-bold align-middle text-end">Material:</td>
                                                    <td class="col align-middle">{{ $reclaim->Note->material }}
                                                    </td>
                                                </tr>
                                                {{-- @if ($reclaim->Note->Viabilities->count())
                                                    <tr>
                                                        <td class="col-2 fw-bold align-middle text-end">Viabilidade:
                                                        </td>
                                                        <td class="col align-middle align-middle">
                                                            @if ($reclaim->Note->Viabilities->last()->tacit && $reclaim->Note->Viabilities->last()->approved)
                                                                <span class="text-warning fw-bold">Aprovado
                                                                    Tácitamente</span>
                                                            @elseif ($reclaim->Note->Viabilities->last()->approved && !$reclaim->Note->Viabilities->last()->rejected)
                                                                <span class="text-success fw-bold">Aprovado</span>
                                                            @elseif(!$reclaim->Note->Viabilities->last()->approved && $reclaim->Note->Viabilities->last()->rejected)
                                                                <span class="text-danger fw-bold">Rejeitado</span>
                                                            @elseif(
                                                                !$reclaim->Note->Viabilities->last()->approved &&
                                                                    !$reclaim->Note->Viabilities->last()->rejected &&
                                                                    !$reclaim->Note->Viabilities->last()->completed)
                                                                <span class="text-primary fw-bold">Viabilidade</span>
                                                            @else
                                                                <span class="text-secondary fw-bold">Desconhecido</span>
                                                            @endif
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td class="col-2 fw-bold align-middle text-end">Contratação:
                                                        </td>
                                                        <td class="col align-middle align-middle">
                                                            @if ($reclaim->Note->Viabilities->last()->hired)
                                                                <span class="text-success fw-bold">Obra
                                                                    Contratada</span>
                                                            @else
                                                                <span class="text-secondary fw-bold">Obra NÃO
                                                                    Contratada</span>
                                                            @endif
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td class="col-2 fw-bold align-middle text-end">DtContratação:
                                                        </td>
                                                        <td class="col align-middle align-middle">
                                                            {{ $reclaim->Note->Viabilities->last()->hired ? date('d/m/Y H:i:s', strToTime($reclaim->Note->Viabilities->last()->hired_at)) : '---' }}
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td class="col-2 fw-bold align-middle text-end">Contratante:
                                                        </td>
                                                        <td class="col align-middle align-middle">
                                                            @if ($reclaim->Note->Viabilities->last()->User)
                                                                <span
                                                                    class="text-success fw-bold">{{ $reclaim->Note->Viabilities->last()->User->name }}</span>
                                                            @else
                                                                <span class="text-secondary fw-bold">----</span>
                                                            @endif
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td class="col-2 fw-bold align-middle text-end">StS OP010:</td>
                                                        <td class="col align-middle fw-bold">
                                                            {{ $reclaim->Note->Viabilities->last()->Order->Operations->count() ? $reclaim->Note->Viabilities->last()->Order->Operations->Where('operacao', '0010')->last()->status : '---' }}
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td class="col-2 fw-bold align-middle text-end">Dt OP010:</td>
                                                        <td class="col align-middle fw-bold">
                                                            {{ isset($reclaim->Note->Viabilities->last()->Order->Operations->where('operacao', '0010')->last()->fimReal) ? date('d/m/Y H:i:s', strToTime($reclaim->Note->Viabilities->last()->Order->Operations->Where('operacao', '0010')->last()->fimReal)) : '---' }}
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td class="col-2 fw-bold align-middle text-end">centroTrabalho:
                                                        </td>
                                                        <td class="col align-middle fw-bold">
                                                            {{ isset($reclaim->Note->Viabilities->last()->Order->Operations->where('operacao', '0010')->last()->cenTrab) ? $reclaim->Note->Viabilities->last()->Order->Operations->where('operacao', '0010')->last()->cenTrab : '---' }}
                                                        </td>
                                                    </tr>
                                                @endif --}}
                                            </tbody>
                                        </table>
                                    </div>

                                </div>


                                <div class="card">
                                    <div class="card-header py-1 edp-bg-sprucegreen-70 text-edp-verde">
                                        <h4 class="fs-5 my-0 py-0">Arquivos</h4>
                                    </div>
                                    <div class="card-body py-1 my-0">
                                        @if ($reclaim->Note->Files->count())
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
                                                    @foreach ($reclaim->Note->Files->sortBy('file_name') as $file)
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
                            <div class="col-md-6">

                                @if ($reclaim->Viabilities->count() && $reclaim->Viabilities->last()->Form)
                                    @php
                                        $form = $reclaim->Viabilities->last()->Form;
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

                                {{-- @if ($reclaim->Note->Viabilities->count() && $reclaim->Note->Viabilities->last()->Comments->count())

                                    <div class="card">
                                        <h5 class="card-header py-1 my-0 edp-bg-sprucegreen-70 text-edp-verde">
                                            COMENTÁRIOS</h5>
                                        <div class="table-responsive">
                                            <table class="table table-sm table-condensed table-striped-columns">
                                                <tbody>

                                                    @foreach ($reclaim->Note->Viabilities->last()->Comments as $comment)
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

                                @endif --}}



                                @if ($reclaim->Comments->count())
                                    <div class="card">
                                        <div class="card-header py-1 edp-bg-sprucegreen-70 text-edp-verde">
                                            <h4 class="fs-5 my-0 py-0">Retorno Interno</h4>
                                        </div>

                                        <div class="table-responsive">
                                            <table class="table table-sm table-striped-columns my-0 py-0">
                                                <tbody>
                                                    @php
                                                        if ($reclaim->Viabilities->isNotEmpty()) {
                                                            $origem = 'VIABILIDADE';
                                                        } elseif ($reclaim->Waiting) {
                                                            $origem = 'CONTRATAÇÃO';
                                                        } elseif ($reclaim->Approvals->isNotEmpty()) {
                                                            $origem = 'VALIDAÇÃO DE PROJETOS';
                                                        } elseif ($reclaim->Externals->isNotEmpty()) {
                                                            $origem = 'ENTIDADE EXTERNA';
                                                        } else {
                                                            $origem = 'DESCONHECIDO';
                                                        }
                                                    @endphp
                                                    <tr>
                                                        <td class="col-2 fw-bold align-middle text-end">Origem:</td>
                                                        <td class="col  align-middle fw-bold">
                                                            {{ $origem }}
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td class="col-2 fw-bold align-middle text-end">Categoria:</td>
                                                        <td class="col  align-middle">
                                                            {{ $reclaim->Subcategory ? $reclaim->Subcategory->Category->name : '' }}
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td class="col-2 fw-bold align-middle text-end">Motivo:</td>
                                                        <td class="col  align-middle">
                                                            {{ $reclaim->Subcategory ? $reclaim->Subcategory->name : $reclaim->category }}
                                                        </td>
                                                    </tr>
                                                </tbody>
                                            </table>

                                            <h4 class="card-header fs-5 my-2 py-1 edp-bg-sprucegreen-70 text-edp-verde">
                                                Comentários</h4>
                                            <div class="card-body">
                                                <div style="max-height: 300px; overflow-y: auto;">
                                                    @foreach ($reclaim->Comments as $index => $comment)
                                                        <div class="card">
                                                            <h6
                                                                class="card-header my-0 py-1 @if ($comment->User->id == auth()->user()->id) text-bg-primary
                                                                @else
                                                                edp-bg-sprucegreen-50 text-white @endif">
                                                                # {{ $index + 1 }} -
                                                                {{ $comment->User->id == auth()->user()->id ? 'Você' : $comment->User->name }}
                                                                <span
                                                                    class="fs-6">{{ !($comment->User->id == auth()->user()->id) ? "({$comment->User->email})" : '' }}</span>
                                                            </h6>
                                                            <table class="table table-sm table-striped-columns">
                                                                <tbody>
                                                                    <tr>

                                                                    </tr>
                                                                    <tr>
                                                                        <td class="col-2 fw-bold align-middle text-end">
                                                                            Commentario:
                                                                        </td>
                                                                        <td class="col align-middle align-middle">
                                                                            {{ $comment->message }}
                                                                        </td>
                                                                    </tr>

                                                                </tbody>
                                                            </table>
                                                            <div class="card-footer py-1">
                                                                <i class="bx bx-time-five"></i>
                                                                {{ $comment->created_at->format('d/m/Y - H:i:s') }}
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                            <div class="mt-3 p-2 card-footer">
                                                <div class="form-group">
                                                    <textarea class="form-control" wire:model.defer="newComment" rows="2" placeholder="Digite seu comentário..."></textarea>
                                                </div>
                                                <div class="mt-2">
                                                    <button class="btn btn-primary btn-sm" wire:click="addComment">
                                                        <i class="bx bx-send align-middle"></i> Enviar
                                                    </button>
                                                </div>
                                            </div>


                                        </div>

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
