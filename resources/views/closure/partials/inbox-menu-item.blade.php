{{-- Caixa do Encerramento dentro das atividades Medição (pagamento) e Fiscalização, com dois selos:
     azul = Ordens em acompanhamento (só visão); vermelho = pendências despachadas para resolver (ação).
     Uso: @include('closure.partials.inbox-menu-item', ['inboxRoute' => 'services.closure_inbox' | 'dispatch.closure_inbox']) --}}
@php
    $inboxArea = \App\Services\Closure\ClosureArea::forFolder((string) ($service->folder ?? ''));
@endphp
@if ($inboxArea && auth()->user()?->can('closure.inbox.' . strtolower($inboxArea)))
    <li class="nav-item">
        <a class="nav-link collapsed" data-bs-target="#closure-inbox-nav" data-bs-toggle="collapse" href="#">
            <i class="bi bi-menu-button-wide"></i><span>ENCERRAMENTO</span><i class="bi bi-chevron-down ms-auto"></i>
        </a>
        <ul id="closure-inbox-nav" class="nav-content collapse show" data-bs-parent="#sidebar-nav">
            <div class="border-start border-3 mb-1 py-0">
                <li>
                    <a href="{{ route($inboxRoute, ['service' => $service->uuid]) }}" class="nav-item text-white fw-normal">
                        <i class="bi bi-eye fs-5 edp-text-verde-dark fw-normal"></i><span>EM ACOMPANHAMENTO</span>
                        @livewire('components.count.closure-inbox-count', ['area' => $inboxArea, 'kind' => 'accompanying'], key('closure-inbox-acc-' . $inboxArea . '-' . $service->uuid))
                    </a>
                </li>
                <li>
                    <a href="{{ route($inboxRoute, ['service' => $service->uuid, 'tab' => 'dispatched']) }}" class="nav-item text-white fw-normal">
                        <i class="bi bi-send-exclamation fs-5 edp-text-verde-dark fw-normal"></i><span>DESPACHADAS PARA RESOLVER</span>
                        @livewire('components.count.closure-inbox-count', ['area' => $inboxArea, 'kind' => 'dispatched'], key('closure-inbox-dsp-' . $inboxArea . '-' . $service->uuid))
                    </a>
                </li>
            </div>
        </ul>
    </li>
@endif
