@extends('layouts.padrao')

@section('breadcrumb')
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
        <li class="breadcrumb-item"><a href="{{ route('closure.overview') }}">Encerramento</a></li>
        <li class="breadcrumb-item active" aria-current="page">Meta</li>
    </ol>
@endsection

@section('content')
    @livewire('closure.cycles.meta', key('closure-meta'))
@endsection
