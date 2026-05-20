@extends('layouts.padrao')

@section('breadcrumb')
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
        <li class="breadcrumb-item">Jurídico</li>
        <li class="breadcrumb-item active" aria-current="page">Relatórios</li>
    </ol>
@endsection

@section('menu')
    @livewire('legal.menu', ['activeSection' => 'gestao'], key('legal-menu'))
@endsection

@section('content')
    @livewire('legal.management.report-export', key('legal-report-export'))
@endsection
