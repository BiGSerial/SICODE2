@extends('layouts.legal-external-landing')

@section('content')
    @livewire('legal.field.assignment-response', ['assignment_id' => $assignment_id, 'external' => true], key('legal-assignment-response-external-'.$assignment_id))
@endsection
