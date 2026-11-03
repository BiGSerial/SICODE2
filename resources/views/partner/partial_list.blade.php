@extends('layouts.company')

@section('breadcrumb')
    <nav aria-label="breadcrumb" class="py-0 my-0">
        <ol class="breadcrumb bg-light px-3 pt-3 rounded-3">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('company') }}">Home</a></li>
                <li class="breadcrumb-item">Construção</li>
                <li class="breadcrumb-item" aria-current="page">Parceiro</li>
                <li class="breadcrumb-item" aria-current="page">Informes - PARCIAL</li>
                <li class="breadcrumb-item active" aria-current="page">Lista</li>
            </ol>
        </ol>
    </nav>
@endsection


@section('menu')
    @livewire('partner.menu')
@endsection

@section('content')
    @livewire('partner.partial-list', key('partial-list'))
@endsection
