@props(['user', 'short' => true])

@php
    $name = $user?->name ?? '----';

    if ($short && $name !== '----') {
        $parts = explode(' ', trim($name));
        $name = trim(($parts[0] ?? '') . ' ' . (count($parts) > 1 ? end($parts) : ''));
    }
@endphp

<span @if($user?->trashed()) class="text-muted text-decoration-line-through" @endif>{{ $name }}</span>
@if($user?->trashed())
    <span class="badge text-bg-secondary ms-1" title="Usuário não faz mais parte dos usuários ativos">Inativo</span>
@endif
