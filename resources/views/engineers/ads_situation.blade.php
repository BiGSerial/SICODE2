@extends('layouts.padrao')

@section('breadcrumb')
    <nav aria-label="breadcrumb" class="py-0 my-0">
        <ol class="breadcrumb bg-light px-3 pt-3 rounded-3">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
                <li class="breadcrumb-item">Engenharia</li>
                <li class="breadcrumb-item">Informes de Conclusão</li>
                <li class="breadcrumb-item active" aria-current="page">Situação de ADS</li>
            </ol>
        </ol>
    </nav>
@endsection

@section('menu')
    @livewire('engineers.menu', key('engineers-menu'))
@endsection

@section('content')
    @livewire('engineers.ads.status', key('engineers-ads-status'))
@endsection
