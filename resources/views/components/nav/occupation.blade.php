@props([
    'rows',
    'companies' => [],
    'online'    => [],
    'avatars'   => [],
    'title'     => 'Ocupação',
])

{{-- Painel de ocupação do rodapé do menu lateral padrão (docs/PADRAO_MENU_LATERAL.md): quem está com quanto da fila da atividade.
     Use dentro de um Livewire com `company_s` e o evento `filterUser` (clique na pessoa filtra a pilha). Estilo: `.snav-occ` em _sidebar.scss.
     rows: user_id (ou id), name, registros, notes, ov, deleted_at · online: ids com o watchdog ativo · avatars: id => URL (User::avatar_url);
     sem foto ou com falha ao carregar, ficam as iniciais. --}}
@php
    $total  = (int) $rows->sum('registros');
    $base   = max(1, $total);
    $top    = max(1, (int) ($rows->max('registros') ?? 1));
    $onlineIds = collect($online)->map(fn ($id) => (string) $id)->all();
    $uid = fn ($r) => (string) ($r->user_id ?? $r->id);
    $onlineCount = $rows->filter(fn ($r) => in_array($uid($r), $onlineIds, true))->count();
@endphp

<details class="snav-occ" data-snav-persist="occupation" wire:ignore.self open>
    <summary class="snav-occ-head">
        <span class="snav-occ-ico"><i class="ri-pulse-line" aria-hidden="true"></i></span>
        <span class="snav-occ-title">{{ $title }}</span>
        <span class="snav-occ-total" title="Atividades em aberto com a equipe">{{ $total }}</span>
        <i class="ri-arrow-down-s-line snav-occ-chev" aria-hidden="true"></i>
    </summary>

    <div class="snav-occ-body">
        <div class="snav-occ-meta">
            <span><i class="ri-user-line" aria-hidden="true"></i> {{ $rows->count() }} {{ $rows->count() === 1 ? 'pessoa' : 'pessoas' }}</span>
            <span class="snav-occ-online"><i class="snav-occ-dot is-on" aria-hidden="true"></i> {{ $onlineCount }} online</span>
        </div>

        @if (count($companies) > 0)
            <select class="snav-occ-select" aria-label="Filtrar ocupação por empresa" wire:model="company_s">
                <option value="">Todas as empresas</option>
                @foreach ($companies as $company)
                    <option value="{{ $company->id }}">{{ explode(' ', $company->name)[0] }}</option>
                @endforeach
            </select>
        @endif

        <ol class="snav-occ-list" role="list">
            @foreach ($rows as $row)
                @php
                    $parts    = explode(' ', trim($row->name));
                    $first    = $parts[0] ?? '';
                    $last     = count($parts) > 1 ? end($parts) : '';
                    $initials = mb_strtoupper(mb_substr($first, 0, 1) . mb_substr($last, 0, 1));
                    $share    = (int) round($row->registros / $base * 100);
                    $barWidth = (int) round($row->registros / $top * 100);
                    $inactive = filled($row->deleted_at);
                    $userId   = $uid($row);
                    $isOnline = in_array($userId, $onlineIds, true);
                @endphp
                <li class="snav-occ-row {{ $loop->first ? 'is-top' : '' }} {{ $inactive ? 'is-inactive' : '' }}" wire:key="occ-{{ $userId }}">
                    <button type="button" class="snav-occ-btn" wire:click="$emit('filterUser', '{{ $userId }}')"
                        title="Filtrar a pilha por {{ trim($first . ' ' . $last) }} · {{ $row->registros }} em aberto ({{ $row->notes }} notas, {{ $row->ov }} OVs)">
                        <span class="snav-occ-rank">{{ $loop->iteration }}</span>
                        <span class="snav-occ-avatar">
                            <span class="snav-occ-initials">{{ $initials }}</span>
                            @if (!empty($avatars[$userId]))
                                <img class="snav-occ-img" src="{{ $avatars[$userId] }}" alt="" loading="lazy" decoding="async">
                            @endif
                            <i class="snav-occ-dot {{ $isOnline ? 'is-on' : 'is-off' }}" aria-label="{{ $isOnline ? 'online' : 'offline' }}"></i>
                        </span>
                        <span class="snav-occ-main">
                            <span class="snav-occ-line">
                                <span class="snav-occ-name">{{ trim($first . ' ' . $last) }}@if ($inactive) <em>inativo</em>@endif</span>
                                <span class="snav-occ-share">{{ $share }}%</span>
                            </span>
                            <span class="snav-occ-bar" aria-hidden="true"><span class="snav-occ-fill snav-occ-w-{{ $barWidth }}"></span></span>
                            <span class="snav-occ-line snav-occ-sub">
                                <strong>{{ $row->registros }}</strong>
                                <span><b>N</b> {{ $row->notes }} · <b>OV</b> {{ $row->ov }}</span>
                            </span>
                        </span>
                    </button>
                </li>
            @endforeach
        </ol>
    </div>
</details>
