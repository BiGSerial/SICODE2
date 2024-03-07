<table class="table table-condensed table-striped">
    <thead>
        <tr>
            {{-- <th>#</th> --}}
            <th scope="col" class="fw-bold text-center">Note</th>
            {{-- <th scope="col" class="fw-bold text-center">DOE</th>
                                <th scope="col" class="fw-bold text-center">MMGD</th> --}}
            {{-- <th scope="col" class="fw-bold text-center">Criado Em</th> --}}
            <th scope="col" class="fw-bold text-center">numPedido</th>
            <th scope="col" class="fw-bold text-center">demConjunto</th>
            <th scope="col" class="fw-bold text-center">Rubrica</th>
            <th scope="col" class="fw-bold text-center">Municipio</th>
            <th scope="col" class="fw-bold text-center">CentroTrabalho</th>
            <th scope="col" class="fw-bold text-center">Grp1</th>
            <th scope="col" class="fw-bold text-center">Grp2</th>
            <th scope="col" class="fw-bold text-center">Grp4</th>
            <th scope="col" class="fw-bold text-center">Grp5</th>
            <th scope="col" class="fw-bold text-center">Postes L</th>
            <th scope="col" class="fw-bold text-center">Status</th>
            <th scope="col" class="fw-bold text-center">Prazo Real</th>
            <th scope="col" class="fw-bold text-center">Situação</th>
        </tr>
    </thead>
    <tbody>
        @if ($lists->count())
            @foreach ($lists as $list)
                <tr>
                    {{-- <td scope="col" class="text-center fw-bold">{{ ++$index }}</td> --}}
                    <td scope="col" class="text-end fw-bold">{{ $list->note }}</td>
                    {{-- <td scope="col" class="text-center">{{ $list->doe ? 'S' : 'N' }}</td>
                <td scope="col" class="text-center">{{ $list->mmgd ? 'S' : 'N' }}</td> --}}
                    {{-- <td scope="col" class="text-center">Criado Em</td> --}}
                    <td scope="col" class="text-start">{{ $list->numPedido }}</td>
                    <td scope="col" class="text-start">{{ $list->material }}</td>
                    <td scope="col" class="text-start">{{ $list->rubrica }}</td>
                    <td scope="col" class="text-start">{{ $list->lexp }}</td>
                    <td scope="col" class="text-start">{{ $list->centerjob }}</td>
                    <td scope="col" class="text-start">{{ $list->group1 }}</td>
                    <td scope="col" class="text-start">{{ $list->group2 }}</td>
                    <td scope="col" class="text-start">{{ $list->group4 }}</td>
                    <td scope="col" class="text-start">{{ $list->group5 }}</td>
                    <td scope="col" class="text-start">{{ $list->postes }}</td>
                    <td scope="col" class="text-start">{{ $list->nstats }}</td>
                    <td scope="col"
                        class="text-center 
                    @if ($list->days_left < 0) text-bg-secondary
                    @elseif($list->days_left >= 0 && $list->days_left < 6)
                    table-danger
                    @elseif($list->days_left >= 6 && $list->days_left < 10)
                        table-warning
                    @else
                        table-success @endif
                ">
                        {{ 30 - $list->days_left }}
                    </td>
                    <td class="fw-light text-center">
                        @if ($list->pze_parecer === 'Vencido')
                            <span class="badge text-bg-danger">VENCIDO</span>
                        @elseif ($list->pze_parecer === 'Não vencido')
                            <span class="badge text-bg-success">EM PRAZO</span>
                        @else
                            <span class="badge text-bg-secondary">DESCONHECIDO</span>
                        @endif
                    </td>
                </tr>
            @endforeach
        @endif
    </tbody>
</table>
