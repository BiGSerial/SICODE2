@extends('layouts.padrao')

@section('breadcrumb')
    <nav aria-label="breadcrumb" class="py-0 my-0">
        <ol class="breadcrumb bg-light px-3 pt-3 rounded-3">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
                <li class="breadcrumb-item">Administração</li>
                <li class="breadcrumb-item active" aria-current="page">Códigos de Protesto</li>
            </ol>
        </ol>
    </nav>
@endsection

@section('menu')
    @include('protest.dispatch.menu')
@endsection

@section('content')
    @livewire('admin.protest-measure-codes')
@endsection
