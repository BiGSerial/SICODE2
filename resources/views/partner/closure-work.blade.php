@extends('layouts.company')

@section('breadcrumb')
    <nav aria-label="breadcrumb" class="py-0 my-0">
        <ol class="breadcrumb bg-light px-3 pt-3 rounded-3">
            <li class="breadcrumb-item"><a href="{{ route('company') }}">Home</a></li>
            <li class="breadcrumb-item"><a href="{{ route('partner.closure.issues') }}">Pendências de Encerramento</a></li>
            <li class="breadcrumb-item active" aria-current="page">Obra</li>
        </ol>
    </nav>
@endsection

@section('menu')
    @livewire('partner.menu')
@endsection

@section('content')
    @livewire('closure.works.show', ['noteId' => $noteId], key('partner-closure-work-' . $noteId))
@endsection
