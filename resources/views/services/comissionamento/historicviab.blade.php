@extends('layouts.padrao')

@section('breadcrumb')
    <nav style="--bs-breadcrumb-divider: url(&#34;data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='8' height='8'%3E%3Cpath d='M2.5 0L1 1.5 3.5 4 1 6.5 2.5 8l4-4-4-4z' fill='%236c757d'/%3E%3C/svg%3E&#34;);"
        aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
            <li class="breadcrumb-item">Serviços</li>
            <li class="breadcrumb-item">{{ $service->service }}</li>
            <li class="breadcrumb-item" aria-current="page">Histórico</li>
        </ol>
    </nav>
@endsection

@section('menu')
    @include('services.comissionamento.menu')
@endsection

@section('content')
    @livewire('services.comission.histviab', ['service' => $service->uuid], key('histviab'))
@endsection
