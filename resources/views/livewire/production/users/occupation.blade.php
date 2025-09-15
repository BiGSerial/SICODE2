<div wire:poll>
    @php
        $total = max(1, $lists->sum('registros'));
        $pct = fn($qtd) => round(($qtd / $total) * 100, 2);
    @endphp

    @if ($lists->count())
        <div class="card border-0 shadow-sm sicode-sidebar-card">
            <div class="card-header py-1 edp-bg-marineblue-100 d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center gap-2">
                    <i class="ri-pulse-line edp-text-verde-dark fs-4"></i>
                    <h6 class="card-title mb-0 text-white">Ocupação</h6>
                </div>
                <span class="badge text-bg-dark">{{ $total }}</span>
            </div>

            <div class="card-body p-2">

                {{-- Filtro Empresa (compacto) --}}
                <div class="mb-2" wire:ignore.self>
                    <select class="form-select form-select-sm border-secondary" aria-label="Seleciona Empresa"
                        wire:model="company_s">
                        <option value="">Todas</option>
                        @if ($this->company_l->count())
                            @foreach ($this->company_l as $company)
                                <option value="{{ $company->id }}">{{ explode(' ', $company->name)[0] }}</option>
                            @endforeach
                        @endif
                    </select>
                </div>

                {{-- Lista compacta com rolagem --}}
                <ul class="list-group list-group-flush slim-scroll">
                    @foreach ($lists as $indice => $list)
                        @php
                            $parts = explode(' ', trim($list->name));
                            $first = $parts[0] ?? '';
                            $last = count($parts) > 1 ? end($parts) : '';
                            $display = trim($first . ' ' . $last);
                            $initials = mb_strtoupper(mb_substr($first, 0, 1) . mb_substr($last, 0, 1));
                            $perc = $pct($list->registros);
                            $rank = $indice + 1;
                            $barClass = $perc >= 50 ? 'bg-success' : ($perc >= 20 ? 'bg-warning' : 'bg-danger');
                        @endphp

                        <li class="list-group-item py-1 px-2 sidebar-row" role="button"
                            wire:click.defer="$emit('filterUser', '{{ $list->id }}')">

                            <div class="d-flex align-items-center gap-2">

                                {{-- Rank --}}
                                <span class="badge text-bg-dark rank-badge">{{ $rank }}</span>

                                {{-- Avatar + Nome --}}
                                <div class="position-relative flex-shrink-0">
                                    <div
                                        class="rounded-circle d-flex align-items-center justify-content-center fw-semibold text-white avatar-xxs">
                                        {{ $initials }}
                                    </div>

                                    {{-- Watchdog dot --}}
                                    @if (isset($list->Watchdog) && $list->Watchdog->watchdog)
                                        <span class="watchdog-dot pulse"></span>
                                    @else
                                        <span class="watchdog-dot off"></span>
                                    @endif
                                </div>

                                {{-- Conteúdo --}}
                                <div class="flex-grow-1 min-w-0">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="text-truncate small fw-semibold">{{ $display }}</span>
                                        <span class="small text-muted">{{ number_format($perc, 0, ',', '.') }}%</span>
                                    </div>

                                    <div class="progress progress-xxs mt-1">
                                        <div class="progress-bar {{ $barClass }}" role="progressbar"
                                            style="width: {{ $perc }}%;" aria-valuenow="{{ $perc }}"
                                            aria-valuemin="0" aria-valuemax="100"></div>
                                    </div>

                                    <div class="d-flex justify-content-between align-items-center">
                                        <small class="text-muted">{{ $list->registros }}</small>
                                        <small class="text-muted">N: {{ $list->notes }} • O:
                                            {{ $list->ov }}</small>
                                    </div>
                                </div>
                            </div>
                        </li>
                    @endforeach
                </ul>

            </div>
        </div>
    @endif
</div>

{{-- estilos compactos (pode ir em um .blade @push('styles') ou css do módulo) --}}
<style>
    .sicode-sidebar-card {
        max-width: 320px;
    }

    /* encaixa no sidebar */
    .sicode-sidebar-card .card-header {
        border: 0;
    }

    .slim-scroll {
        max-height: 60vh;
        overflow-y: auto;
    }

    .slim-scroll::-webkit-scrollbar {
        width: 6px;
    }

    .slim-scroll::-webkit-scrollbar-thumb {
        background: rgba(0, 0, 0, .15);
        border-radius: 6px;
    }

    .sidebar-row {
        border: 0;
        border-bottom: 1px solid rgba(0, 0, 0, .05);
        transition: background-color 0.2s ease, transform 0.15s ease;
        cursor: pointer;
    }

    .sidebar-row:hover {
        background-color: rgba(15, 122, 92, 0.08);

        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
        border-left: 5px solid rgba(32, 6, 146, 0.486);
        /* tom suave verde/EDP */
        transform: translateX(2px);
        /* leve deslocamento lateral */
    }

    .rank-badge {
        min-width: 22px;
        padding: 2px 6px;
        line-height: 1;
    }

    .avatar-xxs {
        width: 24px;
        height: 24px;
        font-size: 11px;
        background: linear-gradient(145deg, #0b4d3b, #0f7a5c);
        box-shadow: 0 2px 4px rgba(0, 0, 0, .12);
    }

    .progress-xxs {
        height: 4px;
        background-color: rgba(0, 0, 0, .06);
    }

    .watchdog-dot {
        position: absolute;
        top: -2px;
        right: -2px;
        width: 8px;
        height: 8px;
        border: 1px solid #fff;
        border-radius: 50%;
        background-color: #ef2727;
    }

    .watchdog-dot.off {
        background-color: #ef2727;
    }

    .watchdog-dot.pulse {
        background-color: #28FF52;
        box-shadow: 0 0 0 rgba(40, 255, 82, .6);
        animation: pulse 1.6s infinite;
    }

    @keyframes pulse {
        0% {
            box-shadow: 0 0 0 0 rgba(40, 255, 82, .6);
        }

        70% {
            box-shadow: 0 0 0 8px rgba(40, 255, 82, 0);
        }

        100% {
            box-shadow: 0 0 0 0 rgba(40, 255, 82, 0);
        }
    }

    /* densidade e tipografia menores */
    .sicode-sidebar-card .small,
    .sicode-sidebar-card small {
        font-size: 11px;
    }

    .sicode-sidebar-card .list-group-item {
        background: transparent;
    }
</style>
