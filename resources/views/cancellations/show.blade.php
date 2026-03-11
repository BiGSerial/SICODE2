@extends('layouts.padrao_ext')

@section('breadcrumb')
    <nav aria-label="breadcrumb" class="py-0 my-0">
        <ol class="breadcrumb bg-light px-3 pt-3 rounded-3">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
                <li class="breadcrumb-item">Cancelamentos</li>
                <li class="breadcrumb-item active" aria-current="page">Detalhe Solicitação</li>
            </ol>
        </ol>
    </nav>
@endsection

@section('menu')
@endsection

@section('content')
    @livewire('services.payment.cancellation.request-show', ['service' => 'global', 'request' => $request])
@endsection
