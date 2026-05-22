@push('css')
    <style>
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: rgba(0, 0, 0, 0.2);
            z-index: 9999;
        }

        .loading-message {
            background-color: white;
            padding: 20px;
            border-radius: 5px;
            text-align: center;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
    </style>
@endpush

@php
    use App\Helpers\SelectOptions;
@endphp

<div class=" min-vh-100 d-flex flex-column">
    <x-show-loading />

    @if ($view_form)
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
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            {{-- Seção 2: Resultado Levantamento --}}
            <section id="resultado-levantamento" class="mb-5">
                <h2 class="h4 mb-3">2. Resultado do Levantamento</h2>
                <div class="card shadow-sm">
                    <div class="card-body">
                        <form class="row g-4">

                            <div class="col-lg-3">
                                <label class="form-label fw-semibold">Postes</label>
                                <input type="number" min="0" max="500"
                                    class="form-control border border-1 @error('postes') is-invalid @enderror"
                                    wire:model.defer="postes">
                                @error('postes')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-3">
                                <label class="form-label fw-semibold">Depende Órgão Externo</label>
                                <select class="form-select border border-1 @error('doe') is-invalid @enderror"
                                    wire:model.defer="doe">
                                    <option value="">Selecione...</option>
                                    <option value="SIM">SIM</option>
                                    <option value="NAO">NÃO</option>
                                    <option value="NAO SEI">NÃO SEI</option>
                                </select>
                                @error('doe')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-3">
                                <label class="form-label fw-semibold">Haverá Interferência em Vegetação</label>
                                <select class="form-select border border-1 @error('ma') is-invalid @enderror"
                                    wire:model.defer="ma">
                                    <option value="">Selecione...</option>
                                    <option value="SIM">SIM</option>
                                    <option value="NAO">NÃO</option>
                                </select>
                                @error('ma')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-3">
                                <label class="form-label fw-semibold">Conclusão</label>
                                <select class="form-select border border-1 @error('conclusion') is-invalid @enderror"
                                    wire:model="conclusion">
                                    <option value="">Selecione...</option>
                                    @foreach (SelectOptions::getSurveyConclusions() as $option)
                                        <option value="{{ $option->value }}">{{ $option->reason }}</option>
                                    @endforeach
                                </select>
                                @error('conclusion')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-12 d-flex align-items-center gap-3 flex-wrap">
                                <div class="form-check mb-0">
                                    <input class="form-check-input @error('cadastro') is-invalid @enderror" type="checkbox"
                                        wire:model="cadastro" id="levCadastroCheck">
                                    <label class="form-check-label" for="levCadastroCheck">Cadastro</label>
                                </div>

                                @if ($cadastro)
                                    <div style="max-width: 220px; width: 100%;">
                                        <label class="form-label fw-semibold mb-1">Postes Cadastro</label>
                                        <input type="number" min="0" max="500"
                                            class="form-control border border-1 @error('postes_c') is-invalid @enderror"
                                            wire:model.defer="postes_c">
                                        @error('postes_c')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                @endif
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

                        @livewire(
                            'files.manager.create-prod-files',
                            [
                                'production' => $production,
                                'needFiles' => false,
                            ],
                            key('production_' . $production->id)
                        )

                        @if ($nota_divergente ?? false)
                            <div class="alert alert-danger mt-3">
                                O arquivo parece divergente da nota/OV trabalhada.
                            </div>
                        @endif

                        <div class="mt-4">
                            <label class="form-label fw-semibold">
                                Informações Adicionais
                                <i class="ri-file-copy-line copyButton" data-id="infoTextArea"
                                    style="cursor: pointer;"></i>
                            </label>
                            <textarea id="infoTextArea" class="form-control border border-1 @error('info') is-invalid @enderror" rows="6"
                                wire:model.defer="info"></textarea>
                            @error('info')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                    </div>
                </div>
            </section>

        </main>

        <footer class="bg-white py-3 border-top">
            <div class="container d-flex justify-content-end gap-2">
                <button class="btn btn-warning" wire:click.prevent="to_pause">Pausar</button>
                <button class="btn btn-primary" wire:click.prevent="save_info">Salvar</button>
                <button class="btn btn-success"
                    wire:click.prevent="to_finish({{ $analise->production_id }})">Encerrar</button>
            </div>
        </footer>
    @else
        <div class="loading-overlay">
            <div class="loading-message">
                <h1>Carregando Dados...</h1>
            </div>
        </div>
    @endif

    @livewire('components.pausenote.pausenote2', key('pausenote-levantamento'))
</div>
