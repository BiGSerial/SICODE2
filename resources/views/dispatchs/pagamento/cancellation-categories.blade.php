@extends('layouts.padrao')

@section('breadcrumb')
    <nav aria-label="breadcrumb" class="py-0 my-0">
        <ol class="breadcrumb bg-light px-3 pt-3 rounded-3">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
                <li class="breadcrumb-item">Despachos</li>
                <li class="breadcrumb-item active" aria-current="page">Categorias de Cancelamento</li>
            </ol>
        </ol>
    </nav>
@endsection

@section('menu')
    @include('dispatchs.pagamento.menu')
@endsection

@section('content')
    @livewire('admin.cancellation-categories')
@endsection
