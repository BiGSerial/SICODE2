@extends('layouts.padrao')

@section('menu')
    @include('reports.menu')
@endsection

@section('content')
    @livewire('reports.inform-ads-tacita-report')
@endsection
