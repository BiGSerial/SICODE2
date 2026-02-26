@extends('layouts.padrao')

@section('menu')
    @include('reports.menu')
@endsection

@section('content')
    @livewire('reports.ads-requested-report')
@endsection
