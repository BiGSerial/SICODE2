@extends('layouts.padrao')

@section('breadcrumb')
    <nav aria-label="breadcrumb" class="py-0 my-0">
        <ol class="breadcrumb bg-light px-3 pt-3 rounded-3">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
                <li class="breadcrumb-item">Administracao</li>
                <li class="breadcrumb-item">Gerenciamento</li>
                <li class="breadcrumb-item active" aria-current="page">ADS</li>
            </ol>
        </ol>
    </nav>
@endsection

@section('menu')
    @include('admin.control.menu')
@endsection

@section('content')
    @livewire('admin.control.ads-monitor', key('admin-control-ads-monitor'))

    <div class="mt-4">
        @livewire('config.system.ads-request-recipients', key('admin-ads-auto-recipients'))
    </div>
@endsection
