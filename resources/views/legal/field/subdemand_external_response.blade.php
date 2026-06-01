@extends('layouts.legal-external-landing')

@section('content')
    @livewire('legal.field.subdemand-external-response', ['token' => $token], key('legal-subdemand-external-'.$token))
@endsection
