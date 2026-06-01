@extends('layouts.padrao')

@section('breadcrumb')
    <ol class="breadcrumb">
        <li class="breadcrumb-item">Jurídico</li>
        <li class="breadcrumb-item active" aria-current="page">Resposta Externa de Subdemanda</li>
    </ol>
@endsection

@section('content')
    @livewire('legal.field.subdemand-external-response', ['token' => $token], key('legal-subdemand-external-'.$token))
@endsection
