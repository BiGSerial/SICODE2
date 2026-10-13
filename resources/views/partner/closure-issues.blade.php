@extends('layouts.company')

@section('breadcrumb')
    <nav aria-label="breadcrumb" class="py-0 my-0">
        <ol class="breadcrumb bg-light px-3 pt-3 rounded-3">
            <li class="breadcrumb-item"><a href="{{ route('company') }}">Home</a></li>
            <li class="breadcrumb-item">Parceiro</li>
            <li class="breadcrumb-item active" aria-current="page">Pendências de Encerramento</li>
        </ol>
    </nav>
@endsection

@section('menu')
    @livewire('partner.menu')
@endsection

@section('content')
    @livewire('partner.closure-issues.index')
@endsection
