@extends('layouts.padrao_ext')
@php
    $meses = [
        1 => 'Janeiro',
        2 => 'Fevereiro',
        3 => 'Março',
        4 => 'Abril',
        5 => 'Maio',
        6 => 'Junho',
        7 => 'Julho',
        8 => 'Agosto',
        9 => 'Setembro',
        10 => 'Outubro',
        11 => 'Novembro',
        12 => 'Dezembro',
    ];

@endphp


@section('menu')
    <aside id="sidebar" class="sidebar edp-bg-sprucegreen-100">

        <ul class="sidebar-nav" id="sidebar-nav">

        </ul>

    </aside>
@endsection

@section('content')

    <div class="container-fluid main-content menu-hidden">

        @can('engineer')
            @livewire('engineer.main')
        @endcan

        @can('user')
            <div class="row justify-content-center">
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header">HOME INFO <span class="fs-6">{{ date('d/m/Y H:i') }}</span></div>

                        <div class="card-body">
                            <h4 class="mb-3">Olá, {{ Auth()->User()->name ? Auth()->User()->name : '' }}</h4>

                            {{-- @dd($prod_mes) --}}

                            @if ($prod_dia->count())
                                {{-- @livewire('components.graph.line', [
                            'title' => 'SUA PRODUÇÃO DIÁRIA (confirmado)',
                            'labels' => $prod_dia->pluck('date'),
                            'datas' => $prod_dia->pluck('total'),
                            'label' => 'DIÁRIO',
                            'Chartid' => 'prodDiario',
                        ]) --}}

                                @livewire('components.graph.linedark', [
                                    'title' => 'SUA PRODUÇÃO DIÁRIA (confirmado)',
                                    'labels' => $prod_dia->pluck('date'),
                                    'datasets' => [
                                        [
                                            'label' => ['NOTAS/OVS'],
                                            // ou 'bar' conforme necessário
                                            'color' => [['rgb(40, 255, 82)']], // Escolha uma cor para o dataset
                                            'data' => $prod_dia->pluck('total'),
                                        ],
                                        [
                                            'label' => ['POSTES'],
                                            // ou 'bar' conforme necessário
                                            'color' => [['rgb(40, 255, 82)']], // Escolha uma cor para o dataset
                                            'data' => $prod_dia->pluck('postes'),
                                        ],
                                        // Adicione mais datasets conforme necessário
                                    ],
                                    'Chartid' => 'prodDiaria',
                                ])
                            @endif

                            @if ($prod_mes->count())
                                {{-- @livewire('components.graph.line', [
                            'title' => 'SEU ACUMULADO MENSAL',
                            'labels' => $prod_mes->pluck('label'),
                            'datas' => $prod_mes->pluck('total'),
                            'label' => 'MENSAL',
                            'Chartid' => 'prodMensal',
                        ]) --}}
                                @livewire('components.graph.linedark', [
                                    'title' => 'SEU ACUMULADO MENSAL',
                                    'labels' => $prod_mes->pluck('label'),
                                    'datasets' => [
                                        [
                                            'label' => ['NOTAS/OVS'],
                                            // ou 'bar' conforme necessário
                                            'color' => [['rgb(40, 255, 82)']], // Escolha uma cor para o dataset
                                            'data' => $prod_mes->pluck('total'),
                                        ],
                                        [
                                            'label' => ['POSTES'],
                                            // ou 'bar' conforme necessário
                                            'color' => [['rgb(40, 255, 82)']], // Escolha uma cor para o dataset
                                            'data' => $prod_mes->pluck('postes'),
                                        ],
                                        // Adicione mais datasets conforme necessário
                                    ],
                                    'Chartid' => 'prodMensal',
                                ])
                            @endif

                        </div>
                    </div>
                </div>

                <div class="col-md-4">

                    <div class="dashboard">
                        <div class="card info-card sales-card">
                            <h5 class="card-header">MINHA PRODUÇÃO <strong>{{ strtoupper($meses[date('n')]) }}</strong></h5>
                            <div class="card-body">



                                <div class="d-flex align-items-center">
                                    <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                                        <i class="ri-user-shared-2-fill text-danger"></i>

                                    </div>
                                    <ul class="list-group list-group-flush">
                                        <li class="list-group-item">TOTAL <span class="fw-bold"
                                                style="font-size: 10px">(confirmado e não confirmado)</span></li>
                                        <li class="list-group-item"><span class="fw-bold fs-4">{{ $status_count->total_notes }}
                                                <span class="fw-bold" style="font-size: 10px">notas</span> /
                                                {{ $status_count->total_postes }}</span> <span class="fw-bold"
                                                style="font-size: 10px">postes</span></li>
                                    </ul>
                                </div>

                                <div class="d-flex align-items-center">
                                    <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                                        <i class="ri-user-follow-fill text-success"></i>

                                    </div>
                                    <ul class="list-group list-group-flush">
                                        <li class="list-group-item">CONFIRMADOS</li>
                                        <li class="list-group-item"><span
                                                class="fw-bold fs-4">{{ $status_count->confirmed_count }} <span class="fw-bold"
                                                    style="font-size: 10px">notas</span> /
                                                {{ $status_count->confirmed_postes }}</span> <span class="fw-bold"
                                                style="font-size: 10px">postes</span></li>
                                    </ul>
                                </div>

                                <div class="d-flex align-items-center">
                                    <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                                        <i class="ri-user-2-fill text-primary"></i>
                                    </div>
                                    <ul class="list-group list-group-flush">
                                        <li class="list-group-item">HOJE <span class="fw-bold"
                                                style="font-size: 10px">(confirmado e não confirmado)</span></li>
                                        <li class="list-group-item"><span
                                                class="fw-bold fs-4">{{ $status_count->completed_today_count }} <span
                                                    class="fw-bold" style="font-size: 10px">notas</span> /
                                                {{ $status_count->postes_today }}</span> <span class="fw-bold"
                                                style="font-size: 10px">postes</span></li>
                                    </ul>
                                </div>
                            </div>

                        </div>
                    </div>

                    @if ($waiting_list->count())
                        <div class="card mb-3">
                            <h5 class="card-header">ENTRADA MANUAL EM ESPERA</h5>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-sm table-condensed">
                                        <thead class="table-dark">
                                            <tr>
                                                <th scope="col">Nota</th>
                                                <th scope="col">Serviço</th>
                                                <th scope="col">Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($waiting_list->where('finish_at', '>=', date('Y-m-01 0:00:00'))
                                                as $list)
                                                <tr>
                                                    <td>{{ $list->note }}</td>
                                                    <td>{{ $list->Service->service }}</td>
                                                    <td>
                                                        @if ($list->completed && !$list->confirmed)
                                                            <span class="badge text-bg-danger">AGUARDANDO</span>
                                                        @elseif ($list->confirmed && !$list->cancel)
                                                            <span class="badge text-bg-success">CONFIRMADO</span>
                                                        @elseif (!$list->completed && !$list->confirmed && $list->cancel)
                                                            <span class="badge text-bg-secondary">EM CANCELAMENTO</span>
                                                        @elseif ($list->confirmed && $list->cancel)
                                                            <span class="badge text-bg-info">CANCELADO</span>
                                                        @elseif (!$list->completed && !$list->confirmed && !$list->cancel)
                                                            <span class="badge text-bg-primary">EM ABERTO</span>
                                                        @endif
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    @endif
                    @if ($inconsistencies->count())
                        <div class="card mb-3">
                            <h5 class="card-header ">NOTAS INCONSISTENTES</h5>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-sm table-condensed">
                                        <thead class="table-dark">
                                            <tr>
                                                <th scope="col">Nota</th>
                                                <th scope="col">Serviço</th>
                                                <th scope="col">Tentativas</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($inconsistencies as $list)
                                                <tr>
                                                    <td>{{ $list->Note->note }}</td>
                                                    <td>{{ $list->Service->service }}</td>
                                                    <td>{{ $list->tries }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        @endcan


    </div>
@endsection
