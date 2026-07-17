@extends('layouts.padrao')

@section('breadcrumb')
    <nav aria-label="breadcrumb" class="py-0 my-0">
        <ol class="breadcrumb bg-light px-3 pt-3 rounded-3">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
                <li class="breadcrumb-item">Serviços</li>
                <li class="breadcrumb-item" aria-current="page">{{ $service->service }}</li>
                <li class="breadcrumb-item active" aria-current="page">Obras Liberadas</li>
            </ol>
        </ol>
    </nav>
@endsection

@section('menu')
    @include('services.oexterno.menu')
@endsection

@section('content')
    @livewire('services.oexterno.released-works', ['service' => $service->uuid])
@endsection
