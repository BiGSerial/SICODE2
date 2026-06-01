@extends('layouts.legal-external-landing')

@section('content')
    @livewire('legal.field.assignment-response', ['uuid' => $uuid, 'external' => true], key('legal-assignment-response-external-'.$uuid))
@endsection
