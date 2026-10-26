@extends('layouts.padrao')

@section('breadcrumb')
    <nav aria-label="breadcrumb" class="py-0 my-0">
        <ol class="breadcrumb bg-light px-3 pt-3 rounded-3">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
                <li class="breadcrumb-item">{{ $service->service }}</li>
                <li class="breadcrumb-item">Gestão do ciclo</li>
                <li class="breadcrumb-item active" aria-current="page">Calendário de depósitos</li>
            </ol>
        </ol>
    </nav>
@endsection

@section('menu')
    @include('dispatchs.encerramento.menu')
@endsection

@section('content')
    @livewire('closure.dispatch.deposit-calendar', key('closure-dispatch-deposits'))
@endsection
