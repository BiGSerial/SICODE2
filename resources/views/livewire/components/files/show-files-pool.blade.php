@php
    use App\Helpers\FileIcon;

    // Agrupar e ordenar alfabeticamente (corrigido para suportar acentuação e consistência)
    $grouped = $files->groupBy(fn($file) => mb_strtoupper($file->service->service ?? 'OUTROS'))->sortKeys();
@endphp

@if ($files->isNotEmpty())
    <div>
        <x-show-loading />
        <ul class="nav nav-tabs mb-2" id="fileTabs" role="tablist">
            @foreach ($grouped as $serviceName => $group)
                @php $tabId = 'tab-' . $loop->index; @endphp
                <li class="nav-item" role="presentation">
                    <button class="nav-link {{ $activeTab === $tabId ? 'active' : '' }}" id="{{ $tabId }}"
                        data-bs-toggle="tab" data-bs-target="#pane-{{ $loop->index }}" type="button" role="tab"
                        aria-controls="pane-{{ $loop->index }}"
                        aria-selected="{{ $activeTab === $tabId ? 'true' : 'false' }}"
                        onclick="Livewire.emit('setActiveTab', '{{ $tabId }}')">
                        {{ strtoupper($serviceName) }}
                    </button>
                </li>
            @endforeach
        </ul>

        <div class="tab-content" id="fileTabContent">
            @foreach ($grouped as $serviceName => $group)
                @php $tabId = 'tab-' . $loop->index; @endphp
                <div class="tab-pane fade {{ $activeTab === $tabId ? 'show active' : '' }}"
                    id="pane-{{ $loop->index }}" role="tabpanel" aria-labelledby="{{ $tabId }}">
                    <div class="file-container">
                        @foreach ($group->sortBy('file_name')->values() as $fileIndex => $file)
                            <div class="file-box" wire:click="downloadFile({{ $file->id }})"
                                style="animation-delay: {{ number_format($fileIndex * 0.05, 2) }}s;">
                                <i class="{{ FileIcon::getIcon($file->ext)->icon }} file-icon"></i>
                                <div class="file-name">{{ $file->file_name }}</div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    </div>
@else
    <div class="text-center mt-2 py-2" role="alert">
        <h4>Sem arquivos disponíveis.</h4>
        <p>Não há arquivos disponíveis para download no momento.</p>
    </div>
@endif

@once
    <style>
        .file-container {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            padding: 6px;
        }

        .file-box {
            background-color: #fff;
            border: 1px solid #ccc;
            border-radius: 6px;
            padding: 6px 10px;
            min-width: 100px;
            max-width: 130px;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s ease;
            box-shadow: 1px 1px 3px rgba(0, 0, 0, 0.05);
            display: flex;
            flex-direction: column;
            opacity: 0;
            transform: translateY(10px);
            animation: fadeInUp 0.3s ease forwards;
        }

        .file-box:hover {
            background-color: #d4d4d4;
            transform: translateY(-2px);
            box-shadow: 1px 1px 5px rgba(0, 0, 0, 0.1);
        }

        @keyframes fadeInUp {
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .file-icon {
            font-size: 1.6rem;
            color: #026402;
        }

        .file-name {
            font-size: 0.75rem;
            margin-top: 4px;
            overflow-wrap: break-word;
            word-break: break-word;
            hyphens: auto;
            width: 100%;
        }
    </style>
@endonce
