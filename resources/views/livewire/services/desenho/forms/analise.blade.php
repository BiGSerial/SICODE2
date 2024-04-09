<div>

    {{-- Carrega o Loading da página --}}
    <x-show-loading />

    @if ($view_form)
        <div class="container">
            <div class="card">
                <h4 class="card-header">Informações da Nota</h4>
                <div class="card-body">
                    <div class="row">
                        <div class="col-6">
                            <dl class="row">
                                <dt class="col-sm-4">Nota/Ov:</dt>
                                <dd class="col-sm-8">{{ $note->note }}</dd>
                                <dt class="col-sm-4">Cliente:</dt>
                                <dd class="col-sm-8">{{ $note->client }}</dd>
                                <dt class="col-sm-4">Município</dt>
                                <dd class="col-sm-8">{{ $note->lexp }}</dd>
                                <dt class="col-sm-4 text-danger">MMGD</dt>
                                <dd class="col-sm-8 text-danger">{{ $note->mmgd ? 'SIM' : 'NÃO' }}</dd>
                            </dl>
                        </div>

                        <div class="col-6">
                            <dl class="row">
                                <dt class="col-sm-4">Tipo:</dt>
                                <dd class="col-sm-8">{{ $note->rubrica }}</dd>
                                <dt class="col-sm-4">Data:</dt>
                                <dd class="col-sm-8">{{ date('d/m/Y', strToTime($note->dt_status)) }}</dd>
                                <dt class="col-sm-4">Pedido:</dt>
                                <dd class="col-sm-8">{{ $note->numPedido }}</dd>
                                <dt class="col-sm-4">Rede:</dt>
                                <dd class="col-sm-8">{{ $note->group2 }}</dd>
                                <dt class="col-sm-4">Custo:</dt>
                                <dd class="col-sm-8">{{ $note->group5 }}</dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>





            <div class="card">
                <h4 class="card-header">RESULTADO DESENHO</h4>
                <div class="card-body">

                    <div class="row">

                        <div class="mb-3 col-3">
                            <label for="inputPassword" class="col-sm-12 col-form-label">Finalidade:</label>
                            <select class="form-select border border-secondary" aria-label="Default select example"
                                wire:model="preresult">
                                @if ($production->d5)
                                    <option value="RESOLUCAO INTERNA" selected>RESOLUÇÃO INTERNA (RI)</option>
                                @else
                                    <option value="" selected>Selecione</option>
                                    <option value="ANALISE">ANALISE</option>
                                    <option value="NORMAL">NORMAL</option>
                                    <option value="REVALIDACAO">REVALIDAÇÃO</option>
                                    <option value="CUSTO MODULAR">CUSTO MODULAR</option>
                                    <option value="PROPOSTA MELHORAMENTO">PROPOSTA MELHORAMENTO</option>
                                @endif
                            </select>
                        </div>

                        @php
                            if (
                                ($preresult !== 'NORMAL' && $preresult !== 'REVALIDACAO') ||
                                $this->conclusion === 'ARQUIVADO' ||
                                $this->conclusion === 'RETORNADO LEVANTAMENTO'
                            ) {
                                $this->postes = 1;
                                $this->odi = '';
                                $this->odd = '';
                                $this->ods = '';
                            }
                        @endphp

                        <div class="mb-3 col-2">
                            <label for="inputPassword" class="col-sm-12 col-form-label">ODI/DR:</label>
                            <input type="text" class="form-control border border-secondary" wire:model.defer="odi"
                                @disabled(
                                    ($preresult !== 'NORMAL' && $preresult !== 'REVALIDACAO') ||
                                        $this->conclusion === 'ARQUIVADO' ||
                                        $this->conclusion === 'RETORNADO LEVANTAMENTO')>
                        </div>
                        <div class="mb-3 col-2">
                            <label for="inputPassword" class="col-sm-12 col-form-label">ODD/PEP:</label>
                            <input type="text" class="form-control border border-secondary" wire:model.defer="odd"
                                @disabled(
                                    ($preresult !== 'NORMAL' && $preresult !== 'REVALIDACAO') ||
                                        $this->conclusion === 'ARQUIVADO' ||
                                        $this->conclusion === 'RETORNADO LEVANTAMENTO')>
                        </div>
                        <div class="mb-3 col-2">
                            <label for="inputPassword" class="col-sm-12 col-form-label">ODS:</label>
                            <input type="text" class="form-control border border-secondary" wire:model.defer="ods"
                                @disabled(
                                    ($preresult !== 'NORMAL' && $preresult !== 'REVALIDACAO') ||
                                        $this->conclusion === 'ARQUIVADO' ||
                                        $this->conclusion === 'RETORNADO LEVANTAMENTO')>
                        </div>
                        <div class="mb-3 col-2">
                            <label for="inputPassword" class="col-sm-12 col-form-label">Postes:</label>
                            <input type="number" min="0" max="300"
                                class="form-control border border-secondary" wire:model.defer="postes"
                                @disabled(
                                    ($preresult !== 'NORMAL' && $preresult !== 'REVALIDACAO') ||
                                        $this->conclusion === 'ARQUIVADO' ||
                                        $this->conclusion === 'RETORNADO LEVANTAMENTO')>
                        </div>


                        @if (($preresult === 'NORMAL' || $preresult === 'REVALIDACAO') && !$production->d5)
                            <div class="col-12">
                                <div class="form-check form-check-inline col-2">
                                    <input class="form-check-input border border-1 border-secondary" type="checkbox"
                                        wire:model.defer="eo" value="EO">
                                    <label class="form-check-label" for="inlineCheckbox1">EO</label>
                                </div>
                                <div class="form-check form-check-inline col-2">
                                    <input class="form-check-input border border-1 border-secondary" type="checkbox"
                                        wire:model.defer="iproject" value="option1">
                                    <label class="form-check-label" for="inlineCheckbox1">iProject</label>
                                </div>
                                <div class="form-check form-check-inline col-2">
                                    <input class="form-check-input border border-1 border-secondary" type="checkbox"
                                        wire:model="cadastro" value="cadastro">
                                    <label class="form-check-label" for="inlineCheckbox1">Cadastro</label>
                                </div>
                            </div>
                        @endif

                        @if ($cadastro)
                            <div class="mb-3 col-2">
                                <label for="inputPassword" class="col-sm-12 col-form-label">Postes Cadastro:</label>
                                <input type="number" min="0" max="300"
                                    class="form-control border border-secondary" wire:model.defer="postes_c">
                            </div>
                        @endif


                        <div class="mb-3 col-4">
                            <label for="inputPassword" class="col-sm-12 col-form-label">Conclusão:</label>
                            <select class="form-select border border-secondary" aria-label="Default select example"
                                wire:model="conclusion">
                                <option value="" selected>Selecione</option>
                                {{-- <option value="ENVIADO PARA ATENDIMENTO">01 - ENVIADO PARA ATENDIMENTO</option> --}}
                                @if ($production->d5)
                                    <option value="COLOCAR ANEXO DO PROJETO">(RI) COLOCAR ANEXO DO PROJETO</option>
                                    <option value="ALTERAÇÃO DO PROJETO">(RI) ALTERAÇÃO DO PROJETO</option>
                                @else
                                    <option value="EM CONTATO COM CLIENTE">10 - EM CONTATO COM CLIENTE</option>
                                    <option value="DEPENDE DE ORGAO EXTERNO">20 - DEPENDE DE ORGÃO EXTERNO</option>
                                    <option value="RETORNADO LEVANTAMENTO">27 - RETORNADO LEVANTAMENTO</option>
                                    <option value="EXECUCAO DE OBRAS DA EMPRESA">47 - EXECUÇÃO DE OBRAS DA EMPRESA
                                    </option>
                                    <option value="EXECUCAO DE OBRAS CUSTO EMPRESA">50 - EXECUÇÃO DE OBRAS CUSTO
                                        EMPRESA
                                    </option>
                                    <option value="ORÇAMENTO ESTIMADO">68 - ORÇAMENTO ESTIMADO</option>
                                    <option value="ORÇAMENTO PRÉVIO">70 - ORÇAMENTO PRÉVIO</option>
                                    <option value="ARQUIVADO">99 - ARQUIVADO</option>
                                @endif


                            </select>
                        </div>

                        <div class="mb-3">



                            <div class="my-2"> <button class="btn btn-sm btn-primary"
                                    onclick="document.getElementById('file-input').click()">CARREGAR PROJETO</button>
                            </div>
                            <div x-data="{ isUploading: false, progress: 0 }" x-on:livewire-upload-start="isUploading = true"
                                x-on:livewire-upload-finish="isUploading = false"
                                x-on:livewire-upload-error="isUploading = false"
                                x-on:livewire-upload-progress="progress = $event.detail.progress">

                                <form wire:submit.prevent="saveFile">
                                    <input type="file" id="file-input" multiple wire:model="files"
                                        value="{{ $this->production->Note->note }}" accept=".pdf,.gif,.jpg,.png"
                                        hidden>
                                    {{-- <button type="submit" id="id-submit"></button> --}}
                                </form>

                                <div x-show="isUploading" class="mb-3">
                                    {{-- <div class="progress-bar progress-bar-striped progress-bar-animated"
                                    role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"
                                    x-bind:style="`width: ${progress}%`">
                                    <span class="align-middle" x-text="`${progress}%`"></span>
                                </div> --}}
                                    <div class="progress my-0" role="progressbar" aria-label="Danger example"
                                        aria-valuenow="100" aria-valuemin="0" aria-valuemax="100"
                                        style="width: 100%; border-radius: 0;">
                                        <span class="progress-bar bg-danger" x-bind:style="`width: ${progress}%`"
                                            x-text="`${progress}%`">
                                    </div>
                                </div>
                            </div>
                            <div class="edp-bg-gray mb-3 py-2 rounded ">
                                <div class="container">

                                    @if (count($show_files))
                                        @foreach ($show_files as $show)
                                            <div
                                                class="col-5 border border-secondary d-flex justify-content-between align-items-center p-0 mb-2 bg-white">
                                                <div class="p-1 m-0 border-end border-secondary"><i
                                                        class="bx bxs-file-{{ $show['ext'] }} text-danger fs-4"></i>
                                                </div>
                                                <div class="p-1 m-0 text-center no-wrap">
                                                    <p class="my-0 py-0">
                                                        {{ $show['name'] }}
                                                    </p>
                                                    <p class="my-0 py-0 text-danger" style="font-size: 12px;">
                                                        {{ $show['old_name'] }}
                                                    </p>
                                                </div>
                                                <div class="p-1 m-0 border-start border-secondary">
                                                    <i class="bx bxs-trash text-danger fs-4"
                                                        wire:click.prevent="delete_file({{ $show['id'] }})"
                                                        style="cursor: pointer;"></i>
                                                </div>
                                            </div>
                                        @endforeach
                                    @else
                                        <div class="my-2 py-2 text-center">
                                            <h4 class="fw-bold">SEM ARQUIVOS</h4>
                                        </div>
                                    @endif

                                </div>
                            </div>

                            @if ($nota_divergente)
                                <div class="card">
                                    <div class="card-body">
                                        <p class="fs-5 text-danger fw-bold text-center">O arquivo carregado parece
                                            estar divergente da nota/ordem de venda trabalhada neste formulário.
                                            Certifique-se de que o arquivo corresponda ao projeto desta nota/ordem de
                                            venda. </p>
                                    </div>
                                </div>
                            @endif
                        </div>

                        <div class="mb-3">
                            <label for="inputPassword" class="col-sm-12 col-form-label">Informações: <span
                                    class="fw-bold"><i class="ri-file-copy-line copyButton" data-id="infoTextArea2"
                                        style="cursor: pointer;"></i></span></label>
                            <textarea id="infoTextArea2" class="form-control border border-secondary" rows="8" wire:model.defer="info"
                                style="text-align: left;"></textarea>
                        </div>


                    </div>

                </div>
            </div>


            <div class="d-flex justify-content-end">
                <button class="btn btn-primary me-2" wire:click.prevent="save_info">SALVAR</button>
                <button class="btn btn-warning me-2" wire:click.prevent="to_pause">PAUSAR</button>
                <button class="btn btn-success me-2"
                    wire:click.prevent="to_finish({{ $analise->production_id }})">ENCERRAR</button>

            </div>
        @else
            <div class="loading-overlay">
                <div class="loading-message">
                    <h1>Carregando Dados...</h1>
                </div>
            </div>
    @endif
</div>
