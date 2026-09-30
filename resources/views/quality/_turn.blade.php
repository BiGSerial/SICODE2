{{-- "De quem é a vez": responde, num olhar, o que o usuário logado precisa fazer ou quem está com a obra. --}}
@php
    use App\Enum\{QualityProcessState as S, QualityProcessStatus as PS};

    $seesN2   = auth()->user()->can('quality.n2');
    $state    = $process->state;
    $isDone   = $process->status === PS::COMPLETED;
    $mine     = !$isDone && ($can['assign'] || $can['review1'] || $can['review2'] || $can['retry'] || $can['contest']);
    $holder   = match ($state) {
        S::AWAITING_DESIGNER => ['Usuário', $process->CurrentDesigner?->name, 'ri-user-settings-line'],
        S::AWAITING_N1_DISPATCH, S::AWAITING_N1_REVIEW, S::N2_RETURNED => ['N1', $process->N1User?->name, 'ri-user-follow-line'],
        default => ['N2', null, 'ri-user-star-line'],
    };
    $headline = match (true) {
        $can['retry']                        => ['O encerramento desta obra ficou pendente', 'Conclua o encerramento: a atividade é encerrada e a Qualidade concluída.', 'Concluir agora', '#q-action'],
        $can['review2']                      => ['Sua decisão: aprovar ou devolver ao N1', 'O N1 aprovou esta obra. Decida agora.', 'Decidir', '#q-action'],
        $can['review1']                      => ['Sua análise: aprovar ou rejeitar', 'O usuário finalizou. Aprove para enviar ao N2 ou rejeite informando o motivo.', 'Analisar', '#q-action'],
        $can['contest'] && $can['assign']    => ['O N2 devolveu esta obra', 'Reencaminhe ao usuário (pode trocá-lo) ou questione o N2.', 'Responder ao N2', '#q-action'],
        $can['assign']                       => ['Escolha o usuário desta obra', 'Ela ainda não foi despachada. A atividade é criada na pilha do usuário escolhido.', 'Despachar', '#q-action'],
        default                              => null,
    };
@endphp

@if ($isDone)
    <div class="turn turn-done mb-3">
        <div class="turn-icon"><i class="ri-checkbox-circle-line"></i></div>
        <div class="turn-main"><div class="turn-title">Obra concluída</div><div class="turn-sub">Aprovada por {{ $process->CompletedBy?->name ?? 'N2' }} em {{ $process->completed_at?->format('d/m/Y H:i') }}. Não há mais etapas.</div></div>
    </div>
@elseif ($mine && $headline)
    <div class="turn turn-mine mb-3">
        <div class="turn-icon"><i class="ri-flashlight-line"></i></div>
        <div class="turn-main">
            <div class="turn-kicker">É a sua vez</div>
            <div class="turn-title">{{ $headline[0] }}</div>
            <div class="turn-sub">{{ $headline[1] }} <span class="ms-1">@include('quality._age', ['since' => $process->state_changed_at])</span></div>
        </div>
        <a href="{{ $headline[3] }}" class="btn btn-light btn-lg fw-bold turn-cta">{{ $headline[2] }} <i class="ri-arrow-right-line"></i></a>
    </div>
@else
    <div class="turn turn-wait mb-3">
        <div class="turn-icon"><i class="{{ $holder[2] }}"></i></div>
        <div class="turn-main">
            <div class="turn-kicker">Aguardando {{ $holder[0] }}</div>
            <div class="turn-title">{{ $holder[1] ?? ($holder[0] === 'N2' && !$seesN2 ? 'N2' : 'Equipe ' . $holder[0]) }}</div>
            <div class="turn-sub">{{ $state->nextStepFor($process->phase, $seesN2) }} <span class="ms-1">@include('quality._age', ['since' => $process->state_changed_at])</span></div>
        </div>
        <a href="#q-tabs" class="btn btn-outline-primary turn-cta" onclick="document.querySelector('[data-bs-target=&quot;#q-discussao&quot;]')?.click()"><i class="ri-chat-3-line"></i> Cobrar / comentar</a>
    </div>
@endif
