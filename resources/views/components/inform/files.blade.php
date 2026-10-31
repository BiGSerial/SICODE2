@props(['files'])

@php
    $images = ['jpg', 'jpeg', 'png', 'webp', 'gif', 'bmp'];
@endphp

<div class="inform-files">
    @foreach ($files as $file)
        @php $isImage = in_array(mb_strtolower((string) $file->ext), $images, true); @endphp
        <div class="inform-file">
            <div class="thumb">
                @if ($isImage)
                    <img src="{{ route('files.preview', ['file' => $file->id, 'thumbnail' => 1]) }}" alt="{{ $file->file_name }}" loading="lazy"
                        onerror="this.remove()">
                @else
                    <i class="{{ \App\Helpers\FileIcon::getIcon($file->ext)->icon }}"></i>
                @endif
            </div>
            <div class="meta">
                <strong>{{ $file->file_name }}</strong>
                <small>{{ $file->Service?->service ? mb_strtoupper($file->Service->service) . ' · ' : '' }}{{ mb_strtoupper((string) $file->ext) }}@if ($file->User) · {{ $file->User->name }}@endif</small>
            </div>
            <div class="actions">
                @if ($isImage)
                    <a href="{{ route('files.preview', ['file' => $file->id]) }}" target="_blank" rel="noopener" title="Abrir"><i class="ri-eye-line"></i></a>
                @endif
                <a href="{{ route('files.download', ['file' => $file->id]) }}" title="Baixar"><i class="ri-download-2-line"></i></a>
            </div>
        </div>
    @endforeach
</div>
