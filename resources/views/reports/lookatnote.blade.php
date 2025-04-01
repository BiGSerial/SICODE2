@extends('layouts.padrao_ext')

@section('breadcrumb')
    <nav aria-label="breadcrumb" class="py-0 my-0">
        <ol class="breadcrumb bg-light px-3 pt-3 rounded-3">
            <ol class="breadcrumb">
                <li class="breadcrumb-item">Home</li>
                <li class="breadcrumb-item active" aria-current="page">SITUAÇÃO CONTRATAÇÂO DE OBRAS</li>
            </ol>
        </ol>
    </nav>
@endsection

@section('content')
    @livewire('construction.hiring.lookatnotes', ['service' => null])
@endsection
