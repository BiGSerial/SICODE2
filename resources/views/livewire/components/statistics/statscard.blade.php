@php
    use Carbon\Carbon;
@endphp
<div wire:poll.5m>
    <div class="card chart-card">
        <div class="card-header py-0">
            <h5 class=" card-title font-weight-bold">{{ $title }}</h5>
        </div>

        <div class="card-body py-0">

            <p class="card-text mb-4 mt-0">Periodo: {{ Carbon::now()->firstOfMonth()->format('d/m') }} à
                {{ Carbon::now()->format('d/m') }} ({{ $workdays }} dias uteis)</p>
            <div class="d-flex justify-content-between">
                <p class="align-self-end"><span
                        class="display-4">{{ $service_count->sum('productions_count') }}</span><span
                        class="display-6">/{{ $service_count->count() }} </span>(Att/Total)
                </p>
                @if ($service_count->count())
                    <p class="align-self-end pb-2">
                        {{ $service_count->count() - $service_count->sum('productions_count') }}
                        ({{ round(($service_count->sum('productions_count') / $service_count->count()) * 100, 2) }}%)
                    </p>
                @endif
            </div>
            <div class="d-flex justify-content-between">
                <p class="align-self-end"><span
                        class="display-4">{{ round($day_count->sum('total') / $workdays, 2) }}</span> (Média Dia)
                </p>
                @if ($service_count->count())
                    <p class="align-self-end pb-2">
                        ({{ $day_count->sum('total') }} Notas)
                    </p>
                @endif
            </div>

            <div class="d-flex justify-content-between">
                <p class="align-self-end"><span
                        class="display-4">{{ round($day_count->sum('postes') / $workdays, 2) }}</span> (Média Dia)
                </p>
                @if ($service_count->count())
                    <p class="align-self-end pb-2">
                        ({{ $day_count->sum('postes') }} postes)
                    </p>
                @endif
            </div>

        </div>

        <div wire:ignore>

            {{-- @dump($days_left) --}}

        </div>

        <div class="card-body">
            <div class="tab-content" id="ex1-content">
                <div class="tab-pane fade show active" id="ex1-tabs-1" role="tabpanel" aria-labelledby="ex1-tabs-1">
                    @livewire(
                        'components.graph.line',
                        [
                            'title' => 'Encerramento por Horário',
                            'labels' => $hour_count->pluck('hour'),
                            'datas' => $hour_count->pluck('count'),
                            'label' => '/hora',
                            'Chartid' => 'prodDiario',
                        ],
                        key($service->uuid)
                    )
                    @livewire('components.graph.linedark', [
                        'title' => 'Produção Diária',
                        'labels' => $day_count->pluck('date'),
                        'datasets' => [
                            [
                                'label' => ['NOTAS/OVS'],
                                // ou 'bar' conforme necessário
                                'color' => [['rgb(40, 255, 82)']], // Escolha uma cor para o dataset
                                'data' => $day_count->pluck('total'),
                            ],
                            [
                                'label' => ['POSTES'],
                                // ou 'bar' conforme necessário
                                'color' => [['rgb(40, 255, 82)']], // Escolha uma cor para o dataset
                                'data' => $day_count->pluck('postes'),
                            ],
                            // Adicione mais datasets conforme necessário
                        ],
                        'Chartid' => 'prodDiaria',
                    ])
                    @livewire('components.graph.stackbar', [
                        'title' => 'Dias Vencimento OV',
                        'labels' => $days_left->pluck('days_remaining'),
                        'datasets' => [
                            [
                                'label' => ['Atribuído'],
                                // ou 'bar' conforme necessário
                                'color' => [['rgb(40, 255, 82)']], // Escolha uma cor para o dataset
                                'data' => $days_left->pluck('production_count'),
                                'stack' => 0,
                            ],
                            [
                                'label' => ['N Atribuído'],
                                // ou 'bar' conforme necessário
                                'color' => [['rgb(40, 255, 82)']], // Escolha uma cor para o dataset
                                'data' => $days_left->pluck('nota_att'),
                                'stack' => 0,
                            ],
                            // Adicione mais datasets conforme necessário
                        ],
                        'Chartid' => 'ContDaysLeft',
                    ])
                </div>

            </div>
        </div>
    </div>
</div>
