@extends('layouts.padrao')

@section('breadcrumb')
    <nav aria-label="breadcrumb" class="py-0 my-0">
        <ol class="breadcrumb bg-light px-3 pt-3 rounded-3">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
                <li class="breadcrumb-item">Serviços</li>
                <li class="breadcrumb-item active" aria-current="page">{{ $service->service }}</li>
            </ol>
        </ol>
    </nav>
@endsection

@section('menu')
    @include('services.encerramento.menu')
@endsection

@section('content')
    @php $aba = request('aba') === 'disponiveis' ? 'disponiveis' : 'minhas'; @endphp
    @can('closure.work')
        <ul class="nav nav-tabs mb-3" id="closure-service-tabs">
            <li class="nav-item">
                <a class="nav-link {{ $aba === 'minhas' ? 'active' : '' }}" href="{{ route('services.main', ['service' => $service->uuid]) }}">Minhas atividades</a>
            </li>
            @if (app(\App\Services\Closure\ClosureSettings::class)->selfAssignEnabled())
                <li class="nav-item">
                    <a class="nav-link {{ $aba === 'disponiveis' ? 'active' : '' }}" href="{{ route('services.main', ['service' => $service->uuid, 'aba' => 'disponiveis']) }}">Atividades disponíveis</a>
                </li>
            @endif
        </ul>
    @endcan

    @if ($aba === 'disponiveis')
        @livewire('closure.work.available', key('closure-work-available'))
    @else
        @livewire('closure.work.index', key('closure-work'))
    @endif
@endsection
