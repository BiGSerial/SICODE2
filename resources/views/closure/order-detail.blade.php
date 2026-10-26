@extends('layouts.padrao')

@section('menu')
    @include('closure.closure-menu', ['ctx' => $ctx])
@endsection

@section('breadcrumb')
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
        <li class="breadcrumb-item"><a href="{{ \App\Services\Closure\ClosureNavContext::homeUrl($ctx) }}">Encerramento</a></li>
        <li class="breadcrumb-item active" aria-current="page">Ordem {{ $orderId }}</li>
    </ol>
@endsection

@section('content')
    @livewire('closure.orders.detail', ['orderId' => $orderId, 'ctx' => $ctx], key('closure-order-detail-' . $orderId))
@endsection
