@php
    use App\Helpers\SelectOptions;
@endphp

<div class="bg-light min-vh-100 d-flex flex-column">
    <x-show-loading />

    @if ($view_form)
        {{-- <header class="bg-primary text-white py-5">
            <div class="container">
                <h1 class="display-5 fw-bold">Painel de Controle da Nota</h1>
                <p class="lead">Gerencie informações, resultado de desenho e arquivos de forma clara e direta.</p>
            </div>
        </header> --}}

        <main class="container my-5 flex-grow-1">
            {{-- Seção 1: Informações da Nota --}}
            <section id="info-nota" class="mb-5">
                <h2 class="h4 mb-3">1. Informações da Nota</h2>
                <div class="row g-4">
                    <div class="col-md-6">
                        <div class="card shadow-sm h-100">
                            <div class="card-body">
                                <dl class="row mb-0">
                                    <dt class="col-5">Nota/OV:</dt>
                                    <dd class="col-7">{{ $note->note }}</dd>

                                    <dt class="col-5">Cliente:</dt>
                                    <dd class="col-7">{{ $note->client }}</dd>

                                    <dt class="col-5">Município:</dt>
                                    <dd class="col-7">{{ $note->lexp }}</dd>

                                    <dt class="col-5 text-danger">MMGD:</dt>
                                    <dd class="col-7 text-danger">{{ $note->mmgd ? 'SIM' : 'NÃO' }}</dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card shadow-sm h-100">
                            <div class="card-body">
                                <dl class="row mb-0">
                                    <dt class="col-5">Tipo:</dt>
                                    <dd class="col-7">{{ $note->rubrica }}</dd>

                                    <dt class="col-5">Data:</dt>
                                    <dd class="col-7">{{ date('d/m/Y', strtotime($note->dt_status)) }}</dd>

                                    <dt class="col-5">Pedido:</dt>
                                    <dd class="col-7">{{ $note->numPedido }}</dd>

                                    <dt class="col-5">Rede:</dt>
                                    <dd class="col-7">{{ $note->group2 }}</dd>

                                    <dt class="col-5">Custo:</dt>
                                    <dd class="col-7">{{ $note->group5 }}</dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            {{-- Seção 2: Resultado Desenho --}}
            <section id="resultado-desenho" class="mb-5">
                <h2 class="h4 mb-3">2. Resultado do Desenho</h2>
                <div class="card shadow-sm">
                    <div class="card-body">
                        <form>
                            <div class="row g-3 align-items-end">
                                <div class="col-lg-3">
                                    <label class="form-label fw-semibold">Finalidade</label>
                                    <select class="form-select" wire:model="preresult">
                                        @if ($production->d5)
                                            <option value="RESOLUCAO INTERNA">RESOLUÇÃO INTERNA (RI)</option>
                                        @else
                                            <option value="">Selecione...</option>
                                            <option value="ANALISE">ANÁLISE</option>
                                            <option value="NORMAL">NORMAL</option>
                                            <option value="REVALIDACAO">REVALIDAÇÃO</option>
                                            <option value="CUSTO MODULAR">CUSTO MODULAR</option>
                                            <option value="PROPOSTA MELHORAMENTO">PROPOSTA MELHORAMENTO</option>
                                        @endif
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label fw-semibold">ODI/DR</label>
                                    <input type="text" class="form-control" wire:model.defer="odi"
                                        @disabled(
                                            ($preresult !== 'NORMAL' && $preresult !== 'REVALIDACAO') ||
                                                in_array($conclusion, ['ARQUIVADO', 'RETORNADO LEVANTAMENTO']))>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label fw-semibold">ODD/PEP</label>
                                    <input type="text" class="form-control" wire:model.defer="odd"
                                        @disabled(
                                            ($preresult !== 'NORMAL' && $preresult !== 'REVALIDACAO') ||
                                                in_array($conclusion, ['ARQUIVADO', 'RETORNADO LEVANTAMENTO']))>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label fw-semibold">ODS</label>
                                    <input type="text" class="form-control" wire:model.defer="ods"
                                        @disabled(
                                            ($preresult !== 'NORMAL' && $preresult !== 'REVALIDACAO') ||
                                                in_array($conclusion, ['ARQUIVADO', 'RETORNADO LEVANTAMENTO']))>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label fw-semibold">Postes</label>
                                    <input type="number" min="0" max="300" class="form-control"
                                        wire:model.defer="postes" @disabled(
                                            ($preresult !== 'NORMAL' && $preresult !== 'REVALIDACAO') ||
                                                in_array($conclusion, ['ARQUIVADO', 'RETORNADO LEVANTAMENTO']))>
                                </div>
                            </div>

                            <div class="row g-3 mt-3">
                                @if (($preresult === 'NORMAL' || $preresult === 'REVALIDACAO') && !$production->d5)
                                    <div class="col-auto form-check">
                                        <input class="form-check-input" type="checkbox" wire:model.defer="eo"
                                            id="eoCheck">
                                        <label class="form-check-label" for="eoCheck">EO</label>
                                    </div>
                                    <div class="col-auto form-check">
                                        <input class="form-check-input" type="checkbox" wire:model.defer="iproject"
                                            id="ipCheck">
                                        <label class="form-check-label" for="ipCheck">iProject</label>
                                    </div>
                                    <div class="col-auto form-check">
                                        <input class="form-check-input" type="checkbox" wire:model.defer="cad"
                                            id="cadCheck">
                                        <label class="form-check-label" for="cadCheck">AutoCad</label>
                                    </div>
                                    <div class="col-auto form-check">
                                        <input class="form-check-input" type="checkbox" wire:model.defer="cadastro"
                                            id="cadCadastroCheck">
                                        <label class="form-check-label" for="cadCadastroCheck">Cadastro</label>
                                    </div>
                                @endif

                                @if ($cadastro)
                                    <div class="col-md-2">
                                        <label class="form-label fw-semibold">Postes Cadastro</label>
                                        <input type="number" min="0" max="300" class="form-control"
                                            wire:model.defer="postes_c">
                                    </div>
                                @endif
                            </div>

                            <div class="row g-3 mt-4">
                                <div class="col-md-4">
                                    <label class="form-label fw-semibold">Conclusão</label>
                                    <select class="form-select" wire:model="conclusion">
                                        <option value="">Selecione...</option>
                                        @if ($production->d5)
                                            @foreach (SelectOptions::getReclaimsOptions() as $opt)
                                                <option value="{{ $opt->value }}">{{ $opt->info }}</option>
                                            @endforeach
                                        @else
                                            @foreach (SelectOptions::getDrawConclusions() as $opt)
                                                <option value="{{ $opt->value }}">{{ $opt->reason }}</option>
                                            @endforeach
                                        @endif
                                    </select>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </section>

            {{-- Seção 3: Arquivos & Informações --}}
            <section id="arquivos-info" class="mb-5">
                <h2 class="h4 mb-3">3. Arquivos & Informações</h2>
                <div class="card shadow-sm">
                    <div class="card-body">
                        @livewire('files.manager.create-prod-files', ['production' => $production, 'needFiles' => $needFiles], key('production_' . $production->id))

                        @if ($nota_divergente)
                            <div class="alert alert-danger mt-3">
                                O arquivo parece divergente da nota/OV trabalhada.
                            </div>
                        @endif

                        <div class="mt-4">
                            <label class="form-label fw-semibold">Informações Adicionais</label>
                            <textarea class="form-control" rows="6" wire:model.defer="info"></textarea>
                        </div>
                    </div>
                </div>
            </section>
        </main>

        <footer class="bg-white py-3 border-top">
            <div class="container d-flex justify-content-end gap-2">
                <button class="btn btn-warning" wire:click.prevent="to_pause">Pausar</button>
                <button class="btn btn-success"
                    wire:click.prevent="to_finish({{ $analise->production_id }})">Encerrar</button>
                <button class="btn btn-primary" wire:click.prevent="save_info">Salvar</button>
            </div>
        </footer>
    @else
        <div class="d-flex justify-content-center align-items-center vh-100">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Carregando...</span>
            </div>
        </div>
    @endif
</div>
