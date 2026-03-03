@extends('layouts.padrao_ext')

@section('breadcrumb')
    <nav aria-label="breadcrumb" class="py-0 my-0">
        <ol class="breadcrumb bg-light px-3 pt-3 rounded-3">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
                <li class="breadcrumb-item">Dispatch</li>
                <li class="breadcrumb-item">Reclamações</li>
                <li class="breadcrumb-item active" aria-current="page">View</li>
            </ol>
        </ol>
    </nav>
@endsection

@section('menu')
    {{-- Visualização apenas leitura --}}
@endsection

@section('content')
    @livewire('protests.dispatch.view', ['readOnly' => true])
@endsection
