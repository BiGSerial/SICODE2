{{-- Aba "Arquivos": todos os arquivos da Nota, de qualquer atividade (inclui ADS e ADS tácita). --}}
<div class="table-card mb-3">
    <div class="od-section-head">
        <div>
            <h3><i class="ri-folder-2-line text-muted"></i> Arquivos da obra</h3>
            <div class="small text-muted mt-1">{{ $files->count() }} arquivo(s){{ $fileType !== '' || $fileSearch !== '' ? ' com os filtros atuais' : '' }} · de todas as atividades</div>
        </div>
        <div class="d-flex flex-wrap gap-2 align-items-center">
            <div class="input-group input-group-sm" style="width: 220px;">
                <span class="input-group-text"><i class="ri-search-line"></i></span>
                <input type="search" class="form-control" placeholder="Nome do arquivo" wire:model.debounce.400ms="fileSearch" aria-label="Buscar arquivo">
            </div>
            <select class="form-select form-select-sm w-auto" wire:model="fileType" aria-label="Tipo de arquivo">
                <option value="">Todos os tipos</option>
                @foreach ($fileTypes as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach
            </select>
            <button type="button" class="btn btn-outline-secondary btn-sm" wire:click="selectAllFiles">Selecionar todos</button>
            <button type="button" class="btn btn-primary btn-sm" wire:click="downloadZip" @disabled($selectedFiles === [])><i class="ri-file-zip-line"></i> Baixar ZIP ({{ count($selectedFiles) }})</button>
        </div>
    </div>
    <div class="table-responsive dash-scroll">
        <table class="table table-sm align-middle mb-0 dash-table" style="min-width: 860px;">
            <thead>
                <tr>
                    <th class="ps-3" style="width: 40px;"></th>
                    <th>Arquivo</th>
                    <th>Tipo</th>
                    <th>Origem</th>
                    <th>Enviado por</th>
                    <th>Data</th>
                    <th class="pe-3 text-end">Baixar</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($files as $file)
                    <tr wire:key="work-file-{{ $file['id'] }}" @class(['table-warning' => !$file['exists']])>
                        <td class="ps-3"><input type="checkbox" class="form-check-input" value="{{ $file['id'] }}" wire:model.defer="selectedFiles" @disabled(!$file['exists']) aria-label="Selecionar {{ $file['name'] }}"></td>
                        <td>
                            <div class="fw-semibold small text-break">{{ $file['name'] }}</div>
                            <div class="ord-sub">{{ $file['ext'] ?: 'sem extensão' }}@if ($file['tacit']) · <span class="badge bg-warning text-dark">ADS tácita</span>@endif @unless ($file['exists']) · <span class="text-danger">não localizado</span>@endunless</div>
                        </td>
                        <td><span class="od-chip">{{ $fileTypes[$file['kind']] ?? 'Outros' }}</span></td>
                        <td class="small">{{ $file['origin'] ?? '—' }}</td>
                        <td><div class="small fw-semibold">{{ $file['author'] ?? '—' }}</div><div class="ord-sub">{{ $file['company'] ?? '' }}</div></td>
                        <td class="small text-nowrap">{{ $file['at']?->format('d/m/Y H:i') ?? '—' }}</td>
                        <td class="pe-3 text-end">
                            @if ($file['exists'])
                                <button type="button" class="btn btn-sm btn-outline-primary" wire:click="downloadFile({{ $file['id'] }})" aria-label="Baixar {{ $file['name'] }}"><i class="ri-download-2-line"></i></button>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7"><x-closure.empty-state class="py-4" icon="ri-folder-open-line" title="Nenhum arquivo" text="Esta obra não tem arquivos com os filtros atuais." /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
