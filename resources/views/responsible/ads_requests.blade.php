@extends('layouts.padrao')

@section('breadcrumb')
    <nav aria-label="breadcrumb" class="py-0 my-0">
        <ol class="breadcrumb bg-light px-3 pt-3 rounded-3">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
                <li class="breadcrumb-item">Responsavel</li>
                <li class="breadcrumb-item">Solicitacoes ADS</li>
            </ol>
        </ol>
    </nav>
@endsection

@section('menu')
    @livewire('responsible.menu', key('responsible-menu'))
@endsection

@section('content')
    @livewire('responsible.ads-requests', key('responsible-ads-requests'))
@endsection
