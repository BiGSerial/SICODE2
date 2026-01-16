@extends('layouts.padrao')

@section('menu')
    @include('reports.return-intern-menu')
@endsection

@section('content')
    @livewire('reports.return-intern-list')
@endsection
