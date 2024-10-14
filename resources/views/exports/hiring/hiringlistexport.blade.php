@php
    use Carbon\Carbon;
    use App\Helpers\DaysLeft;
    use App\Custom\Viabilitiesstatus;
@endphp

@if ($lists->count())




    <table class="table table-sm table-striped table-condensed">
        <thead class="table-dark">
            <tr>

                <th scope="col" class="fw-bold">Ordem</th>
                <th scope="col" class="fw-bold">Nota</th>
                <th scope="col" class="fw-bold">PEP</th>
                <th scope="col" class="fw-bold">Tipo</th>
                <th scope="col" class="fw-bold">Projeto Anexado</th>
                <th scope="col" class="fw-bold">Rubrica</th>
                <th scope="col" class="fw-bold">denConjunto</th>
                <th scope="col" class="fw-bold">Municipio</th>
                <th scope="col" class="fw-bold">Status Ordem</th>
                <th scope="col" class="fw-bold">Status OV/NOTA</th>
                <th scope="col" class="fw-bold">Status OP10</th>
                <th scope="col" class="fw-bold">Centro OP10</th>
                <th scope="col" class="fw-bold">Prazo Restante</th>
                <th scope="col" class="fw-bold">Status</th>


            </tr>
        </thead>
        <tbody>
            @foreach ($lists as $list)
                @php
                    $days_left = new DaysLeft($list->Note);
                    $color = '';

                    $viability = '';
                    $status = '';
                    $block = false;
                    $waiting = false;

                    if ($list->Note->Viabilities->count()) {
                        if ($list->Note->Viabilities->count()) {
                            $viability = $list->Note->Viabilities->last();

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

                    if ($list->Note->Waitings->count() && $list->Note->Waitings->where('complete', false)->count()) {
                        $block = true;
                        $waiting = true;
                    }
                @endphp

                <tr>

                    <td class="fw-bold">{{ $list->ordem }}</td>
                    <td>{{ $list->Note->note }}</td>
                    <td>{{ $list->pep }}</td>
                    <td>{{ $list->Note->type_note == 2 ? 'OV' : 'NOTA' }}</td>
                    <td>
                        {{ $list->Note->Files->filter(function ($file) {
                                return str_starts_with($file->file_name, 'PROJETO');
                            })->count()
                            ? 'SIM'
                            : 'NÃO' }}
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
                        {{ $days_left->getLastDate() }}
                    </td>
                    <td>
                        @if ($block)
                            @if (!$waiting)
                                <p class="py-0 my-0">
                                    <span
                                        class="badge text-wrap aling-middle {{ Viabilitiesstatus::status($list->Note->Viabilities->last()->status)->colorbg }}"
                                        style="width: 6rem;">{{ mb_strToUpper(Viabilitiesstatus::status($list->Note->Viabilities->last()->status)->status) }}</span>
                                </p>
                            @else
                                ---
                            @endif
                        @elseif ($waiting)
                            <span class="badge text-wrap text-bg-danger">EM ESPERA (RI)</span>
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endif
