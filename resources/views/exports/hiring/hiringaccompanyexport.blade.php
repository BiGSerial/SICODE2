@php
    use Carbon\Carbon;

@endphp


@if ($lists->count())

    <table class="table table-sm table-striped table-condensed">
        <thead class="table-dark">
            <tr>

                <th scope="col" class="fw-bold">Ordem</th>
                <th scope="col" class="fw-bold">Nota</th>
                <th scope="col" class="fw-bold">Files</th>
                <th scope="col" class="fw-bold">Rubrica</th>
                <th scope="col" class="fw-bold">Municipio</th>
                <th scope="col" class="fw-bold">Empreitaira</th>
                <th scope="col" class="fw-bold">Engenheiro</th>
                <th scope="col" class="fw-bold">Dt Envio</th>
                <th scope="col" class="fw-bold">Est Retorno</th>
                <th scope="col" class="fw-bold">Real Retorno</th>
                <th scope="col" class="fw-bold">Situação</th>

            </tr>
        </thead>
        <tbody>
            @foreach ($lists as $list)
                @php
                    $dueDate = $list->sended_at ? Carbon::parse($list->sended_at)->addDays(7) : null;
                    $today = Carbon::now();
                    $daysDifference = $dueDate ? $today->diffInDays($dueDate) : null;

                    if ($dueDate) {
                        $totalDaysDifference = $dueDate->diffInMinutes($list->sended_at);
                        $elapsedDaysDifference = Carbon::parse($list->sended_at)->diffInMinutes($today);

                        $percentElapsed = ($elapsedDaysDifference / $totalDaysDifference) * 100;
                    } else {
                        $percentElapsed = 0;
                    }
                @endphp

                <tr>
                    <td class="fw-bold">{{ $list->Order->ordem }}</td>
                    <td>{{ $list->Order->Note->note }}</td>
                    <td>
                        {{ $list->Order->Note->Files->count() ? 'SIM' : 'NÃO' }}
                    </td>
                    <td>{{ $list->Order->Note->rubrica }}</td>
                    <td>{{ $list->Order->Note->lexp }}</td>
                    <td>{{ $list->Company->name }}</td>
                    <td>{{ $list->Engineer->name }}</td>
                    <td>{{ $list->sended_at ? Carbon::parse($list->sended_at)->format('d/m/Y') : '---' }}
                    </td>
                    <td>
                        {{ $dueDate ? $dueDate->format('d/m/Y') : '---' }}
                    </td>
                    <td>{{ $list->returned_at ? Carbon::parse($list->returned_at)->format('d/m/Y') : '---' }}
                    </td>
                    <td>
                        {{-- Componente Blade para Exibir status baseado nos Booleand. Precisa do Array de Viability --}}
                        {{-- <x-hiring.status_viability :status="$list" /> --}}

                        @if ($list->completed)
                            <span class="badge text-bg-success">Contratado</span>
                        @elseif ($list->approved && !$list->tacit)
                            <span class="badge text-bg-success">Aprovado</span>
                        @elseif ($list->approved && $list->tacit)
                            <span class="badge text-bg-warning">Aprovação Tácita</span>
                        @elseif ($list->rejected && !$list->canceled)
                            <span class="badge text-bg-danger">Rejeitado</span>
                        @elseif ($list->canceled)
                            <span class="badge text-bg-secondary">Cancelado</span>
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endif
