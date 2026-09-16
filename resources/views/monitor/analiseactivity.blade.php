@extends('layouts.padrao_ext')



@section('content')
    <div class="row g-2">
        <div class="col-12">
            @livewire('monitor.services.monitorbusca', key('MonitorBusca'))
        </div>



        @php
            $userServices = Auth()->user()->ToServices()->with('Service')->where('service', true)->get();
        @endphp
        @if ($userServices->count())
            @foreach ($userServices as $userService)
                @php($service = $userService->Service)
                @if (!$service)
                    @continue
                @endif
                <div class="col-xs-6 col-md-6 col-xl-6">
                    @livewire('components.statistics.statscard', ['service' => $service->uuid], key('stats-' . $service->uuid))

                </div>
            @endforeach
        @endif

    </div>
@endsection
