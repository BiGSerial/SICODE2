{{-- Lista compacta de obras. Espera $processes; opcionais $showCompany (padrão true), $ageLabel. --}}
<div class="table-responsive"><table class="table wk-table mb-0">
    @include('quality._obra-table-head', ['showCompany' => $showCompany ?? true])
    <tbody>
    @forelse ($processes as $process)
        @include('quality._obra', ['process' => $process, 'showCompany' => $showCompany ?? true, 'ageLabel' => $ageLabel ?? 'nesta etapa', 'action' => 'Abrir'])
    @empty
        <tr><td colspan="7" class="ob-empty"><i class="ri-inbox-2-line"></i><div class="fw-bold">Nenhuma obra encontrada.</div><div class="small">Ajuste os filtros ou volte mais tarde.</div></td></tr>
    @endforelse
    </tbody>
</table></div>
