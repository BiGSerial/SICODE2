{{-- Lista rica de obras (cartões). Espera $processes; opcionais $showCompany (padrão true), $ageLabel. --}}
<div class="ob-list">
    @forelse ($processes as $process)
        @include('quality._obra', ['process' => $process, 'showCompany' => $showCompany ?? true, 'ageLabel' => $ageLabel ?? 'nesta etapa', 'action' => 'Abrir'])
    @empty
        <div class="ob-empty"><i class="ri-inbox-2-line"></i><div class="fw-bold">Nenhuma obra encontrada.</div><div class="small">Ajuste os filtros ou volte mais tarde.</div></div>
    @endforelse
</div>
