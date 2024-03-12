@if ($lists->count())




    <table class="table table-sm table-striped table-condensed">
        <thead class="table-dark">
            <tr>

                <th scope="col" class="fw-bold">Ordem</th>
                <th scope="col" class="fw-bold">Nota</th>
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
                <th scope="col" class="fw-bold">Situação</th>

            </tr>
        </thead>
        <tbody>
            @foreach ($lists as $list)
                @php
                    $block = false;
                    $viability = '';
                    $status = '';

                    if ($list->Viabilities->count()) {
                        if ($list->Viabilities->Where('completed', false)->count()) {
                            $viability = $list->Viabilities->Where('completed', false)->last();

                            $block = true;

                            if ($viability->approved) {
                                $status = [
                                    'info' => 'Aprovado',
                                    'color_text' => 'text-bg-succes',
                                    'table' => 'table-success',
                                ];
                            } elseif ($viability->rejected && !$viability->approved) {
                                $status = [
                                    'info' => 'Rejeitado',
                                    'color_text' => 'text-bg-danger',
                                    'table' => 'table-danger',
                                ];
                            } elseif ($viability->canceled && !$viability->rejected && !$viability->approved) {
                                $status = [
                                    'info' => 'Cancelado',
                                    'color_text' => 'text-bg-secondary',
                                    'table' => 'table-secondary',
                                ];
                            } else {
                                $status = [
                                    'info' => 'Em Viabilidade',
                                    'color_text' => 'text-bg-primary',
                                    'table' => 'table-primary',
                                ];
                            }
                        }
                    }
                @endphp

                <tr>

                    <td class="fw-bold">{{ $list->ordem }}</td>
                    <td>{{ $list->Note->note }}</td>
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
                    <td>
                        {{ $list->Note->days_left }}</td>
                    <td>
                        @if ($block)
                            <span>{{ $status['info'] }}</span>
                        @endif
                    </td>

                </tr>
            @endforeach
        </tbody>
    </table>
@endif
