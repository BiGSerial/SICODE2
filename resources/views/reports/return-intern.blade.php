@extends('layouts.padrao')

@section('menu')
    @include('reports.return-intern-menu')
@endsection

@section('content')
    @livewire('reports.open-return-interns')
@endsection
