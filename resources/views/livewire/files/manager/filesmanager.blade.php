@php
    use Illuminate\Support\Facades\Storage;
@endphp
<div>
    @php
        $isSuperAdm = (bool) auth()->user()?->superadm;
    @endphp

    <!-- Formulário de Busca e Seleção de Quantidade por Página -->
    <div class="row mb-3">

        <div class="col-md-1">
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

        <div class="col-md-2">
            <select class="form-select" wire:model="companySelected">
                <option value="">Todos</option>
                @if ($companies->count())
                    @foreach ($companies as $company)
                        <option value="{{ $company->id }}">{{ $company->name }}</option>
                    @endforeach
                @endif
            </select>
        </div>

        <div class="col-md-2">
            <select class="form-select" wire:model="rubricSelected">
                <option value="">Todos</option>
                @if ($rubrics->count())
                    @foreach ($rubrics as $rubric)
                        <option value="{{ $rubric['rubrica'] }}">{{ $rubric['rubrica'] }}</option>
                    @endforeach
                @endif
            </select>
        </div>
        <div class="col-md-4">
            <input type="text" class="form-control" placeholder="Buscar por nome do arquivo ou Nota"
                wire:model.debounce.300ms="search">
        </div>
    </div>

    <div class="d-flex justify-content-end">
        <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" role="switch" id="flexSwitchCheckDefault"
                wire:model="noFile">
            <label class="form-check-label" for="flexSwitchCheckDefault">Somente Sem Link</label>
        </div>
        <button class="btn btn-primary ms-2" wire:click.prevent="export_excel" wire:loading.attr="disabled"
            wire:target="export_excel">


            <i class="ri-file-excel-2-line align-middle" wire:loading.remove wire:target="export_excel"></i>
            <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true" wire:loading
                wire:target="export_excel"></span>
            Exportar</button>
        <button class="btn btn-primary ms-2" wire:click.prevent="checkFilesExists" wire:loading.attr="disabled"
            wire:target="checkFilesExists">


            <i class="ri-link-m align-middle" wire:loading.remove wire:target="checkFilesExists"></i>
            <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true" wire:loading
                wire:target="checkFilesExists"></span>
            Check</button>
        <button class="btn btn-success ms-2" wire:click.prevent="downloadZip" wire:loading.attr="disabled"
            wire:target="downloadZip">
            <i class="ri-download-2-line align-middle" wire:loading.remove wire:target="downloadZip"></i>
            <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true" wire:loading
                wire:target="downloadZip"></span>
            Download Selecionados</button>
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
                        <th class="text-center">
                            <input type="checkbox" class="form-check-input" wire:click="selectAll">
                        </th>
                        <th class="text-center">Nota</th>
                        <th class="text-center">Nome</th>
                        <th class="text-center">Ext</th>
                        <th class="text-center">Tam</th>
                        <th class="text-center">Serviço</th>
                        <th class="text-center">Usuário</th>
                        <th class="text-center">Empresa</th>
                        <th class="text-center">Data Criação</th>
                        <th class="text-center"></th>
                    </tr>
                </thead>
                <tbody>
                    @if ($lists->count())

                        @foreach ($lists as $list)
                            @php
                                $f_exists = Storage::exists($list->path);
                                $isTacitRestricted = (bool) ($list->has_tacit_ads_restriction ?? false);
                                $isBlockedForUser = !$isSuperAdm && $isTacitRestricted;
                            @endphp
                            <tr wire:key="fileRow-{{ $list->id }}"
                                class="
                                text-center align-middle
                                @if (!$f_exists) table-warning @endif
                                @if ($isBlockedForUser) table-secondary @endif
                            "
                                @if ($isBlockedForUser) style="opacity: .6;" @endif>
                                <td class="text-center align-middle">
                                    <input type="checkbox" class="form-check-input" wire:model.defer="selectedFiles"
                                        value="{{ $list->id }}" @disabled($isBlockedForUser)
                                        title="{{ $isBlockedForUser ? 'ADS tácita: seleção bloqueada para usuário comum.' : '' }}">
                                </td>
                                <td class="text-center align-middle">{{ $list->Note->note }}</td>
                                <td class="text-center align-middle">{{ $list->file_name }}</td>
                                <td class="text-center align-middle">{{ $list->ext }}</td>
                                <td class="text-center align-middle">
                                    @if ($f_exists)
                                        {{ $this->formatFileSize(Storage::size($list->path)) }}
                                    @else
                                        ---
                                    @endif
                                </td>
                                <td class="text-center align-middle">
                                    {{ isset($list->Service->service) ? $list->Service->service : '---' }}
                                </td>
                                <td class="text-center align-middle">{{ $list->User->name }}</td>
                                <td class="text-center align-middle">{{ $list->User->Company?->name }}</td>
                                <td class="text-center align-middle">
                                    {{ date('d/m/Y H:i:s', strtotime($list->created_at)) }}
                                </td>
                                <td class="text-center align-middle">
                                    <i class="ri-pencil-fill text-primary fs-5" style="cursor: pointer;"
                                        wire:click.prevent="$emitTo('files.manager.fileedit', 'editFile', {{ $list }})"></i>
                                    @if ($f_exists)
                                        @if ($isBlockedForUser)
                                            <i class="ri-lock-2-line text-muted fs-5"
                                                title="Download bloqueado para usuário comum (ADS tácita)."></i>
                                        @else
                                            <i class="ri-download-cloud-2-line text-primary fs-5" style="cursor: pointer;"
                                                wire:click.prevent="downloadFile({{ $list }})"></i>
                                        @endif
                                    @endif
                                    <i class="ri-upload-cloud-2-fill text-primary fs-5" style="cursor: pointer;"
                                        wire:click.prevent="$emitTo('files.manager.createfiles', 'createFile', {{ $list->Note }})"></i>
                                    <i class="ri-delete-bin-2-line text-danger fs-5"
                                        wire:click.prevent="$emitTo('files.manager.fileedit', 'deleteFile', {{ $list }})"
                                        style="cursor: pointer;"></i>

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
    @livewire('files.manager.createfiles', key('create-files'))
</div>
