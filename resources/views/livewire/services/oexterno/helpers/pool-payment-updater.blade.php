<div x-data="{
    fileName: '{{ addslashes($preview['filename'] ?? '') }}',
    initialFileName: '{{ addslashes($preview['filename'] ?? '') }}',
    initialRows: {{ $preview['rows'] ?? 0 }},
    uploading: false,
    progress: 0,
    readyToProcess: @entangle('readyToProcess')
}" x-on:livewire-upload-start.window="uploading=true; progress=0"
    x-on:livewire-upload-finish.window="uploading=false" x-on:livewire-upload-error.window="uploading=false"
    x-on:livewire-upload-progress.window="progress=$event.detail.progress" x-init="$wire.on('poolpayment:cleared', () => {
        fileName = '';
        initialFileName = '';
        initialRows = 0;
        progress = 0;
        uploading = false;
        readyToProcess = false;
    });
    $wire.on('poolpayment:done', () => {
        fileName = '';
        initialFileName = '';
        initialRows = 0;
        $refs.file.value = null;
        progress = 0;
        uploading = false;
        readyToProcess = false;
        @this.call('removeFile');
    });"
    class="card border-0 shadow-sm">
    <div class="card-body">
        <div class="d-flex align-items-center justify-content-between mb-2">
            <div>
                <h6 class="mb-0">Atualizar Pool de Pagamento</h6>
                <small class="text-muted">.xlsx ou .csv — máx. 10MB</small>
            </div>
            <div class="d-flex gap-2">
                <input x-ref="file" type="file" class="d-none" accept=".xlsx,.csv" wire:model="file"
                    @change="fileName=$event.target.files[0]?.name ?? ''; initialFileName=''; initialRows=0; readyToProcess=false">

                {{-- Botão Selecionar: sempre visível, desabilitado durante upload --}}
                <button type="button" class="btn btn-sm btn-outline-secondary" @click="$refs.file.click()"
                    :disabled="uploading">
                    Selecionar
                </button>

                {{-- Botão Remover: visível se houver um arquivo no Alpine OU no preview do Livewire, desabilitado durante upload --}}
                <button type="button" class="btn btn-sm btn-outline-secondary"
                    @click="fileName=''; initialFileName=''; initialRows=0; $refs.file.value=null; progress=0; uploading=false; readyToProcess=false; @this.call('removeFile')"
                    :disabled="uploading || (!fileName && !initialFileName)">
                    Remover
                </button>

                {{-- Botão Processar: visível se houver um arquivo, desabilitado durante upload ou se não estiver pronto para processar --}}
                <button type="button" class="btn btn-sm btn-primary" wire:click.prevent="process" wire:target="process"
                    :disabled="uploading || !readyToProcess">
                    <span wire:loading.remove wire:target="process">Processar</span>
                    <span wire:loading wire:target="process">
                        <span class="spinner-border spinner-border-sm me-1"></span> Processando…
                    </span>
                </button>
            </div>
        </div>

        {{-- Mensagens de sucesso/erro que não são de validação de formulário --}}
        @if (session()->has('pp_ok'))
            <div class="alert alert-success mt-2" role="alert">
                {{ session('pp_ok') }}
            </div>
        @endif
        @error('file')
            <div class="text-danger small mt-2">{{ $message }}</div>
        @enderror

        {{-- Seção de pré-visualização: visível se houver um nome de arquivo no Alpine ou no preview do Livewire --}}
        <template x-if="fileName || initialFileName">
            <div class="mt-3">
                <div class="d-flex align-items-center justify-content-between small">
                    <div class="text-truncate me-2">
                        {{-- Exibe o nome do arquivo do Alpine (se um novo foi selecionado) ou do Livewire (se já existia no preview) --}}
                        <span x-text="fileName || initialFileName"></span>
                        {{-- Exibe o número de linhas apenas se for maior que zero --}}
                        <template x-if="initialRows > 0">
                            <span class="text-muted"> • {{ $preview['rows'] }} linhas</span>
                        </template>
                    </div>
                    <div x-show="uploading" class="text-muted" x-text="progress + '%'"></div>
                </div>
                <div class="progress mt-1" role="progressbar" :aria-valuenow="progress" aria-valuemin="0"
                    aria-valuemax="100">
                    <div class="progress-bar" :style="`width:${progress}%;`"></div>
                </div>

                @if ($preview['rows'] > 0) {{-- Detalhes da pré-visualização só são exibidos se houver linhas --}}
                    <div class="mt-3 small">
                        <div class="d-flex flex-wrap gap-2">
                            <span class="badge bg-dark-subtle text-dark">Linhas: {{ $preview['rows'] }}</span>
                            @if (!empty($preview['columns']))
                                <span class="badge bg-dark-subtle text-dark">
                                    Colunas: {{ implode(', ', $preview['columns']) }}
                                </span>
                            @endif
                        </div>

                        @if (!empty($preview['missing']))
                            <div class="mt-2">
                                <div class="text-warning fw-semibold">Campos obrigatórios ausentes:</div>
                                <ul class="mb-0 ps-3">
                                    @foreach ($preview['missing'] as $m)
                                        <li class="text-warning">{{ $m }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        @if (!empty($preview['typeErrors']))
                            <div class="mt-2">
                                <div class="text-danger fw-semibold">Erros de tipo:</div>
                                <ul class="mb-0 ps-3">
                                    @foreach ($preview['typeErrors'] as $err)
                                        <li class="text-danger">
                                            Linha {{ $err['row'] }} – campo <code>{{ $err['column'] }}</code>:
                                            "{{ $err['value'] }}" esperado <em>{{ $err['expected'] }}</em>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        {{-- Mensagem de "Pronto para processar" só se não houver erros e readyToProcess for true --}}
                        <template x-if="readyToProcess">
                            <div class="mt-2 text-success d-flex align-items-center gap-2">
                                <i class="bi bi-check2-circle"></i>
                                Pronto para processar.
                            </div>
                        </template>
                    </div>
                @endif
            </div>
        </template>
    </div>
</div>
