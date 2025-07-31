@extends('layouts.padrao_ext')
@php
    $meses = [
        1 => 'Janeiro',
        2 => 'Fevereiro',
        3 => 'Março',
        4 => 'Abril',
        5 => 'Maio',
        6 => 'Junho',
        7 => 'Julho',
        8 => 'Agosto',
        9 => 'Setembro',
        10 => 'Outubro',
        11 => 'Novembro',
        12 => 'Dezembro',
    ];

@endphp


@section('menu')
    <aside id="sidebar" class="sidebar edp-bg-sprucegreen-100">

        <ul class="sidebar-nav" id="sidebar-nav">

        </ul>

    </aside>
@endsection

@section('content')
    @livewire('home.dashboard.dashboard', key('dashboard'))
@endsection
