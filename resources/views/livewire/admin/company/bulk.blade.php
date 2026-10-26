@php
    $actionLabels = ['create' => ['Criar', 'success'], 'update' => ['Atualizar', 'primary'], 'remove' => ['Remover', 'danger'], 'noop' => ['Sem alteração', 'secondary']];
@endphp
<div class="d-inline-flex gap-2 flex-wrap">
    <button type="button" class="btn btn-outline-success" wire:click.prevent="exportAll" wire:loading.attr="disabled" wire:target="exportAll">
        <i class="ri-file-excel-2-line" wire:loading.remove wire:target="exportAll"></i>
        <span class="spinner-border spinner-border-sm" wire:loading wire:target="exportAll"></span>
        Exportar todas
    </button>
    <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#company_bulk_modal">
        <i class="ri-file-upload-line"></i> Planilha em massa
    </button>

    <div wire:ignore.self class="modal fade" id="company_bulk_modal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-xl modal-dialog-scrollable text-start">
            <div class="modal-content border-0 shadow">
                <div class="modal-header text-white" style="background: linear-gradient(130deg, #0f172a 0%, #0f766e 80%);">
                    <h1 class="modal-title fs-5"><i class="ri-file-excel-2-line fs-4 align-middle"></i> Cadastro em massa por planilha</h1>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fechar" wire:click="cancel"></button>
                </div>
                <div class="modal-body bg-light">
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <div class="card border-0 shadow-sm h-100">
                                <div class="card-body">
                                    <div class="fw-bold mb-1"><span class="badge text-bg-success me-1">1</span> Baixe a planilha</div>
                                    <p class="small text-muted mb-3">Empresas, centros e depósitos, contratos e atividades em abas. Listas suspensas, campos protegidos e validação de entrada: não há como digitar um valor inválido.</p>
                                    <div class="d-flex gap-2 flex-wrap">
                                        <button type="button" class="btn btn-sm btn-success" wire:click.prevent="exportAll" wire:loading.attr="disabled" wire:target="exportAll">
                                            <i class="ri-download-2-line"></i> Cadastro atual (editar)
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-secondary" wire:click.prevent="downloadTemplate" wire:loading.attr="disabled" wire:target="downloadTemplate">
                                            <i class="ri-file-line"></i> Modelo em branco
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card border-0 shadow-sm h-100">
                                <div class="card-body">
                                    <div class="fw-bold mb-1"><span class="badge text-bg-success me-1">2</span> Envie para conferência</div>
                                    <p class="small text-muted mb-3">Nada é gravado agora. O sistema confere tudo e mostra o que será criado, atualizado ou removido.</p>
                                    <input type="file" class="form-control" wire:model="upload" accept=".xlsx" wire:loading.attr="disabled" wire:target="upload">
                                    <div class="small text-muted mt-2" wire:loading wire:target="upload"><span class="spinner-border spinner-border-sm"></span> Conferindo a planilha...</div>
                                    @error('upload')
                                        <div class="text-danger small mt-2">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>

                    @if ($fatal)
                        <div class="alert alert-danger"><i class="ri-error-warning-line"></i> {{ $fatal }}</div>
                    @endif

                    @if ($applied)
                        <div class="alert alert-success mb-0"><i class="ri-checkbox-circle-line"></i> Importação concluída: {{ $summary['changes'] ?? 0 }} alteração(ões) gravada(s). A lista foi atualizada.</div>
                    @elseif ($summary)
                        <div class="row g-2 mb-3">
                            @foreach ($summary['kinds'] as $kind)
                                <div class="col-6 col-lg-3">
                                    <div class="card border-0 shadow-sm h-100">
                                        <div class="card-body py-2">
                                            <div class="small text-muted">{{ $kind['label'] }}</div>
                                            <div class="small">
                                                <span class="text-success fw-semibold">{{ $kind['create'] }} novos</span> ·
                                                <span class="text-primary fw-semibold">{{ $kind['update'] }} atualizados</span> ·
                                                <span class="text-danger fw-semibold">{{ $kind['remove'] }} remoções</span>
                                            </div>
                                            <div class="small text-muted">{{ $kind['noop'] }} sem alteração @if ($kind['error']) · <span class="text-danger fw-semibold">{{ $kind['error'] }} com erro</span> @endif</div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        @if ($summary['errors'] > 0)
                            <div class="alert alert-danger"><i class="ri-error-warning-line"></i> <strong>{{ $summary['errors'] }} erro(s)</strong> encontrado(s). Nada foi gravado. Corrija a planilha e envie novamente.</div>
                        @elseif ($summary['changes'] === 0)
                            <div class="alert alert-secondary"><i class="ri-information-line"></i> A planilha está de acordo com o cadastro atual: nenhuma alteração a gravar.</div>
                        @else
                            <div class="alert alert-success"><i class="ri-checkbox-circle-line"></i> Planilha válida: <strong>{{ $summary['changes'] }} alteração(ões)</strong> serão gravadas de uma só vez{{ $summary['warnings'] ? ", com {$summary['warnings']} aviso(s)" : '' }}.</div>
                        @endif

                        @if ($rows)
                            <div class="btn-group btn-group-sm mb-2" role="group">
                                @foreach (['errors' => 'Erros', 'warn' => 'Avisos', 'changes' => 'Alterações', 'all' => 'Tudo'] as $key => $label)
                                    <button type="button" class="btn btn-outline-secondary {{ $filter === $key ? 'active' : '' }}" wire:click="$set('filter', '{{ $key }}')">{{ $label }}</button>
                                @endforeach
                            </div>
                            <div class="card border-0 shadow-sm">
                                <div class="table-responsive" style="max-height: 340px;">
                                    <table class="table table-sm align-middle mb-0">
                                        <thead class="table-light sticky-top">
                                            <tr><th style="width:150px">Aba / linha</th><th>Item</th><th style="width:130px">Resultado</th><th>Detalhe</th></tr>
                                        </thead>
                                        <tbody>
                                            @forelse ($this->visibleRows as $row)
                                                @php [$label, $color] = $actionLabels[$row['action']] ?? ['—', 'secondary']; @endphp
                                                <tr>
                                                    <td class="small text-muted">{{ $row['sheet'] }} · linha {{ $row['line'] }}</td>
                                                    <td class="small fw-semibold">{{ $row['label'] }}</td>
                                                    <td>
                                                        @if ($row['errors'])
                                                            <span class="badge text-bg-danger">Erro</span>
                                                        @else
                                                            <span class="badge text-bg-{{ $color }}">{{ $label }}</span>
                                                        @endif
                                                    </td>
                                                    <td class="small">
                                                        @foreach ($row['errors'] as $message)
                                                            <div class="text-danger">{{ $message }}</div>
                                                        @endforeach
                                                        @foreach ($row['warnings'] as $message)
                                                            <div class="text-warning-emphasis">⚠ {{ $message }}</div>
                                                        @endforeach
                                                        @if (!$row['errors'])
                                                            <div class="text-muted">{{ $row['detail'] }}</div>
                                                        @endif
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr><td colspan="4" class="text-center text-muted py-4">Nada nesta categoria.</td></tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        @endif
                    @endif
                </div>
                <div class="modal-footer">
                    @if ($summary && !$applied && ($summary['errors'] > 0 || $summary['warnings'] > 0))
                        <button type="button" class="btn btn-outline-secondary me-auto" wire:click.prevent="downloadIssues"><i class="ri-download-2-line"></i> Baixar inconsistências</button>
                    @endif
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" wire:click="cancel">{{ $applied ? 'Fechar' : 'Cancelar' }}</button>
                    @if ($summary && !$applied)
                        <button type="button" class="btn btn-primary" wire:click.prevent="confirm" wire:loading.attr="disabled" wire:target="confirm" @disabled(($summary['errors'] ?? 1) > 0 || ($summary['changes'] ?? 0) === 0)>
                            <span class="spinner-border spinner-border-sm" wire:loading wire:target="confirm"></span>
                            Confirmar e gravar
                        </button>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
