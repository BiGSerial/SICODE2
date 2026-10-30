@extends('layouts.padrao')

@section('breadcrumb')
    <nav aria-label="breadcrumb" class="py-0 my-0">
        <ol class="breadcrumb bg-light px-3 pt-3 rounded-3">
            <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
            <li class="breadcrumb-item">Engenharia</li>
            <li class="breadcrumb-item">Informes</li>
            <li class="breadcrumb-item active" aria-current="page">Painel de informes</li>
        </ol>
    </nav>
@endsection

@section('menu')
    @livewire('engineers.menu', key('engineers-menu'))
@endsection

@section('content')
    @livewire('engineers.inform-dashboard', key('engineers-inform-dashboard'))
@endsection
