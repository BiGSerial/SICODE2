@props(['zip' => false])
@php
    use App\Services\Files\AttachmentPolicy;
@endphp
<div {{ $attributes->class(['upload-rules small text-muted']) }}>
    <div><i class="ri-file-list-3-line"></i> <strong>Formatos aceitos:</strong> {{ AttachmentPolicy::describe($zip) }}</div>
    <div><i class="ri-scales-line"></i> <strong>Limites:</strong> até {{ AttachmentPolicy::MAX_FILE_MB }} MB por arquivo e {{ AttachmentPolicy::MAX_BATCH_MB }} MB por envio.</div>
</div>
