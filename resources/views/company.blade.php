@extends('layouts.company')

@section('breadcrumb')
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb bg-light px-3 py-2  my-1 rounded-3">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('company') }}">Home</a></li>
                <li class="breadcrumb-item">Construção</li>
                <li class="breadcrumb-item active" aria-current="page">Parceiro</li>
            </ol>
        </ol>
    </nav>
@endsection


@section('menu')
    @livewire('partner.menu')
@endsection

@section('content')
@endsection
