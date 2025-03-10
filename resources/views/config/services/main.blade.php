@extends('layouts.padrao')

@section('breadcrumb')
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb bg-light px-3 py-2  my-1 rounded-3">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
                <li class="breadcrumb-item"><a href="{{ route('config.main') }}">Configurações</a></li>
                <li class="breadcrumb-item active" aria-current="page">Serviços</li>
            </ol>
        </ol>
    </nav>
@endsection

@section('menu')
    @include('config.menu')
@endsection

@section('content')
    @livewire('config.services.services')
@endsection
