{{-- Sidebar das telas de gestão do Encerramento (Visão geral, Meta, Passivo, Gestão da Obra, Ordem):
     reaproveita o menu padrão do serviço (despacho para quem despacha, serviço para os demais). --}}
@if (($ctx ?? null) === \App\Services\Closure\ClosureNavContext::SERVICE)
    @include('services.encerramento.menu')
@elsecan('closure.dispatch')
    @include('dispatchs.encerramento.menu')
@else
    @include('services.encerramento.menu')
@endcan
