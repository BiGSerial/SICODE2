@php
    use App\Helpers\FileIcon;

    // 1) Agrupar por serviço (maiúsculas) e ordenar alfabeticamente as chaves
    $grouped = $files->groupBy(fn($file) => mb_strtoupper($file->service->service ?? 'OUTROS'))->sortKeys();

    // 2) Garantir um valor inicial para $activeTab (primeira aba) se vier vazio
    if ($files->isNotEmpty() && empty($activeTab)) {
        $activeTab = 'fm-tab-0';
    }
@endphp

{{-- Contêiner principal para o gerenciador de arquivos com estilos modernos --}}
<div class="file-manager-wrapper">
    @if ($files->isNotEmpty())
        {{-- Indicador de carregamento do Livewire --}}
        <x-show-loading />

        {{-- Navegação por serviços (abas) --}}
        {{-- wire:ignore impede o Livewire de reidratar a barra que o Bootstrap controla --}}
        <ul class="fm-tab-nav" id="fileTabs" role="tablist" wire:ignore>
            @php $__loopIndex = 0; @endphp
            @foreach ($grouped as $serviceName => $group)
                @php
                    $tabId = 'fm-tab-' . $__loopIndex; // id do botão
                    $paneId = 'fm-pane-' . $__loopIndex; // id do conteúdo
                    $isActive = $activeTab === $tabId;
                @endphp
                <li class="fm-tab-item" role="presentation">
                    <button class="fm-tab-link {{ $isActive ? 'active' : '' }}" id="{{ $tabId }}" type="button"
                        role="tab" data-bs-toggle="tab" data-bs-target="#{{ $paneId }}"
                        aria-controls="{{ $paneId }}" aria-selected="{{ $isActive ? 'true' : 'false' }}">
                        {{ strtoupper($serviceName) }}
                    </button>
                </li>
                @php $__loopIndex++; @endphp
            @endforeach
        </ul>

        {{-- Conteúdo das abas com a grade de arquivos --}}
        <div class="fm-tab-content tab-content" id="fileTabContent">
            @php $__loopIndex = 0; @endphp
            @foreach ($grouped as $serviceName => $group)
                @php
                    $tabId = 'fm-tab-' . $__loopIndex;
                    $paneId = 'fm-pane-' . $__loopIndex;
                    $isActive = $activeTab === $tabId;
                @endphp

                <div class="fm-tab-pane tab-pane fade {{ $isActive ? 'show active' : '' }}" id="{{ $paneId }}"
                    role="tabpanel" aria-labelledby="{{ $tabId }}">
                    {{-- Grade responsiva para exibir os cartões de arquivos --}}
                    <div class="fm-file-grid">
                        @foreach ($group->sortBy('file_name')->values() as $fileIndex => $file)
                            <div class="fm-file-card" wire:click="downloadFile({{ $file->id }})"
                                style="--fm-card-delay: {{ number_format($fileIndex * 0.05, 2) }}s;"
                                title="{{ $file->file_name }}">
                                <i class="{{ FileIcon::getIcon($file->ext)->icon }} fm-file-card-icon"></i>
                                <div class="fm-file-card-name">{{ $file->file_name }}</div>
                            </div>
                        @endforeach
                    </div>
                </div>

                @php $__loopIndex++; @endphp
            @endforeach
        </div>
    @else
        {{-- Estado vazio --}}
        <div class="fm-empty-state" role="alert">
            <h4>Sem arquivos disponíveis.</h4>
            <p>Não há arquivos disponíveis para download no momento.</p>
        </div>
    @endif
</div>

{{-- CSS (carregado uma única vez) --}}
@pushOnce('css')
    <style>
        /* Contêiner principal */
        .file-manager-wrapper {
            font-family: 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background-color: #f8f9fa;
            padding: 1rem;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        }

        /* Nav / filtros (abas) */
        .fm-tab-nav {
            display: flex;
            flex-wrap: wrap;
            gap: .5rem;
            margin-bottom: 1rem;
            padding-bottom: .5rem;
            border-bottom: 1px solid #e0e0e0;
            list-style: none;
            padding-left: 0;
        }

        .fm-tab-link {
            display: block;
            padding: .6rem 1.2rem;
            border-radius: 20px;
            background: #e9ecef;
            color: #495057;
            text-decoration: none;
            font-weight: 500;
            transition: all .2s ease-in-out;
            border: none;
            cursor: pointer;
            outline: none;
            white-space: nowrap;
        }

        .fm-tab-link:hover {
            background: #dee2e6;
            color: #212529;
        }

        .fm-tab-link.active {
            background: #0d6efd;
            color: #fff;
            box-shadow: 0 2px 8px rgba(13, 110, 253, .2);
        }

        /* Conteúdo */
        .fm-tab-pane {
            padding-top: 1rem;
            animation: fmFadeIn .4s ease-out forwards;
        }

        @keyframes fmFadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Grid de arquivos */
        .fm-file-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
            gap: 1rem;
            padding: .5rem 0;
        }

        .fm-file-card {
            background: #fff;
            border: 1px solid #e0e0e0;
            border-radius: 10px;
            padding: 1rem;
            text-align: center;
            cursor: pointer;
            transition: all .25s ease-in-out;
            box-shadow: 0 2px 8px rgba(0, 0, 0, .04);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 120px;
            position: relative;
            overflow: hidden;
            opacity: 0;
            transform: translateY(15px);
            animation: fmCardRise .4s ease-out forwards;
        }

        .fm-file-grid>.fm-file-card {
            animation-delay: var(--fm-card-delay, 0s);
        }

        @keyframes fmCardRise {
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .fm-file-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 16px rgba(0, 0, 0, .1);
            background: #f0f8ff;
        }

        .fm-file-card-icon {
            font-size: 2.5rem;
            color: #28a745;
            margin-bottom: .5rem;
        }

        .fm-file-card-name {
            font-size: .85rem;
            color: #343a40;
            overflow-wrap: break-word;
            word-break: break-word;
            hyphens: auto;
            line-height: 1.3;
            max-height: 3.9em;
            overflow: hidden;
            text-overflow: ellipsis;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
        }

        /* Estado vazio */
        .fm-empty-state {
            text-align: center;
            padding: 2rem;
            background: #fff;
            border-radius: 8px;
            border: 1px dashed #ced4da;
            color: #6c757d;
            margin-top: 1rem;
        }

        .fm-empty-state h4 {
            color: #495057;
            margin-bottom: .5rem;
        }

        /* Responsividade */
        @media (max-width:768px) {
            .fm-tab-nav {
                justify-content: center;
                gap: .4rem;
            }

            .fm-tab-link {
                padding: .5rem 1rem;
                font-size: .9rem;
            }

            .fm-file-grid {
                grid-template-columns: repeat(auto-fill, minmax(90px, 1fr));
                gap: .75rem;
            }

            .fm-file-card {
                padding: .75rem;
                min-height: 90px;
            }

            .fm-file-card-icon {
                font-size: 2rem;
                margin-bottom: .3rem;
            }

            .fm-file-card-name {
                font-size: .65rem;
                line-height: 1.2;
                max-height: 3.6em;
            }
        }

        @media (max-width:480px) {
            .fm-file-grid {
                grid-template-columns: repeat(auto-fill, minmax(80px, 1fr));
                gap: .5rem;
            }

            .fm-file-card {
                padding: .5rem;
                min-height: 80px;
            }

            .fm-file-card-icon {
                font-size: 1.8rem;
            }
        }
    </style>
@endpushOnce

{{-- Scripts (carregado uma única vez) --}}
@pushOnce('scripts')
    <script>
        document.addEventListener('livewire:load', () => {
            const tabsNav = document.getElementById('fileTabs');

            // 1) Ao mostrar uma aba (evento de Bootstrap), persistir no Livewire
            if (tabsNav) {
                tabsNav.addEventListener('shown.bs.tab', (e) => {
                    const target = e.target?.getAttribute('data-bs-target'); // ex: "#fm-pane-2"
                    if (!target) return;
                    const btnId = e.target?.id; // ex: "fm-tab-2"
                    if (btnId && window.Livewire) {
                        @this.set('activeTab', btnId);
                    }
                });
            }

            // 2) Após qualquer re-render do Livewire, reabrir a aba correta
            if (window.Livewire && Livewire.hook) {
                Livewire.hook('message.processed', () => {
                    try {
                        const activeId = @this.get('activeTab'); // ex: "fm-tab-2"
                        if (!activeId) return;

                        const btn = document.getElementById(activeId);
                        if (!btn) return;

                        const tab = bootstrap.Tab.getOrCreateInstance(btn);
                        tab.show();
                    } catch (err) {
                        // silencioso
                    }
                });
            }

            // 3) Inicialização: se houver um activeTab vindo do servidor, garantir visual
            try {
                const activeId = @this.get('activeTab');
                if (activeId) {
                    const btn = document.getElementById(activeId);
                    if (btn) bootstrap.Tab.getOrCreateInstance(btn).show();
                } else {
                    // fallback: abrir primeira aba se nada definido
                    const firstBtn = tabsNav?.querySelector('.fm-tab-link');
                    if (firstBtn) bootstrap.Tab.getOrCreateInstance(firstBtn).show();
                }
            } catch (_) {}
        });
    </script>
@endpushOnce
