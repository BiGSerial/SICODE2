@extends('layouts.padrao')

@section('breadcrumb')
    <nav aria-label="breadcrumb" class="py-0 my-0">
        <ol class="breadcrumb bg-light px-3 pt-3 rounded-3">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
                <li class="breadcrumb-item">Serviços</li>
                <li class="breadcrumb-item active" aria-current="page">Cancelamentos</li>
            </ol>
        </ol>
    </nav>
@endsection

@section('menu')
    @include('services.pagamento.menu')
@endsection

@section('content')
    @livewire('services.payment.cancellation.request-create', ['service' => $service->uuid])
@endsection
