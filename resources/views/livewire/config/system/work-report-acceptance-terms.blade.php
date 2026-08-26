@php
    use Illuminate\Support\Str;
@endphp
<div class="acceptance-terms-page"
    x-data="{
        statementContent: @entangle('statementText').defer,
        contractContent: @entangle('contractText').defer,
    }">
    <x-show-loading />

    <style>
        .acceptance-terms-page .editor-card {
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 1rem;
            box-shadow: 0 16px 32px rgba(15, 23, 42, 0.08);
            padding: 1.25rem 1.5rem;
            margin-bottom: 1.5rem;
        }

        .acceptance-terms-page .ql-editor {
            min-height: 160px;
            font-size: 0.95rem;
        }

        .acceptance-terms-page .term-box {
            display: grid;
            grid-template-columns: 52px 1fr;
            gap: 12px;
            padding: 16px;
            border-radius: 12px;
            border: 1px dashed rgba(14, 165, 164, 0.5);
            background: rgba(14, 165, 164, 0.08);
        }

        .acceptance-terms-page .term-icon {
            width: 52px;
            height: 52px;
            border-radius: 12px;
            background: rgba(14, 165, 164, 0.2);
            display: grid;
            place-items: center;
            color: #0f766e;
            font-size: 24px;
        }

        .acceptance-terms-page .term-quote {
            display: grid;
            grid-template-columns: 44px 1fr;
            gap: 12px;
            padding: 14px;
            border-radius: 12px;
            border: 1px dashed rgba(14, 165, 164, 0.65);
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.95), rgba(14, 165, 164, 0.1));
            box-shadow: inset 0 0 0 1px rgba(14, 165, 164, 0.15);
            margin-top: 0.75rem;
        }

        .acceptance-terms-page .term-quote-icon {
            width: 44px;
            height: 44px;
            border-radius: 10px;
            background: rgba(14, 165, 164, 0.2);
            display: grid;
            place-items: center;
            color: #0f766e;
            font-size: 20px;
        }

        .acceptance-terms-page .history-box {
            border-top: 1px solid #e5e7eb;
            padding-top: 0.75rem;
        }

        .acceptance-terms-page .history-list {
            max-height: 220px;
            overflow-y: auto;
            margin: 0;
        }

        .acceptance-terms-page .history-item {
            padding: 0.5rem 0;
            border-bottom: 1px dashed #e5e7eb;
        }

        .acceptance-terms-page .history-item:last-child {
            border-bottom: none;
        }
    </style>

    <div class="container-fluid">
        <div class="d-flex align-items-center justify-content-between mb-3">
            <div>
                <h4 class="mb-0">Termo de Aceite do Informe de Obra</h4>
                <div class="text-muted small">Declaração de responsabilidade exibida ao concluir/reenviar o Informe de Obra.</div>
            </div>
        </div>

        <div class="alert alert-info">
            Editar aqui só afeta <strong>novos</strong> informes a partir de agora. Informes já enviados mantêm gravado o texto exato que o usuário aceitou no momento do envio.
        </div>

        <div class="row g-4">
            <div class="col-12 col-xl-6">
                <div class="editor-card">
                    <h6 class="fw-bold mb-2">Declaração de responsabilidade</h6>
                    <div wire:ignore
                        x-data="{ editor: null }"
                        x-init="
                            editor = new Quill($refs.statementEditor, {
                                theme: 'snow',
                                modules: { toolbar: [['bold', 'italic', 'underline'], [{ list: 'ordered' }, { list: 'bullet' }], ['link'], ['clean']] },
                            });
                            editor.root.innerHTML = statementContent;
                            editor.on('text-change', () => { statementContent = editor.root.innerHTML; });
                            $watch('statementContent', value => { if (editor.root.innerHTML !== value) editor.root.innerHTML = value; });
                        ">
                        <div x-ref="statementEditor"></div>
                    </div>
                    @error('statementText')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror

                    @if ($statementHistory->isNotEmpty())
                        <div class="history-box mt-3">
                            <div class="small fw-bold text-muted mb-1">Histórico de versões</div>
                            <ul class="list-unstyled history-list">
                                @foreach ($statementHistory as $version)
                                    <li class="history-item">
                                        <div class="d-flex justify-content-between align-items-start gap-2">
                                            <div class="small">
                                                <span class="fw-semibold">{{ $version->changedBy?->name ?? 'Sistema' }}</span>
                                                <span class="text-muted">— {{ $version->created_at?->format('d/m/Y H:i') }}</span>
                                                <div class="text-muted">{{ Str::limit(strip_tags((string) $version->value), 100) }}</div>
                                            </div>
                                            <button type="button" class="btn btn-sm btn-outline-secondary flex-shrink-0"
                                                wire:click="restoreVersion('statementText', {{ $version->id }})">
                                                Restaurar
                                            </button>
                                        </div>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                </div>

                <div class="editor-card">
                    <h6 class="fw-bold mb-2">Citação contratual</h6>
                    <div wire:ignore
                        x-data="{ editor: null }"
                        x-init="
                            editor = new Quill($refs.contractEditor, {
                                theme: 'snow',
                                modules: { toolbar: [['bold', 'italic', 'underline'], [{ list: 'ordered' }, { list: 'bullet' }], ['link'], ['clean']] },
                            });
                            editor.root.innerHTML = contractContent;
                            editor.on('text-change', () => { contractContent = editor.root.innerHTML; });
                            $watch('contractContent', value => { if (editor.root.innerHTML !== value) editor.root.innerHTML = value; });
                        ">
                        <div x-ref="contractEditor"></div>
                    </div>
                    @error('contractText')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror

                    @if ($contractHistory->isNotEmpty())
                        <div class="history-box mt-3">
                            <div class="small fw-bold text-muted mb-1">Histórico de versões</div>
                            <ul class="list-unstyled history-list">
                                @foreach ($contractHistory as $version)
                                    <li class="history-item">
                                        <div class="d-flex justify-content-between align-items-start gap-2">
                                            <div class="small">
                                                <span class="fw-semibold">{{ $version->changedBy?->name ?? 'Sistema' }}</span>
                                                <span class="text-muted">— {{ $version->created_at?->format('d/m/Y H:i') }}</span>
                                                <div class="text-muted">{{ Str::limit(strip_tags((string) $version->value), 100) }}</div>
                                            </div>
                                            <button type="button" class="btn btn-sm btn-outline-secondary flex-shrink-0"
                                                wire:click="restoreVersion('contractText', {{ $version->id }})">
                                                Restaurar
                                            </button>
                                        </div>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                </div>

                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-primary" wire:click="save">
                        <span wire:loading.remove wire:target="save">Salvar</span>
                        <span wire:loading wire:target="save">Salvando...</span>
                    </button>
                    <button type="button" class="btn btn-outline-secondary" wire:click="resetToDefault">
                        Restaurar texto padrão
                    </button>
                </div>
            </div>

            <div class="col-12 col-xl-6">
                <h6 class="fw-bold text-muted mb-2">Pré-visualização ao vivo</h6>
                <div class="term-box">
                    <div class="term-icon">
                        <i class="ri-shield-check-line"></i>
                    </div>
                    <div>
                        <p class="mb-2 fw-semibold">Declaração de responsabilidade</p>
                        <div class="text-muted mb-2" x-html="statementContent"></div>
                        <div class="term-quote mt-3">
                            <div class="term-quote-icon">
                                <i class="ri-double-quotes-l"></i>
                            </div>
                            <div>
                                <p class="small text-uppercase fw-semibold mb-2">Citação contratual</p>
                                <div class="text-muted mb-0" x-html="contractContent"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
