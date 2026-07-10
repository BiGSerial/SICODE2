@php
    use Illuminate\Support\Facades\Storage;
@endphp

@push('css')
    <style>
        .files-manager-page {
            --fm-bg: #f6f8fb;
            --fm-surface: #ffffff;
            --fm-border: #dfe7ea;
            --fm-ink: #132331;
            --fm-muted: #697986;
            --fm-green: #0f766e;
            background:
                radial-gradient(circle at 8% 0%, rgba(38, 255, 103, .10), transparent 32%),
                radial-gradient(circle at 94% 8%, rgba(31, 92, 255, .08), transparent 30%),
                var(--fm-bg);
            padding: 1.25rem;
        }

        .files-manager-panel {
            background: var(--fm-surface);
            border: 1px solid var(--fm-border);
            border-radius: 1rem;
            box-shadow: 0 18px 36px rgba(15, 23, 42, .08);
            overflow: hidden;
        }

        .files-manager-header {
            background: linear-gradient(120deg, #102033, #0f766e);
            color: #f8fafc;
            padding: 1.15rem 1.35rem;
        }

        .files-manager-header h4 {
            margin: 0;
            font-weight: 700;
            letter-spacing: .02em;
        }

        .files-manager-header .subtitle {
            color: rgba(248, 250, 252, .72);
            font-size: .88rem;
        }

        .files-manager-card {
            border: 1px solid var(--fm-border);
            border-radius: .85rem;
            background: #fff;
            padding: 1rem;
            height: 100%;
        }

        .files-manager-card h6 {
            color: var(--fm-muted);
            font-size: .75rem;
            font-weight: 700;
            letter-spacing: .08em;
            margin-bottom: .75rem;
            text-transform: uppercase;
        }

        .token-button {
            border-radius: 999px;
            font-size: .78rem;
            padding: .2rem .65rem;
        }

        .files-table-wrap {
            overflow-x: auto;
        }

        .files-table-wrap thead th {
            position: sticky;
            top: 0;
            z-index: 1;
            white-space: nowrap;
        }

        .file-name-cell {
            min-width: 18rem;
        }

        .status-pill {
            border-radius: 999px;
            display: inline-flex;
            align-items: center;
            gap: .25rem;
            padding: .2rem .55rem;
            font-size: .75rem;
            font-weight: 700;
        }

        .status-pill.ok {
            background: rgba(38, 255, 103, .16);
            color: #05603a;
        }

        .status-pill.warn {
            background: rgba(245, 158, 11, .16);
            color: #92400e;
        }
    </style>
@endpush

<div class="files-manager-page">
    @php
        $isSuperAdm = (bool) auth()->user()?->superadm;
    @endphp

    <x-show-loading />

    <div class="files-manager-panel">
        <div class="files-manager-header d-flex flex-column flex-xl-row align-items-xl-center justify-content-between gap-3">
            <div>
                <h4>GERENCIAMENTO DE ARQUIVOS</h4>
                <div class="subtitle">Localize, selecione e extraia arquivos em lote com nomes padronizados.</div>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <button class="btn btn-outline-light btn-sm" wire:click.prevent="clearExtractionFilters"
                    wire:loading.attr="disabled">
                    <i class="ri-filter-off-line me-1"></i>Limpar filtros
                </button>
                <button class="btn btn-outline-light btn-sm" wire:click.prevent="export_excel"
                    wire:loading.attr="disabled" wire:target="export_excel">
                    <i class="ri-file-excel-2-line align-middle" wire:loading.remove wire:target="export_excel"></i>
                    <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true" wire:loading
                        wire:target="export_excel"></span>
                    Exportar lista
                </button>
                <button class="btn btn-outline-light btn-sm" wire:click.prevent="checkFilesExists"
                    wire:loading.attr="disabled" wire:target="checkFilesExists">
                    <i class="ri-link-m align-middle" wire:loading.remove wire:target="checkFilesExists"></i>
                    <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true" wire:loading
                        wire:target="checkFilesExists"></span>
                    Verificar arquivos
                </button>
                <button class="btn btn-success btn-sm" wire:click.prevent="downloadZip" wire:loading.attr="disabled"
                    wire:target="downloadZip" @disabled(!count($selectedFiles))>
                    <i class="ri-download-2-line align-middle" wire:loading.remove wire:target="downloadZip"></i>
                    <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true" wire:loading
                        wire:target="downloadZip"></span>
                    Baixar selecionados
                </button>
            </div>
        </div>

        <div class="p-3">
            <div class="row g-3">
                <div class="col-12 col-xl-4">
                    <div class="files-manager-card">
                        <h6>Pesquisa</h6>
                        <div class="row g-2">
                            <div class="col-12 col-sm-4">
                                <div class="form-floating">
                                    <select class="form-select border border-secondary" wire:model="perPage"
                                        id="perPageFiles">
                                        <option value="25">25</option>
                                        <option value="50">50</option>
                                        <option value="100">100</option>
                                        <option value="150">150</option>
                                        <option value="250">250</option>
                                        <option value="500">500</option>
                                    </select>
                                    <label for="perPageFiles">Por pagina</label>
                                </div>
                            </div>
                            <div class="col-12 col-sm-8">
                                <div class="form-floating">
                                    <input type="text" class="form-control border border-secondary"
                                        placeholder="Buscar" wire:model.debounce.300ms="search" id="searchFiles">
                                    <label for="searchFiles">Buscar arquivo, nota ou ordem</label>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="input-group">
                                    <div class="form-floating">
                                        <textarea class="form-control border border-secondary" id="massSearchFiles" style="height: 62px"
                                            wire:model.defer="massSearch" placeholder="Cole notas ou ordens"></textarea>
                                        <label for="massSearchFiles">Notas ou ordens em massa</label>
                                    </div>
                                    <button class="btn btn-outline-secondary" wire:click.prevent="applyMassSearch"
                                        data-bs-toggle="tooltip" data-bs-placement="top"
                                        data-bs-title="Aplicar busca em massa">
                                        <i class="ri-search-2-line"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-xl-4">
                    <div class="files-manager-card">
                        <h6>Filtros</h6>
                        <div class="row g-2">
                            <div class="col-12 col-md-6">
                                <div class="form-floating">
                                    <select class="form-select border border-secondary" wire:model="fileType"
                                        id="fileType">
                                        @foreach ($fileTypeOptions as $value => $label)
                                            <option value="{{ $value }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                    <label for="fileType">Tipo de arquivo</label>
                                </div>
                            </div>
                            <div class="col-12 col-md-6">
                                <div class="form-floating">
                                    <select class="form-select border border-secondary" wire:model="service"
                                        id="serviceFiles">
                                        <option value="">Todos</option>
                                        @foreach ($services as $serv)
                                            <option value="{{ $serv->uuid }}">{{ $serv->service }}</option>
                                        @endforeach
                                    </select>
                                    <label for="serviceFiles">Origem do arquivo</label>
                                </div>
                            </div>
                            <div class="col-12 col-md-6">
                                <div class="form-floating">
                                    <select class="form-select border border-secondary" wire:model="companySelected"
                                        id="companyFiles">
                                        <option value="">Todas</option>
                                        @foreach ($companies as $company)
                                            <option value="{{ $company->id }}">{{ $company->name }}</option>
                                        @endforeach
                                    </select>
                                    <label for="companyFiles">Empresa</label>
                                </div>
                            </div>
                            <div class="col-12 col-md-6">
                                <div class="form-floating">
                                    <select class="form-select border border-secondary" wire:model="rubricSelected"
                                        id="rubricFiles">
                                        <option value="">Todas</option>
                                        @foreach ($rubrics as $rubric)
                                            <option value="{{ $rubric['rubrica'] }}">{{ $rubric['rubrica'] }}</option>
                                        @endforeach
                                    </select>
                                    <label for="rubricFiles">Rubrica</label>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="form-check form-switch mt-1">
                                    <input class="form-check-input" type="checkbox" role="switch"
                                        id="onlyMissingFiles" wire:model="noFile">
                                    <label class="form-check-label" for="onlyMissingFiles">Somente arquivos nao
                                        localizados</label>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="form-check form-switch mt-1">
                                    <input class="form-check-input" type="checkbox" role="switch"
                                        id="partnerFinalAdsOnly" wire:model="partnerFinalAdsOnly">
                                    <label class="form-check-label" for="partnerFinalAdsOnly">Somente ADS final entregue
                                        pela parceria</label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-xl-4">
                    <div class="files-manager-card">
                        <h6>Nome do download</h6>
                        <div class="form-floating">
                            <input type="text" class="form-control border border-secondary"
                                wire:model="outputNamePattern" id="outputNamePattern"
                                placeholder="Opcional. Ex: &lt;nota&gt; &lt;ordem&gt; 001">
                            <label for="outputNamePattern">Formato opcional do nome</label>
                        </div>
                        <div class="d-flex flex-wrap gap-1 mt-2">
                            @foreach (['<nota>' => 'Nota/OV', '<ordem>' => 'Ordem', '<sequencia>' => 'Sequencia'] as $token => $label)
                                <button type="button" class="btn btn-outline-secondary token-button"
                                    wire:click.prevent="appendOutputToken(@js($token))">
                                    {{ $label }}
                                </button>
                            @endforeach
                        </div>
                        <div class="small text-muted mt-2">
                            Em branco, baixa com o nome salvo. Separadores e espacos ficam como digitados.
                        </div>
                    </div>
                </div>
            </div>

            <div class="summary-bar d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-2 my-3">
                <div class="text-muted">
                    Exibindo <strong>{{ $lists->firstItem() }}</strong> a <strong>{{ $lists->lastItem() }}</strong>
                    de <strong>{{ $lists->total() }}</strong> arquivos.
                    Selecionados: <strong>{{ count($selectedFiles) }}</strong> de <strong>100</strong>.
                </div>
                <div class="d-flex flex-wrap gap-2 align-items-center">
                    <button class="btn btn-outline-primary btn-sm" wire:click.prevent="selectAll">
                        <i class="ri-checkbox-multiple-line me-1"></i>Selecionar filtrados
                    </button>
                    <button class="btn btn-outline-secondary btn-sm" wire:click.prevent="deselectAll">
                        <i class="ri-checkbox-blank-line me-1"></i>Limpar selecao
                    </button>
                    {{ $lists->links() }}
                </div>
            </div>

            <div class="files-table-wrap border rounded">
                <table class="table table-sm table-striped table-hover align-middle mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th class="text-center" style="width: 44px;"></th>
                            <th class="text-center">Nota/OV</th>
                            <th>Arquivo</th>
                            <th class="text-center">Tipo</th>
                            <th class="text-center">Ordem preferencial</th>
                            <th class="text-center">Tamanho</th>
                            <th class="text-center">Origem</th>
                            <th class="text-center">Responsavel</th>
                            <th class="text-center">Empresa</th>
                            <th class="text-center">Recebido em</th>
                            <th class="text-center">Status</th>
                            <th class="text-center">Acoes</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($lists as $list)
                            @php
                                $f_exists = Storage::exists($list->path);
                                $isTacitRestricted = (bool) ($list->has_tacit_ads_restriction ?? false);
                                $isBlockedForUser = !$isSuperAdm && $isTacitRestricted;
                            @endphp
                            <tr wire:key="fileRow-{{ $list->id }}"
                                class="text-center @if (!$f_exists) table-warning @endif @if ($isBlockedForUser) table-secondary @endif"
                                @if ($isBlockedForUser) style="opacity: .6;" @endif>
                                <td>
                                    <input type="checkbox" class="form-check-input" wire:model.defer="selectedFiles"
                                        value="{{ $list->id }}" @disabled($isBlockedForUser)
                                        title="{{ $isBlockedForUser ? 'Selecao bloqueada para este perfil.' : '' }}">
                                </td>
                                <td class="fw-bold">{{ $list->Note?->note ?: '-' }}</td>
                                <td class="text-start file-name-cell">
                                    <div class="fw-semibold">{{ $list->file_name }}</div>
                                    <div class="small text-muted text-uppercase">
                                        {{ $list->ext ?: pathinfo($list->file_name, PATHINFO_EXTENSION) ?: 'sem extensao' }}
                                    </div>
                                </td>
                                <td>{{ $this->fileTypeLabel($list) }}</td>
                                <td>{{ $this->preferredOrder($list) ?: '-' }}</td>
                                <td>
                                    @if ($f_exists)
                                        {{ $this->formatFileSize(Storage::size($list->path)) }}
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>{{ $list->Service?->service ?: '-' }}</td>
                                <td>{{ $list->User?->name ?: '-' }}</td>
                                <td>{{ $list->User?->Company?->name ?: '-' }}</td>
                                <td>{{ $list->created_at ? date('d/m/Y H:i', strtotime($list->created_at)) : '-' }}</td>
                                <td>
                                    @if ($isBlockedForUser)
                                        <span class="status-pill warn"><i class="ri-lock-2-line"></i>Restrito</span>
                                    @elseif ($f_exists)
                                        <span class="status-pill ok"><i class="ri-check-line"></i>Disponivel</span>
                                    @else
                                        <span class="status-pill warn"><i class="ri-error-warning-line"></i>Nao localizado</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="d-flex justify-content-center gap-2">
                                        <i class="ri-pencil-fill text-primary fs-5" style="cursor: pointer;"
                                            title="Editar"
                                            wire:click.prevent="$emitTo('files.manager.fileedit', 'editFile', {{ $list }})"></i>
                                        @if ($f_exists)
                                            @if ($isBlockedForUser)
                                                <i class="ri-lock-2-line text-muted fs-5" title="Download restrito"></i>
                                            @else
                                                <i class="ri-download-cloud-2-line text-primary fs-5"
                                                    style="cursor: pointer;" title="Baixar"
                                                    wire:click.prevent="downloadFile({{ $list }})"></i>
                                            @endif
                                        @endif
                                        <i class="ri-upload-cloud-2-fill text-primary fs-5" style="cursor: pointer;"
                                            title="Adicionar arquivo"
                                            wire:click.prevent="$emitTo('files.manager.createfiles', 'createFile', {{ $list->Note }})"></i>
                                        <i class="ri-delete-bin-2-line text-danger fs-5" style="cursor: pointer;"
                                            title="Excluir"
                                            wire:click.prevent="$emitTo('files.manager.fileedit', 'deleteFile', {{ $list }})"></i>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="12" class="text-center text-muted py-4">
                                    Nenhum arquivo encontrado.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="d-flex justify-content-center mt-3">
                {{ $lists->links() }}
            </div>
        </div>
    </div>

    @livewire('files.manager.fileedit', key('file-edit'))
    @livewire('files.manager.createfiles', key('create-files'))
</div>
