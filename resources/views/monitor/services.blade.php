@extends('layouts.padrao_ext')



@section('content')
    <div class="row">
        <div class="col-12">
            @livewire('monitor.services.monitorbusca', key('MonitorBusca'))
        </div>
        <div class="col-7">
            @livewire('monitor.services.servicelist', key('MonitorServiceList'))
        </div>
        <div class="col-5">
            @livewire('monitor.transfer.transferlist', key('MonitorTransfer'))

            {{-- @if (isset(Auth()->user()->Employee->Contract->services) &&
    Auth()->user()->Employee->Contract->service &&
    Auth()->user()->Employee->Contract->services->count())
                @foreach (Auth()->user()->Employee->Contract->services as $service)
                    @livewire('components.statistics.statscard', ['service' => $service->uuid], key('stats-' . $service->uuid))
                @endforeach
            @endif --}}

        </div>
    </div>
@endsection
