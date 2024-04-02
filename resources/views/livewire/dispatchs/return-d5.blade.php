@php
    use App\Custom\Notestatus;
@endphp
<div>

    <x-show-loading />

    <div class="card">
        <div class="card-header  edp-bg-sprucegreen-100 edp-text-verde-dark">
            <h4 class="fs-4 text-uppercase">RETORNO {{ $service->service }} (D5)</h4>
        </div>

        <div class="card-body edp-bg-gray">
            @if ($lists->count())

                @foreach ($lists as $list)
                    <div class="card my-2" x-data="{ isVisible: false, confirm: false }" @click.away="isVisible = false"
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
                                            <th scope="col">Status</th>
                                            <th scope="col"></th>
                                        </tr>
                                    </thead>
                                    <tbody class="table-group-divider">
                                        <tr>
                                            <td>{{ $list->Note->note }}</td>
                                            <td>{{ $list->Note->rubrica }}</td>
                                            <td>{{ $list->Note->lexp }}</td>
                                            <td>{{ date('d/m/Y H:i:s', strToTime($list->created_at)) }}</td>
                                            <td>
                                                @if ($list->Production->count())
                                                    <span
                                                        class="badge {{ Notestatus::status($list->Production->status)->colorbg }}">{{ Notestatus::status($list->Production->status)->status }}</span>
                                                @else
                                                    <span class="badge text-bg-secodary">Não Criado</span>
                                                @endif
                                            </td>

                                            <td class="text-truncate"><i @click="isVisible = !isVisible"
                                                    class="bx bxs-plus-square text-danger fs-4"
                                                    style="cursor: pointer;"></i></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>


                            <div x-show="isVisible" class="table-group-divider py-3" style="display: none;">
                                <div class="clear-fix">
                                    <div class="row">
                                        <div class="col-6">
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
                                        <div class="col-6">

                                            @if ($list->Note->Files->count())
                                                <table class="table">
                                                    <thead>
                                                        <th>Arquivo</th>
                                                        <th>Autor</th>
                                                    </thead>
                                                    <tbody class="table-group-divider">
                                                        @foreach ($list->Note->Files as $file)
                                                            <tr>
                                                                <td>
                                                                    <p class="mb-2 mb-0 py-0" style="cursor: pointer;"
                                                                        wire:click.prevent="downloadFile({{ $file->id }})">
                                                                        <i
                                                                            class="bx bxs-file-{{ $file->ext }} text-danger"></i>
                                                                        <span>{{ $file->file_name }}</span>
                                                                    </p>
                                                                </td>
                                                                <td>
                                                                    <span>{{ $file->User->name }}</span>
                                                                </td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>

                                                </table>
                                            @endif


                                            <div class="card">
                                                <h4 class="card-header fs-5 edp-bg-seoweedgreen-100 text-white">DADOS
                                                    PARA RETORNO</h4>
                                                <div class="card-body">
                                                    <div class="clear-fix">
                                                        <div class="row">
                                                            <div class="col-8">
                                                                <div class="mb-3">
                                                                    <label for="exampleFormControlInput1"
                                                                        class="form-label">Selecione Empresa:</label>
                                                                    <select
                                                                        class="form-select border border-1 border-secondary"
                                                                        aria-label="Default select example"
                                                                        wire:model="company_s">
                                                                        @if ($companies->count())
                                                                            @foreach ($companies as $company)
                                                                                <option value="{{ $company->id }}">
                                                                                    {{ $company->name }}</option>
                                                                            @endforeach
                                                                        @else
                                                                            <option selected>Sem Empresas Encontradas
                                                                            </option>
                                                                        @endif
                                                                    </select>
                                                                </div>
                                                            </div>
                                                            <div class="col-6">
                                                                <div class="mb-3">
                                                                    <label for="exampleFormControlInput1"
                                                                        class="form-label">Buscar Usuario:</label>
                                                                    <input type="text"
                                                                        class="form-control border border-1 border-secondary"
                                                                        wire:model="search">
                                                                </div>
                                                            </div>
                                                            <div class="col-6">
                                                                <div class="mb-3">
                                                                    <label for="exampleFormControlInput1"
                                                                        class="form-label">Selecione Usuário:</label>
                                                                    <select
                                                                        class="form-select border border-1 border-secondary"
                                                                        aria-label="Default select example"
                                                                        wire:model.defer="user_s">
                                                                        @if ($users->count())
                                                                            @foreach ($users as $user)
                                                                                <option value="{{ $user->id }}">
                                                                                    {{ $user->name }}</option>
                                                                            @endforeach
                                                                        @else
                                                                            <option selected>Selecione uma empresa
                                                                            </option>
                                                                        @endif
                                                                    </select>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div @click.away="confirm=false">
                                                    <div class="text-center mb-3" x-show="!confirm">
                                                        <button class="btn btn-sm btn-primary align-self-end"
                                                            @click="confirm=true">ENVIAR</button>
                                                    </div>

                                                    <div class="card border border-secondary border-2" x-show="confirm"
                                                        style="display: none;">
                                                        <div class="card-body">
                                                            <h4 class="text-center fw-bold mb-3">Deseja realmente
                                                                retornar {{ $list->Note->note }}?</h4>
                                                            <p class="text-justify p-2 border border-1 rounded shadow">
                                                                Antes de enviar, verifique se a empresa e o usuário
                                                                esteja devidamente selecionado.
                                                            </p>
                                                            <div class="clear-fix">
                                                                <div
                                                                    class="center-text mt-2 d-flex justify-content-center">
                                                                    <button class="btn btn-primary btn-sm"
                                                                        wire:click.prevent="returnD5({{ $list->Note->id }}, {{ $list->id }})">SIM</button>
                                                                    <button class="btn btn-danger btn-sm ms-2"
                                                                        @click="confirm=false">NÃO</button>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                            </div>
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
