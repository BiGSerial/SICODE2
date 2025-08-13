@extends('layouts.padrao')

@section('breadcrumb')
    <nav aria-label="breadcrumb" class="py-0 my-0">
        <ol class="breadcrumb bg-light px-3 pt-3 rounded-3">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
                <li class="breadcrumb-item active" aria-current="page">Configurações</li>
            </ol>
        </ol>
    </nav>
@endsection

@section('menu')
    @include('config.menu')
@endsection

@section('content')
    <div class="container-fluid mt-5">
        <div class="row mt-5">

            <div class="col-md-8">
                @livewire('config.system.updatelog', key('systemLogUpdates'))

            </div>
            <div class="col-md-4">
                @livewire('config.system.sysspecs', key('systemSpecs'))
            </div>
           
        </div>
    </div>
@endsection
