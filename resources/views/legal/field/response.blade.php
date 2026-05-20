@extends('layouts.padrao')

@section('breadcrumb')
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
        <li class="breadcrumb-item"><a href="{{ route('legal.field.queue') }}">Minhas Tarefas</a></li>
        <li class="breadcrumb-item active" aria-current="page">Responder Tarefa</li>
    </ol>
@endsection

@section('menu')
    @livewire('legal.menu', ['activeSection' => 'campo'], key('legal-menu'))
@endsection

@section('content')
    @livewire('legal.field.assignment-response', ['assignment_id' => $assignment_id], key('legal-assignment-response-'.$assignment_id))
@endsection
