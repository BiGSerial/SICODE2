@extends('layouts.padrao')

@section('breadcrumb')
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
        <li class="breadcrumb-item">Jurídico</li>
        <li class="breadcrumb-item active" aria-current="page">Minhas Tarefas</li>
    </ol>
@endsection

@section('menu')
    @livewire('legal.menu', ['activeSection' => 'campo'], key('legal-menu'))
@endsection

@section('content')
    @livewire('legal.field.my-assignments', key('legal-my-assignments'))
@endsection
