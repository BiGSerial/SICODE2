@php
    use Carbon\Carbon;
    use App\Custom\Notestatus; // Certifique-se de que este namespace está correto e a classe existe
@endphp

@push('css')
    <style>
        /* Seus estilos CSS */
        .dashboard .info-card {
            padding-bottom: 10px;
        }

        .dashboard .info-card h6 {
            font-size: 28px;
            color: #012970;
            font-weight: 700;
            margin: 0;
            padding: 0;
        }

        .dashboard .card-icon {
            font-size: 32px;
            line-height: 0;
            width: 64px;
            height: 64px;
            flex-shrink: 0;
            flex-grow: 0;
        }

        .dashboard .sales-card .card-icon {
            color: #4154f1;
            background: #f6f6fe;
        }

        .dashboard .revenue-card .card-icon {
            color: #2eca6a;
            background: #e0f8e9;
        }

        .dashboard .customers-card .card-icon {
            color: #ff771d;
            background: #ffecdf;
        }

        /* Activity */
        .dashboard .activity {
            font-size: 14px;
        }

        .dashboard .activity .activity-item .activite-label {
            color: #888;
            position: relative;
            flex-shrink: 0;
            flex-grow: 0;
            min-width: 64px;
        }

        .dashboard .activity .activity-item .activite-label::before {
            content: "";
            position: absolute;
            right: -11px;
            width: 4px;
            top: 0;
            bottom: 0;
            background-color: #eceefe;
        }

        .dashboard .activity .activity-item .activity-badge {
            margin-top: 3px;
            z-index: 1;
            font-size: 11px;
            line-height: 0;
            border-radius: 50%;
            flex-shrink: 0;
            border: 3px solid #fff;
            flex-grow: 0;
        }

        .dashboard .activity .activity-item .activity-content {
            padding-left: 10px;
            padding-bottom: 20px;
        }

        .dashboard .activity .activity-item:first-child .activite-label::before {
            top: 5px;
        }

        .dashboard .activity .activity-item:last-child .activity-content {
            padding-bottom: 0;
        }

        /* News & Updates */
        .dashboard .news .post-item+.post-item {
            margin-top: 15px;
        }

        .dashboard .news img {
            width: 80px;
            float: left;
            border-radius: 5px;
        }

        .dashboard .news h4 {
            font-size: 15px;
            margin-left: 95px;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .dashboard .news h4 a {
            color: #012970;
            transition: 0.3s;
        }

        .dashboard .news h4 a:hover {
            color: #4154f1;
        }

        .dashboard .news p {
            font-size: 14px;
            color: #777777;
            margin-left: 95px;
        }

        /* Recent Sales */
        .dashboard .recent-sales {
            font-size: 14px;
        }

        .dashboard .recent-sales .table thead {
            background: #f6f6fe;
        }

        .dashboard .recent-sales .table thead th {
            border: 0;
        }

        .dashboard .recent-sales .dataTable-top {
            padding: 0 0 10px 0;
        }

        .dashboard .recent-sales .dataTable-bottom {
            padding: 10px 0 0 0;
        }

        /* Top Selling */
        .dashboard .top-selling {
            font-size: 14px;
        }

        .dashboard .top-selling .table thead {
            background: #f6f6fe;
        }

        .dashboard .top-selling .table thead th {
            border: 0;
        }

        .dashboard .top-selling .table tbody td {
            vertical-align: middle;
        }

        .dashboard .top-selling img {
            border-radius: 5px;
            max-width: 60px;
        }

        iconslist {
            display: grid;
            max-width: 100%;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 1.25rem;
            padding-top: 15px;
        }

        .iconslist .icon {
            background-color: #fff;
            border-radius: 0.25rem;
            text-align: center;
            color: #012970;
            padding: 15px 0;
        }

        .iconslist i {
            margin: 0.25rem;
            font-size: 2.5rem;
        }

        .iconslist .label {
            font-family: var(--bs-font-monospace);
            display: inline-block;
            width: 100%;
            overflow: hidden;
            padding: 0.25rem;
            font-size: 12px;
            text-overflow: ellipsis;
            white-space: nowrap;
            color: #666;
        }
    </style>
@endpush
<div>
    <x-show-loading />
    <div class="dashboard-header mb-4">
        <div class="row align-items-center">
            <div class="col-md-8">
                <div class="header-content">
                    <div class="d-flex align-items-center mb-2">
                        <div class="header-icon me-3">
                            <i class="ri-dashboard-2-line"></i>
                        </div>
                        <div>
                            <h1 class="header-title mb-0">Dashboard</h1>
                            <div class="header-subtitle">
                                Bem-vindo, <span class="fw-semibold">{{ auth()->user()->name }}</span>
                            </div>
                        </div>
                    </div>
                    <p class="header-description mb-0">
                        <i class="ri-information-line me-1"></i>
                        Acompanhe a produção e indicadores dos serviços em tempo real. Use os filtros para personalizar
                        sua visualização.
                    </p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="header-filters">
                    <div class="filters-container">
                        <div class="filter-group">
                            <label class="filter-label">
                                <i class="ri-hammer-line me-1 fw-thin"></i>
                                Serviço
                            </label>
                            <select wire:model="selectedService" class="filter-select">
                                <option value="">Todos os serviços</option>
                                @if ($services->isNotEmpty())
                                    @foreach ($services as $service)
                                        <option value="{{ $service->uuid }}">{{ $service->service }}</option>
                                    @endforeach

                                @endif
                            </select>
                        </div>
                        <div class="filter-group">
                            <label class="filter-label">
                                <i class="ri-calendar-line me-1"></i>
                                Período
                            </label>
                            <div class="row">
                                <div class="col-12 mb-2">
                                    <input type="month" wire:model="selectedMonth" class="filter-select"
                                        max="{{ date('Y-m') }}" />
                                </div>
                                <div class="col-6 mb-2">
                                    <input type="date" wire:model="dt_in" class="filter-select"
                                        max="{{ date('Y-m-d') }}" placeholder="Data Início" />
                                </div>
                                <div class="col-6">
                                    <input type="date" wire:model="dt_out" class="filter-select"
                                        max="{{ date('Y-m-d') }}" placeholder="Data Fim" />
                                </div>
                            </div>
                        </div>
                        <div class="filter-group">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="includeOpen"
                                    wire:model="includeOpen">
                                <label class="form-check-label text-white-50" for="includeOpen">
                                    Incluir em aberto
                                </label>
                            </div>
                            <div class="form-check mt-2">
                                <input class="form-check-input" type="checkbox" id="includeRi"
                                    wire:model="includeRi">
                                <label class="form-check-label text-white-50" for="includeRi">
                                    Incluir RI
                                </label>
                            </div>
                        </div>
                        <div class="filter-group">
                            <button type="button" class="filter-select export-btn" wire:click="exportToExcel"
                                wire:loading.attr="disabled">
                                <span wire:loading.remove wire:target="exportToExcel">
                                    <i class="ri-download-line me-2"></i>
                                    Exportar
                                </span>
                                <span wire:loading wire:target="exportToExcel">
                                    <i class="ri-loader-4-line me-2 animate-spin"></i>
                                    Exportando...
                                </span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('css')
        <style>
            .dashboard-header {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                border-radius: 16px;
                padding: 2rem;
                color: white;
                box-shadow: 0 8px 32px rgba(102, 126, 234, 0.15);
                position: relative;
                overflow: hidden;
            }

            .dashboard-header::before {
                content: '';
                position: absolute;
                top: 0;
                right: 0;
                width: 200px;
                height: 200px;
                background: rgba(255, 255, 255, 0.1);
                border-radius: 50%;
                transform: translate(50px, -50px);
            }

            .dashboard-header::after {
                content: '';
                position: absolute;
                bottom: 0;
                left: 0;
                width: 150px;
                height: 150px;
                background: rgba(255, 255, 255, 0.05);
                border-radius: 50%;
                transform: translate(-30px, 30px);
            }

            .header-content {
                position: relative;
                z-index: 2;
            }

            .header-icon {
                width: 60px;
                height: 60px;
                background: rgba(255, 255, 255, 0.2);
                border-radius: 16px;
                display: flex;
                align-items: center;
                justify-content: center;
                backdrop-filter: blur(10px);
                border: 1px solid rgba(255, 255, 255, 0.2);
            }

            .header-icon i {
                font-size: 28px;
                color: white;
            }

            .header-title {
                font-size: 2.5rem;
                font-weight: 700;
                color: white;
                margin: 0;
                text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            }

            .header-subtitle {
                font-size: 1.1rem;
                color: rgba(255, 255, 255, 0.9);
                font-weight: 500;
            }

            .header-description {
                color: rgba(255, 255, 255, 0.8);
                font-size: 0.95rem;
                line-height: 1.5;
            }

            .header-filters {
                position: relative;
                z-index: 2;
            }

            .filters-container {
                background: rgba(255, 255, 255, 0.1);
                border-radius: 12px;
                padding: 1.5rem;
                backdrop-filter: blur(10px);
                border: 1px solid rgba(255, 255, 255, 0.2);
            }

            .filter-group {
                margin-bottom: 1rem;
            }

            .filter-group:last-child {
                margin-bottom: 0;
            }

            .filter-label {
                display: block;
                font-size: 0.85rem;
                font-weight: 600;
                color: rgba(255, 255, 255, 0.9);
                margin-bottom: 0.5rem;
                text-transform: uppercase;
                letter-spacing: 0.5px;
            }

            .filter-select {
                width: 100%;
                padding: 0.75rem 1rem;
                border: 1px solid rgba(255, 255, 255, 0.2);
                border-radius: 8px;
                background: rgba(255, 255, 255, 0.1);
                color: white;
                font-size: 0.9rem;
                backdrop-filter: blur(10px);
                transition: all 0.3s ease;
            }

            .filter-select:focus {
                outline: none;
                border-color: rgba(255, 255, 255, 0.4);
                background: rgba(255, 255, 255, 0.15);
                box-shadow: 0 0 0 3px rgba(255, 255, 255, 0.1);
            }

            .filter-select option {
                background: #2c3e50;
                color: white;
            }

            .filter-select::placeholder {
                color: rgba(255, 255, 255, 0.6);
            }

            /* Modern Cards */
            .modern-card {
                background: white;
                border: none;
                border-radius: 16px;
                box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
                transition: all 0.3s ease;
                overflow: hidden;
                position: relative;
                height: 100%;
                width: 100%;
            }

            .modern-card::before {
                content: '';
                position: absolute;
                top: 0;
                left: 0;
                right: 0;
                height: 4px;
                background: linear-gradient(90deg, #667eea, #764ba2);
            }

            .modern-card:hover {
                transform: translateY(-2px);
                box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
            }

            .modern-card-body {
                padding: 1.5rem;
                display: flex;
                flex-direction: column;
                min-height: 100%;
            }

            .modern-card-head {
                display: flex;
                justify-content: space-between;
                align-items: flex-start;
                gap: .75rem;
                min-height: 48px;
                margin-bottom: 1rem;
            }

            .modern-card-title {
                font-size: 0.9rem;
                font-weight: 600;
                color: #6c757d;
                margin-bottom: 0;
                text-transform: uppercase;
                letter-spacing: 0.5px;
                line-height: 1.25;
            }

            .modern-period-badge {
                flex-shrink: 0;
                max-width: 66%;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
            }

            .modern-card-icon {
                width: 50px;
                height: 50px;
                border-radius: 12px;
                display: flex;
                align-items: center;
                justify-content: center;
                margin-bottom: 1rem;
            }

            .modern-card-icon.primary {
                background: linear-gradient(135deg, #667eea, #764ba2);
                color: white;
            }

            .modern-card-icon.success {
                background: linear-gradient(135deg, #56ab2f, #a8e6cf);
                color: white;
            }

            .modern-card-icon.warning {
                background: linear-gradient(135deg, #f093fb, #f5576c);
                color: white;
            }

            .modern-card-value {
                font-size: 2.2rem;
                font-weight: 700;
                color: #2c3e50;
                margin-bottom: 0.5rem;
                line-height: 1.1;
                word-break: break-word;
            }

            .modern-card-value-split {
                display: flex;
                flex-wrap: wrap;
                align-items: baseline;
                gap: .35rem;
            }

            .modern-growth-row {
                margin-top: auto;
                display: flex;
                justify-content: space-between;
                gap: .75rem;
                flex-wrap: wrap;
            }

            .modern-card-growth {
                font-size: 0.85rem;
                font-weight: 600;
            }

            .growth-positive {
                color: #28a745;
            }

            .growth-negative {
                color: #dc3545;
            }

            /* Modern Chart Card */
            .chart-card {
                background: white;
                border: none;
                border-radius: 16px;
                box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
                overflow: hidden;
            }

            .chart-card-header {
                background: linear-gradient(135deg, rgba(102, 126, 234, 0.1), rgba(118, 75, 162, 0.1));
                padding: 1.5rem;
                border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            }

            .chart-card-title {
                font-size: 1.1rem;
                font-weight: 600;
                color: #2c3e50;
                margin: 0;
            }

            .chart-card-body {
                padding: 1.5rem;
            }

            /* Modern Activity Card */
            .activity-card {
                background: white;
                border: none;
                border-radius: 16px;
                box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
                overflow: hidden;
            }

            .activity-header {
                background: linear-gradient(135deg, rgba(102, 126, 234, 0.1), rgba(118, 75, 162, 0.1));
                padding: 1.5rem;
                border-bottom: 1px solid rgba(0, 0, 0, 0.05);
                display: flex;
                justify-content: between;
                align-items: center;
            }

            .activity-title {
                font-size: 1.1rem;
                font-weight: 600;
                color: #2c3e50;
                margin: 0;
            }

            .modern-dropdown {
                background: none;
                border: none;
                color: #6c757d;
                padding: 0.5rem;
                border-radius: 8px;
                transition: all 0.3s ease;
            }

            .modern-dropdown:hover {
                background: rgba(102, 126, 234, 0.1);
                color: #667eea;
            }

            .activity-body {
                padding: 1.5rem;
                max-height: 500px;
                overflow-y: auto;
            }

            .modern-activity-item {
                display: flex;
                align-items: flex-start;
                padding: 1rem 0;
                border-bottom: 1px solid rgba(0, 0, 0, 0.05);
                transition: all 0.3s ease;
            }

            .modern-activity-item:hover {
                background: rgba(102, 126, 234, 0.02);
                border-radius: 8px;
                margin: 0 -0.5rem;
                padding: 1rem 0.5rem;
            }

            .modern-activity-item:last-child {
                border-bottom: none;
            }

            .activity-time {
                font-size: 0.75rem;
                color: #6c757d;
                font-weight: 500;
                min-width: 60px;
                text-align: center;
                background: rgba(102, 126, 234, 0.1);
                padding: 0.25rem 0.5rem;
                border-radius: 6px;
                margin-right: 1rem;
            }

            .activity-badge {
                width: 12px;
                height: 12px;
                border-radius: 50%;
                margin: 0.25rem 1rem 0 0;
                flex-shrink: 0;
            }

            .activity-content {
                flex: 1;
            }

            .activity-text {
                font-size: 0.9rem;
                color: #495057;
                margin: 0;
                line-height: 1.5;
            }

            .activity-note {
                font-weight: 600;
                color: #2c3e50;
            }

            /* Responsive adjustments */
            @media (max-width: 768px) {
                .dashboard-header {
                    padding: 1.5rem;
                }

                .header-title {
                    font-size: 2rem;
                }

                .filters-container {
                    margin-top: 1rem;
                }

                .filter-group {
                    margin-bottom: 0.75rem;
                }

                .modern-card-body {
                    padding: 1.25rem;
                }

                .modern-card-value {
                    font-size: 1.8rem;
                }

                .modern-period-badge {
                    max-width: 58%;
                }
            }
        </style>
    @endpush

    <div class="row dashboard">
        <div class="col-md-8">
            @php
                $periodStart = !empty($dt_in ?? null) ? Carbon::parse($dt_in) : null;
                $periodEnd = !empty($dt_out ?? null) ? Carbon::parse($dt_out) : null;
                $periodBadge = ($periodStart && $periodEnd)
                    ? ($periodStart->translatedFormat('d/M') . ' - ' . $periodEnd->translatedFormat('d/M/Y'))
                    : ($data['mes'] ?? '');
            @endphp
            <div class="row mb-4 g-3">
                <div class="col-xxl-4 col-md-6 d-flex">
                    <div class="modern-card h-100 w-100">
                        <div class="modern-card-body">
                            <div class="modern-card-head">
                                <div class="modern-card-title">
                                    <i class="ri-lightbulb-flash-line me-1"></i>
                                    Postes/Ativos
                                </div>
                                <span class="badge bg-light text-dark modern-period-badge">
                                    {{ $periodBadge }}
                                </span>
                            </div>

                            <div class="modern-card-icon primary">
                                <i class="ri-lightbulb-flash-line fs-4"></i>
                            </div>

                            <div class="modern-card-value">{{ $data['totalPostesMes'] ?? 0 }}</div>

                            @php

                                $growthPostes = 0;
                                $isIncreasePostes = true;
                                $currentPostes = $data['totalPostesMes'] ?? 0;
                                $previousPostes = $data['totalPostesMesAnterior'] ?? 0;

                                if ($previousPostes == 0) {
                                    if ($currentPostes > 0) {
                                        $growthPostes = 100;
                                        $isIncreasePostes = true;
                                    } else {
                                        $growthPostes = 0;
                                        $isIncreasePostes = true;
                                    }
                                } else {
                                    $diff = $currentPostes - $previousPostes;
                                    $growthPostes = ($diff / $previousPostes) * 100;
                                    $isIncreasePostes = $growthPostes >= 0;
                                }
                            @endphp

                            <div
                                class="modern-card-growth {{ $isIncreasePostes ? 'growth-positive' : 'growth-negative' }}">
                                <i class="ri-arrow-{{ $isIncreasePostes ? 'up' : 'down' }}-line me-1"></i>
                                {{ number_format($growthPostes, 1) }}% {{ $isIncreasePostes ? 'aumento' : 'queda' }}

                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xxl-4 col-md-6 d-flex">
                    <div class="modern-card h-100 w-100">
                        <div class="modern-card-body">
                            <div class="modern-card-head">
                                <div class="modern-card-title">
                                    <i class="ri-bill-line me-1"></i>
                                    Notas/OV
                                </div>
                                <span class="badge bg-light text-dark modern-period-badge">
                                    {{ $periodBadge }}
                                </span>
                            </div>

                            <div class="modern-card-icon success">
                                <i class="ri-bill-line fs-4"></i>
                            </div>

                            <div class="modern-card-value">{{ $data['totalNotasMes'] ?? 0 }}</div>

                            @php
                                $growthProductions = 0;
                                $isIncreaseProductions = true;
                                $currentNotas = $data['totalNotasMes'] ?? 0;
                                $previousNotas = $data['totalNotasMesAnterior'] ?? 0;

                                if ($previousNotas == 0) {
                                    if ($currentNotas > 0) {
                                        $growthProductions = 100;
                                        $isIncreaseProductions = true;
                                    } else {
                                        $growthProductions = 0;
                                        $isIncreaseProductions = true;
                                    }
                                } else {
                                    $diff = $currentNotas - $previousNotas;
                                    $growthProductions = ($diff / $previousNotas) * 100;
                                    $isIncreaseProductions = $growthProductions >= 0;
                                }
                            @endphp

                            <div
                                class="modern-card-growth {{ $isIncreaseProductions ? 'growth-positive' : 'growth-negative' }}">
                                <i class="ri-arrow-{{ $isIncreaseProductions ? 'up' : 'down' }}-line me-1"></i>
                                {{ number_format($growthProductions, 1) }}%
                                {{ $isIncreaseProductions ? 'aumento' : 'queda' }}


                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xxl-4 col-xl-12 d-flex">
                    <div class="modern-card h-100 w-100">
                        <div class="modern-card-body">
                            <div class="modern-card-head">
                                <div class="modern-card-title">
                                    <i class="ri-lightbulb-flash-line me-1"></i>
                                    Produção Hoje
                                </div>
                            </div>

                            <div class="modern-card-icon warning">
                                <i class="ri-lightbulb-flash-line fs-4"></i>
                            </div>

                            <div class="modern-card-value modern-card-value-split">
                                {{ $data['totalPostesHoje'] ?? 0 }} <span class="fs-6">Postes</span> /
                                {{ $data['totalNotasHoje'] ?? 0 }} <span class="fs-6">Notas</span>
                            </div>

                            @php
                                $growthPostesHoje = 0;
                                $isIncreasePostesHoje = true;
                                $currentPostesHoje = $data['totalPostesHoje'] ?? 0;
                                $previousPostesOntem = $data['totalPostesOntem'] ?? 0;

                                if ($previousPostesOntem == 0) {
                                    if ($currentPostesHoje > 0) {
                                        $growthPostesHoje = 100;
                                        $isIncreasePostesHoje = true;
                                    } else {
                                        $growthPostesHoje = 0;
                                        $isIncreasePostesHoje = true;
                                    }
                                } else {
                                    $diffPostesHoje = $currentPostesHoje - $previousPostesOntem;
                                    $growthPostesHoje = ($diffPostesHoje / $previousPostesOntem) * 100;
                                    $isIncreasePostesHoje = $growthPostesHoje >= 0;
                                }

                                $growthNotasHoje = 0;
                                $isIncreaseNotasHoje = true;
                                $currentNotasHoje = $data['totalNotasHoje'] ?? 0;
                                $previousNotasOntem = $data['totalNotasOntem'] ?? 0;

                                if ($previousNotasOntem == 0) {
                                    if ($currentNotasHoje > 0) {
                                        $growthNotasHoje = 100;
                                        $isIncreaseNotasHoje = true;
                                    } else {
                                        $growthNotasHoje = 0;
                                        $isIncreaseNotasHoje = true;
                                    }
                                } else {
                                    $diffNotasHoje = $currentNotasHoje - $previousNotasOntem;
                                    $growthNotasHoje = ($diffNotasHoje / $previousNotasOntem) * 100;
                                    $isIncreaseNotasHoje = $growthNotasHoje >= 0;
                                }
                            @endphp

                            <div class="modern-growth-row">
                                <div
                                    class="modern-card-growth {{ $isIncreasePostesHoje ? 'growth-positive' : 'growth-negative' }}">
                                    <i class="ri-arrow-{{ $isIncreasePostesHoje ? 'up' : 'down' }}-line me-1"></i>
                                    {{ number_format($growthPostesHoje, 1) }}% Postes
                                </div>
                                <div
                                    class="modern-card-growth {{ $isIncreaseNotasHoje ? 'growth-positive' : 'growth-negative' }}">
                                    <i class="ri-arrow-{{ $isIncreaseNotasHoje ? 'up' : 'down' }}-line me-1"></i>
                                    {{ number_format($growthNotasHoje, 1) }}% Notas
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>


            <div class="chart-card">
                <div class="chart-card-header">
                    <h5 class="chart-card-title">
                        <i class="ri-bar-chart-line me-2"></i>
                        Produção Diária
                        <span class="badge bg-primary ms-2">{{ $data['mes'] ?? '' }}/{{ $data['ano'] ?? '' }}</span>
                    </h5>
                </div>
                <div class="chart-card-body" wire:ignore>
                    <div style="max-height: 400px">
                        <x-grafico.apex :chart="$mesAtual" chartId="mesAtual" class="w-100" />
                    </div>
                </div>
            </div>

            <div class="chart-card mt-4">
                <div class="chart-card-header">
                    <h5 class="chart-card-title">
                        <i class="ri-line-chart-line me-2"></i>
                        Mensal
                        <span class="badge bg-success ms-2">{{ $data['ano'] ?? '' }} /
                            {{ $data['ano'] - 1 ?? '' }}</span>
                    </h5>
                </div>
                <div class="chart-card-body">
                    <div style="max-height: 400px" wire:ignore>
                        <x-grafico.apex :chart="$acumuladoMensal" chartId="acumuladoMensal" class="w-100" />
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="activity-card">
                <div class="activity-header">
                    <h5 class="activity-title">
                        <i class="ri-pulse-line me-2"></i>
                        Atividade Recente
                        <span class="badge bg-light text-dark ms-2">{{ $recentFilterName }}</span>
                    </h5>
                    <div class="dropdown">
                        <button class="modern-dropdown" data-bs-toggle="dropdown">
                            <i class="ri-filter-line"></i>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li class="dropdown-header">
                                <h6 class="mb-0">Filtros</h6>
                            </li>
                            <li><a class="dropdown-item" href="#" wire:click="$set('recentFilter', 'today')">
                                    <i class="ri-calendar-line me-2"></i>Hoje
                                </a></li>
                            <li><a class="dropdown-item" href="#" wire:click="$set('recentFilter', 'month')">
                                    <i class="ri-calendar-month-line me-2"></i>Este Mês
                                </a></li>
                            <li><a class="dropdown-item" href="#" wire:click="$set('recentFilter', 'year')">
                                    <i class="ri-calendar-year-line me-2"></i>Este Ano
                                </a></li>
                        </ul>
                    </div>
                </div>

                <div class="activity-body">
                    @if ($recentActivity->isNotEmpty())
                        @foreach ($recentActivity as $activity)
                            <div class="modern-activity-item">
                                <div class="activity-time">
                                    {{ $activity->created_at->diffForHumans(null, true, true) }}
                                </div>
                                <div class="activity-badge bg-{{ Notestatus::status($activity->status)->color }}"
                                    data-bs-toggle="tooltip" data-bs-placement="top"
                                    title="{{ $activity->description }}"></div>
                                <div class="activity-content">
                                    <p class="activity-text">
                                        <span class="activity-note">{{ $activity->note->note ?? 'N/A' }} -
                                            {{ $activity->service->service ?? 'N/A' }}</span><br>
                                        {{ $activity->info }}
                                    </p>
                                </div>
                            </div>
                        @endforeach
                    @else
                        <div class="text-center py-4">
                            <div class="mb-3">
                                <i class="ri-emotion-sad-line" style="font-size: 3rem; color: #dee2e6;"></i>
                            </div>
                            <p class="text-muted mb-0">Nenhuma atividade recente encontrada.</p>
                        </div>
                    @endif
                </div>
            </div>



            <div class="activity-card mt-4">
                <div class="activity-header">
                    <h5 class="activity-title">
                        <i class="ri-exchange-line me-2"></i>
                        Transferências Pendentes
                        <span class="badge bg-warning text-dark ms-2 d-none" id="transfercount">3 Pendentes</span>
                    </h5>

                </div>

                <div class="activity-body">
                    @livewire('home.tools.transfer-notes', ['idCount' => 'transfercount'], key('transfer-notes'))
                </div>
            </div>







        </div>
    </div>
</div>
