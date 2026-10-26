@extends('layouts.padrao')

@section('menu')
    @include('closure.closure-menu', ['ctx' => $ctx])
@endsection

@section('breadcrumb')
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
        <li class="breadcrumb-item"><a href="{{ \App\Services\Closure\ClosureNavContext::homeUrl($ctx) }}">Encerramento</a></li>
        <li class="breadcrumb-item active" aria-current="page">Gestão da Obra</li>
    </ol>
@endsection

@section('content')
    @livewire('closure.works.show', ['noteId' => $noteId, 'ctx' => $ctx, 'audience' => 'internal'], key('closure-work-show-' . $noteId))
@endsection
