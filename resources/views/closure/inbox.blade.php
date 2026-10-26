@extends('layouts.padrao')

@section('menu')
    @include($menu)
@endsection

@section('breadcrumb')
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
        <li class="breadcrumb-item">{{ $service->service }}</li>
        <li class="breadcrumb-item active" aria-current="page">Caixa do Encerramento</li>
    </ol>
@endsection

@section('content')
    @livewire('closure.inbox.index', ['area' => $area, 'tab' => (string) request('tab', 'accompanying')], key('closure-inbox-' . $area))
@endsection
