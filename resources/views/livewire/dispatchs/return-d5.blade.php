<div>

    <x-show-loading />

    <div class="card">
        <div class="card-header  edp-bg-sprucegreen-100 edp-text-verde-dark">
            <h4 class="fs-4 text-uppercase">RETORNO {{ $service->service }} (D5)</h4>
        </div>

        <div class="card-body edp-bg-gray">
            @if ($lists->count())
                @foreach ($lists as $list)
                    <div class="card my-2" x-data="{ isVisible: false }" @click.away="isVisible = false"
                        wire:key='list-{{ $list->id }}'>

                        <div class="card-body my-0 py-0">
                            <div class="table-responsive">
                                <table class="table table-condensed table-sm">
                                    <thead>
                                        <tr>
                                            <th scope="col">Note/Ov</th>
                                            <th scope="col">Rubrica</th>
                                            <th scope="col">Municipio</th>
                                            <th scope="col">Data</th>
                                            <th scope="col"></th>
                                        </tr>
                                    </thead>
                                    <tbody class="table-group-divider">
                                        <tr>
                                            <td>{{ $list->Note->note }}</td>
                                            <td>{{ $list->Note->rubrica }}</td>
                                            <td>{{ $list->Note->lexp }}</td>
                                            <td>{{ date('d/m/Y H:i:s', strToTime($list->created_at)) }}</td>
                                            <td class="text-truncate"><i @click="isVisible = !isVisible"
                                                    class="bx bxs-plus-square text-danger fs-4"
                                                    style="cursor: pointer;"></i></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>


                            <div x-show="isVisible" style="display: none;">
                                <div class="clear-fix">
                                    <div class="row">
                                        <div class="col-8">
                                            @if ($list->Comments->count())
                                                <div class="card">
                                                    <h5 class="card-header edp-bg-seoweedgreen-100 text-white">
                                                        Comentários</h5>
                                                    <div class="card-body">
                                                        <div class="clearfix">

                                                            @foreach ($list->Comments as $comment)
                                                                <div class="d-flex justify-content-start">
                                                                    <div
                                                                        class="border border-2 border-secondary rounded mb-3">

                                                                        <div class="text-bg-secondary p-2 text-justify">
                                                                            {{ $comment->message }}</div>
                                                                        <p class="text-start mt-2 mb-1 px-2"><span
                                                                                class="fw-bold">Por:</span>
                                                                            {{ $comment->User->name }}
                                                                            <span class="fw-bold">as</span>
                                                                            {{ date('d/m/Y H:i:s') }}

                                                                        </p>
                                                                    </div>
                                                                </div>
                                                            @endforeach

                                                        </div>
                                                    </div>
                                                </div>
                                            @endif
                                        </div>
                                        <div class="col-4">
                                            <p class="fw-bold fs-6 my-0 py-0">Arquivos:</p>
                                            @if ($list->Note->Files->count())
                                                @foreach ($list->Note->Files as $file)
                                                    <p class="mb-2 mb-0 py-0" style="cursor: pointer;"
                                                        wire:click.prevent="downloadFile({{ $file->id }})">
                                                        <i class="bx bxs-file-{{ $file->ext }} text-danger"></i>
                                                        <span>{{ $file->file_name }}</span>
                                                    </p>
                                                @endforeach
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div>

                    </div>
                @endforeach
            @endif
        </div>

    </div>

</div>
