@extends('layouts.padrao')

@section('breadcrumb')
    <nav aria-label="breadcrumb" class="py-0 my-0">
        <ol class="breadcrumb bg-light px-3 pt-3 rounded-3">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
                <li class="breadcrumb-item">Despacho</li>
                <li class="breadcrumb-item active" aria-current="page">Histórico {{ $service->service }}</li>
            </ol>
        </ol>
    </nav>
@endsection

@section('menu')
    @include('dispatchs.menu')
@endsection

@section('content')
    @livewire('dispatchs.historic', ['service' => $service->uuid])
@endsection
