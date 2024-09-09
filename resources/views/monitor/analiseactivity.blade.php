@extends('layouts.padrao_ext')



@section('content')
    <div class="row g-2">
        <div class="col-12">
            @livewire('monitor.services.monitorbusca', key('MonitorBusca'))
        </div>



        @if (isset(Auth()->user()->Employee->Contract->services) &&
                Auth()->user()->Employee->Contract->service &&
                Auth()->user()->Employee->Contract->services->count())
            @foreach (Auth()->user()->Employee->Contract->services as $service)
                <div class="col-xs-6 col-md-6 col-xl-6">
                    @livewire('components.statistics.statscard', ['service' => $service->uuid], key('stats-' . $service->uuid))

                </div>
            @endforeach
        @endif

    </div>
@endsection
