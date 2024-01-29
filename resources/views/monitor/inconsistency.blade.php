@extends('layouts.padrao_ext')

@section('content')
    <div class="col-12">
        @livewire('monitor.services.monitorbusca', key('MonitorBusca'))
    </div>
    @livewire('monitor.services.inconsistencylist', key('inconsistency'))
@endsection
