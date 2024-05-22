@php
    use Carbon\Carbon;
@endphp

@if ($lists->count())




    <table class="table table-sm table-striped table-condensed">
        <thead class="table-dark">
            <tr>

                <th scope="col" class="fw-bold">Ordem</th>
                <th scope="col" class="fw-bold">Nota</th>
                <th scope="col" class="fw-bold">PEP</th>
                <th scope="col" class="fw-bold">Tipo</th>
                <th scope="col" class="fw-bold">Files</th>
                <th scope="col" class="fw-bold">Rubrica</th>
                <th scope="col" class="fw-bold">denConjunto</th>
                <th scope="col" class="fw-bold">Municipio</th>
                <th scope="col" class="fw-bold">Status Ordem</th>
                <th scope="col" class="fw-bold">Status OV/NOTA</th>
                <th scope="col" class="fw-bold">Status OP10</th>
                <th scope="col" class="fw-bold">Centro OP10</th>
                <th scope="col" class="fw-bold">Prazo Restante</th>


            </tr>
        </thead>
        <tbody>
            @foreach ($lists as $list)
                @php
                    $days_left = '';

                    // Dias Restantes
                    if ($list->type_note == 1) {
                        if ($list->mesalization && $list->mesalization != 'erro') {
                            preg_match('/\d+\/\d+/', $list->mesalization, $matches);

                            if (!empty($matches)) {
                                [$mes, $ano] = explode('/', $matches[0]);

                                if ($mes >= 1) {
                                    $data = "{$ano}-{$mes}-28 23:59:59";

                                    $hoje = Carbon::now();

                                    $dataCarbon = Carbon::createFromFormat('Y-m-d H:i:s', $data);

                                    $days_left = $hoje->diffInDays($dataCarbon, false);
                                } else {
                                    $data = "{$ano}-12-28 23:59:59";

                                    $hoje = Carbon::now();

                                    $dataCarbon = Carbon::createFromFormat('Y-m-d H:i:s', $data);

                                    $days_left = $hoje->diffInDays($dataCarbon, false);
                                }
                            }
                        }
                    } elseif ($list->type_note == 2) {
                        $days_left = $list->days_left;
                    }

                    $color = '';

                @endphp

                <tr>

                    <td class="fw-bold">{{ $list->ordem }}</td>
                    <td>{{ $list->Note->note }}</td>
                    <td>{{ $list->pep }}</td>
                    <td>{{ $list->Note->type_note == 2 ? 'OV' : 'NOTA' }}</td>
                    <td>
                        {{ $list->Note->Files->count() ? 'SIM' : 'NÃO' }}
                    </td>
                    <td>{{ $list->Note->rubrica }}</td>
                    <td>{{ $list->denConjunto }}</td>
                    <td>{{ $list->Note->lexp }}</td>
                    <td>{{ $list->statusSist }}
                    </td>
                    <td>
                        @if ($list->Note->type_note == 1)
                            {{ $list->Note->centerjob }}
                        @elseif($list->Note->type_note == 2)
                            {{ $list->Note->nstats }}
                        @else
                            ---
                        @endif
                    </td>
                    <td>
                        {{-- @if ($list->Operations->count())
                                            @dump($list->Operations->where('operacao', '0010'))
                                        @endif --}}
                        {{ $list->Operations->count() ? ($list->Operations->where('operacao', '0010')->first() ? $list->Operations->where('operacao', '0010')->first()->status : '___') : '---' }}
                    </td>
                    <td> {{ $list->Operations->count() ? ($list->Operations->where('operacao', '0010')->first() ? $list->Operations->where('operacao', '0010')->first()->cenTrab : '___') : '---' }}
                    </td>
                    <td class="text-center align-middle">
                        {{ Carbon::now()->addDays($days_left)->format('d/m/Y') }}
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endif
