@php
    use Illuminate\Support\Facades\Storage;
@endphp
<div>

    <!-- Formulário de Busca e Seleção de Quantidade por Página -->
    <div class="row mb-3">

        <div class="col-md-3">
            <select class="form-select" wire:model="perPage">
                <option value="5">5 por página</option>
                <option value="10">10 por página</option>
                <option value="15">15 por página</option>
                <option value="20">20 por página</option>
                <option value="50">50 por página</option>
                <option value="100">100 por página</option>
                <option value="150">150 por página</option>
            </select>
        </div>

        <div class="col-md-3">
            <select class="form-select" wire:model="service">
                <option value="">Todos</option>
                @if ($services->count())
                    @foreach ($services as $serv)
                        <option value="{{ $serv->uuid }}">{{ $serv->service }}</option>
                    @endforeach
                @endif
            </select>
        </div>

        <div class="col-md-4">
            <input type="text" class="form-control" placeholder="Buscar por nome do arquivo ou Nota"
                wire:model.debounce.300ms="search">
        </div>
    </div>

    <!-- Renderização dos links de paginação -->
    <div class="d-flex justify-content-center">
        {{ $lists->links() }}
    </div>
    <!-- Mostrar a quantidade de registros exibidos e total -->
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between">
                <div>
                    Exibindo {{ $lists->firstItem() }} a {{ $lists->lastItem() }} de {{ $lists->total() }} registros
                </div>
                <div>
                    Página {{ $lists->currentPage() }} de {{ $lists->lastPage() }}
                </div>
            </div>
        </div>
    </div>


    <div class="row">
        <div class="col-12">
            <table class="table table-sm table-condensed table-hover">
                <thead>
                    <tr class="table-dark">
                        <th class="text-center"></th>
                        <th class="text-center">Nota</th>
                        <th class="text-center">Nome</th>
                        <th class="text-center">Ext</th>
                        <th class="text-center">Tam</th>
                        <th class="text-center">Serviço</th>
                        <th class="text-center">Usuário</th>
                        <th class="text-center">Data Criação</th>
                        <th class="text-center"></th>
                    </tr>
                </thead>
                <tbody>
                    @if ($lists->count())
                        @php

                            // Funções
                            function formatFileSize($size)
                            {
                                $units = ['B', 'KB', 'MB', 'GB', 'TB'];
                                $unitIndex = 0;

                                while ($size >= 1024 && $unitIndex < count($units) - 1) {
                                    $size /= 1024;
                                    $unitIndex++;
                                }

                                return number_format($size, 2) . ' ' . $units[$unitIndex];
                            }

                        @endphp
                        @foreach ($lists as $list)
                            @php
                                $f_exists = Storage::exists($list->path);
                            @endphp
                            <tr wire:key="fileRow-{{ $list->id }}"
                                class="
                                text-center align-middle
                                @if (!$f_exists) table-warning @endif

                            ">
                                <td class="text-center align-middle"></td>
                                <td class="text-center align-middle">{{ $list->Note->note }}</td>
                                <td class="text-center align-middle">{{ $list->file_name }}</td>
                                <td class="text-center align-middle">{{ $list->ext }}</td>
                                <td class="text-center align-middle">
                                    @if ($f_exists)
                                        {{ formatFileSize(Storage::size($list->path)) }}
                                    @else
                                        ---
                                    @endif
                                </td>
                                <td class="text-center align-middle">
                                    {{ isset($list->Service->service) ? $list->Service->service : '---' }}
                                </td>
                                <td class="text-center align-middle">{{ $list->User->name }}</td>
                                <td class="text-center align-middle">
                                    {{ date('d/m/Y H:i:s', strtotime($list->created_at)) }}
                                </td>
                                <td class="text-center align-middle">
                                    <i class="ri-pencil-fill text-primary fs-5" style="cursor: pointer;"
                                        wire:click.prevent="$emitTo('files.manager.fileedit', 'editFile', {{ $list }})"></i>
                                </td>
                            </tr>
                        @endforeach
                    @else
                        <tr>
                            <td colspan="9" class="text-center">Nenhum registro encontrado.</td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>

    <!-- Mostrar a quantidade de registros exibidos e total -->
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between">
                <div>
                    Exibindo {{ $lists->firstItem() }} a {{ $lists->lastItem() }} de {{ $lists->total() }} registros
                </div>
                <div>
                    Página {{ $lists->currentPage() }} de {{ $lists->lastPage() }}
                </div>
            </div>
        </div>
    </div>

    <!-- Renderização dos links de paginação -->
    <div class="d-flex justify-content-center">
        {{ $lists->links() }}
    </div>

    @livewire('files.manager.fileedit', key('file-edit'))
</div>
