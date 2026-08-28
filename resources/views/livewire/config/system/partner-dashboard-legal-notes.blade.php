@php
    use Illuminate\Support\Str;
@endphp
<div class="legal-notes-page"
    x-data="{
        adsDueContent: @entangle('adsDueText').defer,
        adsOverdueContent: @entangle('adsOverdueText').defer,
        valuesDisclaimerContent: @entangle('valuesDisclaimerText').defer,
    }">
    <x-show-loading />

    <style>
        .legal-notes-page .editor-card {
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 1rem;
            box-shadow: 0 16px 32px rgba(15, 23, 42, 0.08);
            padding: 1.25rem 1.5rem;
            margin-bottom: 1.5rem;
        }

        .legal-notes-page .ql-editor {
            min-height: 120px;
            font-size: 0.95rem;
        }

        .legal-notes-page .pd-legal-note {
            margin: 0;
            padding: 0.85rem 1rem;
            border: 1px solid #cbd5e1;
            border-left: 4px solid #0f766e;
            border-radius: 10px;
            background: linear-gradient(180deg, #f8fafc 0%, #f1f5f9 100%);
        }

        .legal-notes-page .pd-legal-note-title {
            font-size: 0.76rem;
            font-weight: 700;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            color: #0f172a;
            margin-bottom: 0.35rem;
        }

        .legal-notes-page .pd-legal-note p {
            margin: 0;
            color: #334155;
            font-size: 0.8rem;
            line-height: 1.35;
        }

        .legal-notes-page .pd-legal-note p + p {
            margin-top: 0.5rem;
        }

        .legal-notes-page .preview-block + .preview-block {
            margin-top: 1.25rem;
        }

        .legal-notes-page .pd-disclaimer {
            border: 1px solid rgba(180, 83, 9, 0.35);
            border-left: 5px solid #b45309;
            border-radius: 12px;
            background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%);
            box-shadow: 0 10px 24px rgba(180, 83, 9, 0.12);
            color: #78350f;
            font-size: 0.82rem;
            line-height: 1.35;
            padding: 0.85rem 1rem;
        }

        .legal-notes-page .history-box {
            border-top: 1px solid #e5e7eb;
            padding-top: 0.75rem;
        }

        .legal-notes-page .history-list {
            max-height: 220px;
            overflow-y: auto;
            margin: 0;
        }

        .legal-notes-page .history-item {
            padding: 0.5rem 0;
            border-bottom: 1px dashed #e5e7eb;
        }

        .legal-notes-page .history-item:last-child {
            border-bottom: none;
        }
    </style>

    <div class="container-fluid">
        <div class="d-flex align-items-center justify-content-between mb-3">
            <div>
                <h4 class="mb-0">Avisos Legais - Painel do Parceiro</h4>
                <div class="text-muted small">Textos exibidos na tela inicial do parceiro: "Base contratual" dos painéis de ADS a vencer/em atraso, e o aviso de valores meramente informativos.</div>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-12 col-xl-6">
                <div class="editor-card">
                    <h6 class="fw-bold mb-2">Entregas de ADS a vencer</h6>
                    <div wire:ignore
                        x-data="{ editor: null }"
                        x-init="
                            editor = new Quill($refs.adsDueEditor, {
                                theme: 'snow',
                                modules: { toolbar: [['bold', 'italic', 'underline'], [{ list: 'ordered' }, { list: 'bullet' }], ['link'], ['clean']] },
                            });
                            editor.root.innerHTML = adsDueContent;
                            editor.on('text-change', () => { adsDueContent = editor.root.innerHTML; });
                            $watch('adsDueContent', value => { if (editor.root.innerHTML !== value) editor.root.innerHTML = value; });
                        ">
                        <div x-ref="adsDueEditor"></div>
                    </div>
                    @error('adsDueText')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror

                    @if ($adsDueHistory->isNotEmpty())
                        <div class="history-box mt-3">
                            <div class="small fw-bold text-muted mb-1">Histórico de versões</div>
                            <ul class="list-unstyled history-list">
                                @foreach ($adsDueHistory as $version)
                                    <li class="history-item">
                                        <div class="d-flex justify-content-between align-items-start gap-2">
                                            <div class="small">
                                                <span class="fw-semibold">{{ $version->changedBy?->name ?? 'Sistema' }}</span>
                                                <span class="text-muted">— {{ $version->created_at?->format('d/m/Y H:i') }}</span>
                                                <div class="text-muted">{{ Str::limit(strip_tags((string) $version->value), 100) }}</div>
                                            </div>
                                            <button type="button" class="btn btn-sm btn-outline-secondary flex-shrink-0"
                                                wire:click="restoreVersion('adsDueText', {{ $version->id }})">
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
                    <h6 class="fw-bold mb-2">Entregas de ADS em atraso</h6>
                    <div wire:ignore
                        x-data="{ editor: null }"
                        x-init="
                            editor = new Quill($refs.adsOverdueEditor, {
                                theme: 'snow',
                                modules: { toolbar: [['bold', 'italic', 'underline'], [{ list: 'ordered' }, { list: 'bullet' }], ['link'], ['clean']] },
                            });
                            editor.root.innerHTML = adsOverdueContent;
                            editor.on('text-change', () => { adsOverdueContent = editor.root.innerHTML; });
                            $watch('adsOverdueContent', value => { if (editor.root.innerHTML !== value) editor.root.innerHTML = value; });
                        ">
                        <div x-ref="adsOverdueEditor"></div>
                    </div>
                    @error('adsOverdueText')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror

                    @if ($adsOverdueHistory->isNotEmpty())
                        <div class="history-box mt-3">
                            <div class="small fw-bold text-muted mb-1">Histórico de versões</div>
                            <ul class="list-unstyled history-list">
                                @foreach ($adsOverdueHistory as $version)
                                    <li class="history-item">
                                        <div class="d-flex justify-content-between align-items-start gap-2">
                                            <div class="small">
                                                <span class="fw-semibold">{{ $version->changedBy?->name ?? 'Sistema' }}</span>
                                                <span class="text-muted">— {{ $version->created_at?->format('d/m/Y H:i') }}</span>
                                                <div class="text-muted">{{ Str::limit(strip_tags((string) $version->value), 100) }}</div>
                                            </div>
                                            <button type="button" class="btn btn-sm btn-outline-secondary flex-shrink-0"
                                                wire:click="restoreVersion('adsOverdueText', {{ $version->id }})">
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
                    <h6 class="fw-bold mb-2">Valores meramente informativos (Informes)</h6>
                    <div wire:ignore
                        x-data="{ editor: null }"
                        x-init="
                            editor = new Quill($refs.valuesDisclaimerEditor, {
                                theme: 'snow',
                                modules: { toolbar: [['bold', 'italic', 'underline'], [{ list: 'ordered' }, { list: 'bullet' }], ['link'], ['clean']] },
                            });
                            editor.root.innerHTML = valuesDisclaimerContent;
                            editor.on('text-change', () => { valuesDisclaimerContent = editor.root.innerHTML; });
                            $watch('valuesDisclaimerContent', value => { if (editor.root.innerHTML !== value) editor.root.innerHTML = value; });
                        ">
                        <div x-ref="valuesDisclaimerEditor"></div>
                    </div>
                    @error('valuesDisclaimerText')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror

                    @if ($valuesDisclaimerHistory->isNotEmpty())
                        <div class="history-box mt-3">
                            <div class="small fw-bold text-muted mb-1">Histórico de versões</div>
                            <ul class="list-unstyled history-list">
                                @foreach ($valuesDisclaimerHistory as $version)
                                    <li class="history-item">
                                        <div class="d-flex justify-content-between align-items-start gap-2">
                                            <div class="small">
                                                <span class="fw-semibold">{{ $version->changedBy?->name ?? 'Sistema' }}</span>
                                                <span class="text-muted">— {{ $version->created_at?->format('d/m/Y H:i') }}</span>
                                                <div class="text-muted">{{ Str::limit(strip_tags((string) $version->value), 100) }}</div>
                                            </div>
                                            <button type="button" class="btn btn-sm btn-outline-secondary flex-shrink-0"
                                                wire:click="restoreVersion('valuesDisclaimerText', {{ $version->id }})">
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

                <div class="preview-block">
                    <div class="small text-muted mb-1">Entregas de ADS a vencer</div>
                    <div class="pd-legal-note">
                        <div class="pd-legal-note-title">Base contratual</div>
                        <div x-html="adsDueContent"></div>
                    </div>
                </div>

                <div class="preview-block">
                    <div class="small text-muted mb-1">Entregas de ADS em atraso</div>
                    <div class="pd-legal-note">
                        <div class="pd-legal-note-title">Base contratual</div>
                        <div x-html="adsOverdueContent"></div>
                    </div>
                </div>

                <div class="preview-block">
                    <div class="small text-muted mb-1">Cabeçalho da seção "Informes"</div>
                    <div class="pd-disclaimer" x-html="valuesDisclaimerContent"></div>
                </div>
            </div>
        </div>
    </div>
</div>
