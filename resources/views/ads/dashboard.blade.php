@extends('layouts.padrao')

@section('menu')
    @include('ads.menu')
@endsection

@section('content')
    @livewire('reports.ads-requested-report')
@endsection

