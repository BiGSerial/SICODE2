<div>
    @if ($mode === 'send' && $note)
        <div class="cpop-overlay" wire:click.self="close" @keydown.escape.window="$wire.close()">
            <div class="cpop cpop-md tone-red" role="dialog" aria-modal="true" aria-label="Enviar RI ao Desenho">
                <div class="cpop-head">
                    <span class="cpop-icon"><i class="ri-draft-line"></i></span>
                    <div>
                        <h3 class="cpop-title">Enviar RI ao Desenho</h3>
                        <div class="cpop-sub">Retorno interno da obra <strong>{{ $note->note }}</strong> para o Desenho corrigir.</div>
                    </div>
                    <button type="button" class="cpop-close" aria-label="Fechar" wire:click="close"><i class="ri-close-line"></i></button>
                </div>
                <div class="cpop-body">
                    <div class="alert alert-warning small py-2">
                        <strong>O RI vale para a obra inteira.</strong> Enquanto o Desenho não concluir, as <strong>{{ $states->count() }} Ordem(ns)</strong> abaixo ficam <strong>travadas</strong> (ninguém assume, inicia, finaliza ou transfere). Status e responsável não mudam; ao retornar, tudo continua de onde parou.
                    </div>
                    <div class="mb-3 small">
                        @foreach ($states as $state)
                            <span class="badge bg-light text-dark border me-1 mb-1" wire:key="ri-state-{{ $state->id }}">{{ $state->Order?->ordem }}</span>
                        @endforeach
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label for="ri-category" class="cpop-label">Categoria do retorno *</label>
                            <select id="ri-category" class="form-select @error('categoryId') is-invalid @enderror" wire:model="categoryId">
                                <option value="">Selecione…</option>
                                @foreach ($categories as $category)
                                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="ri-subcategory" class="cpop-label">Subcategoria *</label>
                            <select id="ri-subcategory" class="form-select @error('categoryId') is-invalid @enderror" wire:model="subcategoryId" @disabled($subcategories->isEmpty())>
                                <option value="">{{ $categoryId === '' ? 'Escolha a categoria primeiro' : 'Selecione…' }}</option>
                                @foreach ($subcategories as $subcategory)
                                    <option value="{{ $subcategory->id }}">{{ $subcategory->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        @error('categoryId') <div class="text-danger small">{{ $message }}</div> @enderror
                    </div>
                    <label for="ri-message" class="cpop-label">O que o Desenho precisa corrigir? *</label>
                    <textarea id="ri-message" class="form-control" rows="4" wire:model.defer="message" placeholder="Ex.: projeto divergente do executado; faltou o trecho X…"></textarea>
                    @error('message') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                    <div class="small text-muted mt-2">Cria um retorno (Reclaim) para o Desenho, igual ao RI da Contratação. O Desenho trata na tela dele e, ao concluir, as Ordens destravam sozinhas.</div>
                </div>
                <div class="cpop-foot">
                    <button type="button" class="btn btn-outline-secondary" wire:click="close">Cancelar</button>
                    <button type="button" class="btn btn-danger fw-semibold" wire:click="submit" wire:loading.attr="disabled" wire:target="submit"><i class="ri-send-plane-line"></i> Enviar RI</button>
                </div>
            </div>
        </div>
    @elseif ($mode === 'cancel' && $ri)
        <div class="cpop-overlay" wire:click.self="close" @keydown.escape.window="$wire.close()">
            <div class="cpop cpop-md tone-amber" role="dialog" aria-modal="true" aria-label="Cancelar RI">
                <div class="cpop-head">
                    <span class="cpop-icon"><i class="ri-lock-unlock-line"></i></span>
                    <div>
                        <h3 class="cpop-title">Cancelar RI</h3>
                        <div class="cpop-sub">Obra <strong>{{ $ri->Note?->note }}</strong> · enviado {{ $ri->sent_at?->format('d/m/Y H:i') }}</div>
                    </div>
                    <button type="button" class="cpop-close" aria-label="Fechar" wire:click="close"><i class="ri-close-line"></i></button>
                </div>
                <div class="cpop-body">
                    <div class="alert alert-info small py-2">Cancelar só <strong>destrava as Ordens</strong> do Encerramento. O retorno aberto no Desenho <strong>continua</strong> (é da outra atividade); um novo RI só poderá ser enviado depois que o Desenho concluir.</div>
                    <label for="ri-cancel" class="cpop-label">Motivo do cancelamento *</label>
                    <textarea id="ri-cancel" class="form-control" rows="3" wire:model.defer="message"></textarea>
                    @error('message') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                </div>
                <div class="cpop-foot">
                    <button type="button" class="btn btn-outline-secondary" wire:click="close">Voltar</button>
                    <button type="button" class="btn btn-warning fw-semibold" wire:click="submit" wire:loading.attr="disabled" wire:target="submit">Cancelar RI</button>
                </div>
            </div>
        </div>
    @endif
</div>
