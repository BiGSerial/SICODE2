{{-- Acumulador: Final + Parcial + D5 mesclados. Linha = etapa atual · coluna = dias úteis desde o informe. --}}
<div class="ppr-card p-3 mb-3">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-2 gap-2">
        <div>
            <h5 class="mb-0">Informes por etapa e idade</h5>
            <small class="text-muted">Colunas = dias úteis desde a data do informe (concluídos: duração total). Clique numa célula para listar os informes.</small>
        </div>
        @if ($cell_stage)
            <button type="button" class="btn btn-outline-secondary btn-sm" wire:click="clearCell"><i class="ri-close-line"></i> Limpar seleção</button>
        @endif
    </div>

    <div class="table-responsive">
        <table class="ppr-matrix">
            <thead>
                <tr>
                    <th class="stage">Etapa atual</th>
                    @foreach ($matrix['days'] as $d)
                        <th>{{ $d >= \App\Services\Reports\PostWorkProcessReportService::MATRIX_MAX_DAY ? $d . '+' : $d }}</th>
                    @endforeach
                    <th class="total">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($matrix['rows'] as $stageKey => $stageRow)
                    <tr>
                        <th class="stage">
                            {{ $stageRow['label'] }}
                            <small class="text-muted d-block fw-normal">prazo até {{ $stageRow['limit'] }}d</small>
                        </th>
                        @foreach ($matrix['days'] as $d)
                            @php $cell = $stageRow['cells'][$d]; @endphp
                            @if ($cell['count'] === 0)
                                <td class="empty">·</td>
                            @else
                                <td class="cell {{ $cell['status'] }} {{ $cell_stage === $stageKey && $cell_day === $d ? 'selected' : '' }}"
                                    wire:click="pickCell('{{ $stageKey }}', {{ $d }})"
                                    title="{{ collect($cell['by_type'])->map(fn ($n, $t) => "$t: $n")->implode(' · ') }}">
                                    {{ $cell['count'] }}
                                </td>
                            @endif
                        @endforeach
                        <td class="total">{{ $stageRow['total'] }}</td>
                    </tr>
                @endforeach
                <tr class="grand">
                    <th class="stage" style="color:#fff;background:#0f172a;border-radius:.4rem;padding:.4rem .6rem">Total geral</th>
                    @foreach ($matrix['days'] as $d)
                        <td>{{ $matrix['col_totals'][$d] ?: '' }}</td>
                    @endforeach
                    <td>{{ $matrix['total'] }}</td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="ppr-legend mt-3">
        <span><i style="background:#bbf7d0"></i>Dentro do prazo da etapa</span>
        <span><i style="background:#fde68a"></i>No último dia do prazo</span>
        <span><i style="background:#fca5a5"></i>Além do prazo</span>
    </div>
</div>
