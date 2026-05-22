@extends('layouts.padrao')

@section('breadcrumb')
    <nav aria-label="breadcrumb" class="py-0 my-0">
        <ol class="breadcrumb bg-light px-3 pt-3 rounded-3">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
                <li class="breadcrumb-item"><a href="{{ route('config.main') }}">Configurações</a></li>
                <li class="breadcrumb-item active" aria-current="page">Monitor do Schedule</li>
            </ol>
        </ol>
    </nav>
@endsection

@section('menu')
    @include('config.menu')
@endsection

@section('content')
    <div class="container-fluid mt-4">
        @livewire('config.system.schedule-monitor', key('systemScheduleMonitorPage'))
    </div>
@endsection
