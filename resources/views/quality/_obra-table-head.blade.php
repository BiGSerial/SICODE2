@php
    $selectable = !empty($selectable);
    $showCompany = $showCompany ?? true;
@endphp
<thead>
    <tr>
        @if ($selectable)<th class="text-center wk-col-check" title="Selecionar obras" aria-label="Selecionar obras"><i class="ri-checkbox-multiple-line"></i></th>@endif
        <th>Nota</th>
        <th>Material</th>
        <th>Localização</th>
        @if ($showCompany)<th>Empresa</th>@endif
        <th>Responsável</th>
        <th>Progresso</th>
        <th class="text-end">Prazo / ação</th>
    </tr>
</thead>
