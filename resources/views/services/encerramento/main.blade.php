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
    @php
        // "Todas as Ordens" só existe (aba e acesso) quando o despacho liberou a auto-atribuição.
        $selfAssign = app(\App\Services\Closure\ClosureSettings::class)->selfAssignEnabled();
        $aba        = in_array(request('aba'), ['minhas', 'disponiveis'], true) ? request('aba') : ($selfAssign ? 'todas' : 'minhas');
        $aba        = $aba === 'disponiveis' && !$selfAssign ? 'minhas' : $aba;
    @endphp
    @can('closure.work')
        <ul class="nav nav-tabs mb-3" id="closure-service-tabs">
            @if ($selfAssign)
                <li class="nav-item">
                    <a class="nav-link {{ $aba === 'todas' ? 'active' : '' }}" href="{{ route('services.main', ['service' => $service->uuid]) }}">Todas as Ordens</a>
                </li>
            @endif
            <li class="nav-item">
                <a class="nav-link {{ $aba === 'minhas' ? 'active' : '' }}" href="{{ route('services.main', ['service' => $service->uuid, 'aba' => 'minhas']) }}">Minhas atividades</a>
            </li>
            @if (app(\App\Services\Closure\ClosureSettings::class)->selfAssignEnabled())
                <li class="nav-item">
                    <a class="nav-link {{ $aba === 'disponiveis' ? 'active' : '' }}" href="{{ route('services.main', ['service' => $service->uuid, 'aba' => 'disponiveis']) }}">Atividades disponíveis</a>
                </li>
            @endif
        </ul>
    @endcan

    @if ($aba === 'todas')
        @livewire('closure.work.all', key('closure-work-all'))
    @elseif ($aba === 'disponiveis')
        @livewire('closure.work.available', key('closure-work-available'))
    @else
        @livewire('closure.work.index', key('closure-work'))
    @endif
@endsection
