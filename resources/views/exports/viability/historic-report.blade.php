@php
    use App\Custom\Viabilitiesstatus;
    use App\Custom\Notestatus;
    use Carbon\Carbon;
@endphp

<div class="table-responsive">
    <table class="table table-sm table-condensed table-striped table-hover">
        <thead>

            <tr>
                <th scope="col" class="text-center align-middle">Nota/OV</th>
                <th scope="col" class="text-center align-middle">Ordem</th>
                <th scope="col" class="text-center align-middle">Contratado</th>
                <th scope="col" class="text-center align-middle">Recebido</th>
                <th scope="col" class="text-center align-middle">Viabilizado</th>
                <th scope="col" class="text-center align-middle">Completado em</th>
                <th scope="col" class="text-center align-middle">Rubrica</th>
                <th scope="col" class="text-center align-middle">Municipio</th>
                <th scope="col" class="text-center align-middle">Status Tacit</th>
                <th scope="col" class="text-center align-middle">Status</th>
                <th scope="col" class="text-center align-middle">Empreiteira</th>
            </tr>
        </thead>
        <tbody class="table-group-divider">
            @if ($data->count())
                @foreach ($data as $index => $viability)
                    @php
                        $dueDate = Carbon::parse($viability->sended_at)->addDays($viability->getDays() + 7);
                        $today = Carbon::now();
                        $daysDifference = $dueDate ? $today->diffInDays($dueDate) : 0;
                        $daysDifference = $dueDate && $dueDate->isBefore($today) ? -$daysDifference : $daysDifference;
                    @endphp
                    <tr wire:key="viability-{{ $viability->id }}"
                        wire:dblclick="$emitTo('partner.actions.responserviab','getInfoResponse', {{ $viability }})"
                        style="cursor: pointer;" data-bs-toggle="tooltip" data-bs-placement="left"
                        data-bs-title="Duplo Clique para mais Opções">

                        <td class="text-center align-middle">{{ $viability->Note->note }}</td>
                        <td class="text-center align-middle">
                            @if ($viability->count())
                                @foreach ($viability->Note->Orders as $order)
                                    <p class="p-0 m-1">{{ $order->ordem }}</p>
                                @endforeach
                            @endif
                        </td>
                        <td class="text-center align-middle">{{ $viability->hired ? 'SIM' : 'NÃO' }}</td>
                        <td class="text-center align-middle fw-bold">
                            {{ Carbon::parse($viability->sended_at)->format('d/m/Y') }}</td>
                        <td class="text-center align-middle fw-bold">
                            {{ isset($viability->returned_at) ? Carbon::parse($viability->returned_at)->format('d/m/Y') : '---' }}
                        </td>
                        <td class="text-center align-middle fw-bold">
                            {{ isset($viability->completed_at) ? Carbon::parse($viability->completed_at)->format('d/m/Y') : '---' }}
                        </td>
                        <td class="text-center align-middle">{{ $viability->Note->rubrica }}</td>
                        <td class="text-center align-middle">{{ $viability->Note->lexp }}</td>
                        <td class="text-center align-middle">
                            @if ($viability->Justification)
                                @if ($viability->Justification->granted && !$viability->Justification->dismissed)
                                    <span>DEFERIDO</span>
                                @elseif ($viability->Justification->dismissed && !$viability->Justification->granted)
                                    <span>INDEFERIDO</span>
                                @elseif (!$viability->Justification->dismissed && !$viability->Justification->granted)
                                    <span>EM AVALIAÇÃO</span>
                                @else
                                    <span>INCONSISTÊNCIA</span>
                                @endif
                            @else
                                @if ($viability->tacit)
                                    <span>SEM JUSTIFICATIVA</span>
                                @else
                                    ---
                                @endif
                            @endif
                        </td>
                        <td class="text-center align-middle">
                            {{ Viabilitiesstatus::status($viability->status)->status }}</td>
                        <td class="text-center align-middle">{{ $viability->Company->name }}</td>
                    </tr>
                @endforeach
            @endif
        </tbody>
    </table>
</div>
