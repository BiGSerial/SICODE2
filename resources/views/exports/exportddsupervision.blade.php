@php
    use Carbon\Carbon;
    use App\Helpers\DaysLeft;
    use App\Custom\Notestatus;

@endphp

<table>
    <thead>
        <tr>
            <th scope="col" class="fw-bold text-center">Note</th>
            <th scope="col" class="fw-bold text-center">Ordem</th>
            <th scope="col" class="fw-bold text-center">DD</th>
            <th scope="col" class="fw-bold text-center">MMGD</th>
            <th scope="col" class="fw-bold text-center">Postes</th>
            <th scope="col" class="fw-bold text-center">Informado Em</th>
            <th scope="col" class="fw-bold text-center">numPedido</th>
            <th scope="col" class="fw-bold text-center">Rubrica</th>
            <th scope="col" class="fw-bold text-center">Municipio</th>
            <th scope="col" class="fw-bold text-center">Grp1</th>
            <th scope="col" class="fw-bold text-center">Grp2</th>
            <th scope="col" class="fw-bold text-center">Grp4</th>
            <th scope="col" class="fw-bold text-center">Grp5</th>
            <th scope="col" class="fw-bold text-center">Fiscalizações</th>
            <th scope="col" class="fw-bold text-center">Status</th>
            <th scope="col" class="fw-bold text-center">Dias Informe</th>
            <th scope="col" class="fw-bold text-center">Situação</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($exports as $export)
            @php
                $block = null;
                $lastUser = '';
                $lastCompany = '';

                $count = $export->Productions->where('service_id', $service->uuid)->where('noinconsistency', false);

                $count2 = $export->Productions->where('service_id', $service->uuid)->where('completed', true);

                if ($count2->count()) {
                    // $lastUser = $export->Productions
                    //     ->where('service_id', $service->uuid)
                    //     ->where('completed', true)
                    //     ->last()->User->name;

                    $lastUser = $count2->last()->User->name;

                    $lastUser = explode(' ', $lastUser);
                    $lastUser = $lastUser[0] . ' ' . end($lastUser);
                }

                if ($count->count()) {
                    $production = $count->load('Company')->last();

                    if (isset($production->Company->name)) {
                        $lastCompany = explode(' ', $production->Company->name);
                        $lastCompany = mb_strtoupper($lastCompany[0]);
                    } else {
                        $lastCompany = 'Desconhecido';
                    }

                    if ($production->dt_note == $export->dt_status || !$production->confirmed) {
                        $block = true;
                    }

                    // $block = true;

                    // $chave = array_search($export->id, $selected);

                    // if ($chave !== false) {
                    //     unset($selected[$chave]);
                    //     $selected = $selected;
                    // }
                }

            @endphp
            <tr>
                <td class="fw-bold copy-text text-center" data-value="{{ $export->note }}">
                    {{ $export->note }}
                </td>
                <td class="text-center">
                    @if ($export->WorkForm)
                        @foreach ($export->WorkForm->Orders as $order)
                            <p class="my-0 py-0">{{ $order->ordem }}</p>
                        @endforeach
                    @else
                        ---
                    @endif
                </td>
                <td class="fw-bold text-danger text-center">
                    {{ $export->Wpas->isNotEmpty() ? $export->Wpas->last()->dd : '' }}
                </td>
                <td class="fw-bold text-danger text-center">
                    {{ $export->mmgd ? 'MMGD' : '' }}
                </td>
                <td class="fw-bold text-primary text-center">
                    {{ isset($export->postes) ? $export->postes : '---' }}
                </td>
                <td class="fw-light text-center">
                    {{ $export->WorkForm ? Carbon::parse($export->WorkForm->informed_at)->format('d/m/Y H:i:s') : '---' }}
                </td>
                <td class="fw-light text-center">{{ mb_strtoupper($export->numPedido) }}</td>
                <td class="fw-light text-center">{{ $export->rubrica }}</td>
                <td class="fw-light text-center">
                    @if (!empty($export->lexp))
                        {{ $export->lexp }}
                    @else
                        <span tabindex="1" data-bs-toggle="popover" data-bs-trigger="hover focus"
                            data-bs-placement="top" data-bs-title="Editar Município"
                            data-bs-content="Clique para editar o município faltante para esta nota.">
                            <button class="btn btn-sm btn-secondary"
                                wire:click.prevent="$emit('editMunicipio', '{{ $export->id }}')">Edit</button>
                        </span>
                    @endif

                </td>
                <td class="fw-light text-center">{{ $export->group1 }}</td>
                <td class="fw-light text-center">{{ $export->group2 ? $export->group2 : '_____' }}
                </td>
                <td class="fw-light text-center">{{ $export->group4 ? $export->group4 : '_____' }}
                </td>
                <td class="fw-light text-center">{{ $export->group5 ? $export->group5 : '_____' }}
                </td>



                <td class="fw-light text-center" tabindex="2" data-bs-toggle="popover" data-bs-trigger="hover focus"
                    data-bs-placement="top" data-bs-title="Levantamentos Realizados"
                    data-bs-content="Informa se esta NOTA/OV específica já passou por este estatus antes. Caso afirmativo, é exibido a quantidade de vezes e a última pessoa a encerrar esta NOTA/OV neste SERVIÇO.">
                    @if ($count2->count())
                        <span class="badge text-bg-dark">{{ $count2->count() }}</span><br>
                        {{ $lastUser }}
                    @else
                        --
                    @endif
                </td>

                <td class="fw-light text-center">
                    {{ $export->nstats }}<br><span>{{ $export->centerjob }}</span></td>
                {{-- <td class="fw-light text-center">{{ $export->pze }}</td> --}}
                @php
                    $days_left = $export->WorkForm
                        ? Carbon::parse($export->WorkForm->informed_at)->diffInDays(Carbon::now(), false)
                        : 0;
                @endphp
                <td scope="col"
                    class="text-center
                        @if ($days_left <= 20) text-bg-success
                        @elseif ($days_left >= 28)
                        text-bg-danger
                        @else
                        text-bg-warning @endif
                    "
                    tabindex="0" data-bs-toggle="popover" data-bs-trigger="hover focus" data-bs-placement="top"
                    data-bs-title="Prazo Real"
                    data-bs-content="
                        <p>Os prazos contados já foram expurgado os tempos em status não contabilizáveis.</p>
                        <span class='fs-4 text-success'>&#9632;</span> 10> DIAS PARA VENCER <br>
                        <span class='fs-4 text-warning'>&#9632;</span> 10< DIAS PARA VENCER <br>
                        <span class='fs-4 text-danger'>&#9632;</span> 5< DIAS PARA VENCER <br>
                        <span class='fs-4 text-secondary'>&#9632;</span> VENCIDO <br>
                        ">
                    {{ $days_left }}
                </td>
                <td class="fw-light text-center">
                    @if ($export->pze_parecer === 'Vencido')
                        <span class="badge text-bg-danger">VENCIDO</span>
                    @elseif ($export->pze_parecer === 'Não vencido')
                        <span class="badge text-bg-success">EM PRAZO</span>
                    @else
                        <span class="badge text-bg-secondary">DESCONHECIDO</span>
                    @endif
                </td>
            </tr>
        @endforeach
    </tbody>
</table>
