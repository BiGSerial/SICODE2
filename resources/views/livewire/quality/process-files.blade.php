<div>
    @php use App\Support\QualityUi; @endphp
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div class="d-flex flex-wrap gap-1">
            <button type="button" wire:click="setKind('todos')" class="btn btn-sm {{ $kind === 'todos' ? 'btn-primary' : 'btn-outline-primary' }}">Todos <span class="badge text-bg-light">{{ $all }}</span></button>
            @foreach ($kinds as $key => $label)
                @continue(empty($counts[$key]))
                <button type="button" wire:click="setKind('{{ $key }}')" class="btn btn-sm {{ $kind === $key ? 'btn-primary' : 'btn-outline-primary' }}">{{ $label }} <span class="badge text-bg-light">{{ $counts[$key] }}</span></button>
            @endforeach
        </div>
        <div class="d-flex gap-2 align-items-center">
            <div class="input-group input-group-sm" style="width: 220px;"><span class="input-group-text"><i class="ri-search-line"></i></span><input type="search" wire:model.debounce.400ms="search" class="form-control" placeholder="Buscar arquivo"></div>
            @if ($all)<a href="{{ route('quality.process.files.zip', $process) }}" class="btn btn-sm btn-success"><i class="ri-download-2-line"></i> Baixar tudo (.zip)</a>@endif
        </div>
    </div>

    @if ($items->isEmpty())
        <div class="ob-empty"><i class="ri-gallery-line"></i><div class="fw-bold">{{ $all ? 'Nenhum arquivo neste filtro.' : 'Nenhum arquivo enviado ainda.' }}</div><div class="small">Os arquivos anexados pelo usuário em cada rodada aparecem aqui para download.</div></div>
    @else
        <div class="gal-grid">
            @foreach ($items as $item)
                @php
                    $file    = $item->file;
                    $isImage = $item->kind['key'] === 'image';
                    $isPdf   = $item->kind['key'] === 'pdf';
                    $thumb   = $isImage ? route('quality.process.file.preview', [$process, $file, 'thumbnail' => 1]) : null;
                    $view    = ($isImage || $isPdf) ? route('quality.process.file.preview', [$process, $file]) : null;
                    $uploader = $file->User?->name;
                @endphp
                <div class="gal-card" wire:key="file-{{ $file->id }}">
                    <div class="gal-thumb" @if ($view) data-lightbox="{{ $view }}" data-kind="{{ $item->kind['key'] }}" data-title="{{ $item->name }}" style="cursor: zoom-in;" @endif>
                        @if ($thumb)
                            <img src="{{ $thumb }}" loading="lazy" alt="{{ $item->name }}">
                        @else
                            <div class="gal-icon" style="color: {{ $item->kind['tone'] }}; background: {{ $item->kind['tone'] }}14;"><i class="{{ $item->kind['icon'] }}"></i><b>{{ mb_strtoupper($file->ext ?: '—') }}</b></div>
                        @endif
                        @if ($item->tag)<span class="gal-tag">{{ $item->tag }}</span>@endif
                    </div>
                    <div class="gal-body">
                        <div class="gal-name" title="{{ $item->name }}">{{ $item->name }}</div>
                        <div class="gal-meta">{{ QualityUi::bytes($file->size) }} · {{ $file->created_at?->format('d/m/Y H:i') }}</div>
                        <div class="d-flex align-items-center justify-content-between mt-2">
                            <div class="d-flex align-items-center gap-1 small text-muted min-w-0">@if ($uploader)<span class="chat-avatar" style="width: 22px; height: 22px; flex-basis: 22px; font-size: .6rem; background: {{ QualityUi::avatarTone($uploader) }}">{{ QualityUi::initials($uploader) }}</span><span class="text-truncate" style="max-width: 110px;">{{ $uploader }}</span>@endif</div>
                            <a href="{{ route('quality.process.file', [$process, $file]) }}" class="btn btn-sm btn-outline-primary" title="Baixar"><i class="ri-download-2-line"></i></a>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
        @if ($items->count() < $total)
            <div class="text-center mt-3"><button type="button" wire:click="loadMore" class="btn btn-outline-primary btn-sm">Carregar mais ({{ $total - $items->count() }})</button></div>
        @endif
    @endif
</div>
