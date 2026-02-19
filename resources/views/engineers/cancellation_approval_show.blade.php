@extends('layouts.padrao_ext')

@section('breadcrumb')
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
        <li class="breadcrumb-item">Engenharia</li>
        <li class="breadcrumb-item"><a href="{{ route('engineers.cancellations.index') }}">Aprovação de Cancelamentos</a></li>
        <li class="breadcrumb-item active" aria-current="page">Detalhe</li>
    </ol>
@endsection

@section('menu')
@endsection

@section('content')
    @livewire('engineers.cancellation-approvals.show', ['request' => $request])
@endsection
