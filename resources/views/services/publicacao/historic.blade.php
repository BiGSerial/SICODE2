@extends('layouts.padrao')

@section('breadcrumb')
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb bg-light px-3 py-2  my-1 rounded-3">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
                <li class="breadcrumb-item">Serviços</li>
                <li class="breadcrumb-item">{{ $service->service }}</li>
                <li class="breadcrumb-item" aria-current="page">Histórico</li>
            </ol>
        </ol>
    </nav>
@endsection

@section('menu')
    @include('services.publicacao.menu')
@endsection

@section('content')
    {{-- @livewire('services.analises_pre.historic', ['service' => $service->uuid]) --}}
    @livewire('services.publication.historic', ['service' => $service->uuid])
@endsection
