@extends('layouts.padrao_ext')

@section('breadcrumb')
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb bg-light px-3 py-2  my-1 rounded-3">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
                <li class="breadcrumb-item">Logger</li>
                <li class="breadcrumb-item active" aria-current="page">Registros de Updates</li>
            </ol>
        </ol>
    </nav>
@endsection


@section('content')
    <div class="col-12">
        @livewire('logger.updatelogs')
    </div>
@endsection
