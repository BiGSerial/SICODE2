@php
    use App\Custom\Notestatus;
@endphp
<table class="table table-sm table-striped table-condensed">
    <thead class="table-dark">
        <tr>

            {{-- @can('management')
                <th scope="col" class="fw-bold">Note</th>
            @endcan --}}
            <th scope="col" class="fw-bold text-center">Note</th>
            <th scope="col" class="fw-bold text-center">DOE</th>
            <th scope="col" class="fw-bold text-center">MMGD</th>
            <th scope="col" class="fw-bold text-center">Criado Em</th>
            <th scope="col" class="fw-bold text-center">numPedido</th>
            <th scope="col" class="fw-bold text-center">Rubrica</th>
            <th scope="col" class="fw-bold text-center">Municipio</th>
            <th scope="col" class="fw-bold text-center">Grp1</th>
            <th scope="col" class="fw-bold text-center">Grp2</th>

            <th scope="col" class="fw-bold text-center">Grp4</th>
            <th scope="col" class="fw-bold text-center">Grp5</th>
            <th scope="col" class="fw-bold text-center">Postes L</th>
            <th scope="col" class="fw-bold text-center">RetornoInterno</th>
            <th scope="col" class="fw-bold text-center">Status</th>
            <th scope="col" class="fw-bold text-center">DiasStatus</th>
            <th scope="col" class="fw-bold text-center">Prazo Real</th>
            <th scope="col" class="fw-bold text-center">Situação</th>
            <th scope="col" class="fw-bold text-center">Usuario</th>
            <th scope="col" class="fw-bold text-center">Empresa</th>
            <th scope="col" class="fw-bold text-center">Status</th>
            <th scope="col" class="fw-bold text-center">Dispatch_at</th>
            <th scope="col" class="fw-bold text-center">Completed_at</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($lists as $list)
            @php
                $block = null;
                $lastUser = '';
                $lastCompany = '';

                $productions = $list->Productions->where('service_id', $service);

                if ($productions && $productions->count()) {
                    $company = $productions->last()->Company ? $productions->last()->Company->name : 'Desconhecido';
                    $user = $productions->last()->User ? $productions->last()->User->name : 'Desconhecido';
                    $status = Notestatus::status($productions->last()->status)->status;
                    $Completed = $productions->last()->completed_at;
                    $dispatch = $productions->last()->dispatch_at;
                } else {
                    $company = '';
                    $user = '';
                    $status = '';
                    $Completed = '';
                    $dispatch = '';
                }

            @endphp

            <tr
                class="align-middle
                @if ($block) @if ($production->status == 1)
                table-warning
                @elseif ($production->status == 2)
                table-primary
                @elseif ($production->status == 5)
                table-success
                @else
                table-primary @endif @endif">

                {{-- @can('management')
                    <td class="fw-bold copy-text" data-value="{{ $list->note }}">{{ $list->note }}
                    </td>
                @endcan --}}
                <td class="fw-bold copy-text" data-value="{{ $list->note }}">
                    {{ $list->note }}
                </td>
                <td class="fw-bold text-success text-center">
                    @if ($list->doe)
                        <i class="ri-checkbox-circle-line"></i>
                    @endif
                </td>
                <td class="fw-bold text-danger text-center">
                    {{ $list->mmgd ? 'MMGD' : '' }}
                </td>
                <td class="fw-light text-center">{{ date('d/m/Y', strToTime($list->dt_created)) }}
                </td>
                <td class="fw-light text-center">{{ mb_strtoupper($list->numPedido) }}</td>
                <td class="fw-light text-center">{{ $list->rubrica }}</td>
                <td class="fw-light text-center">{{ $list->lexp }}</td>
                <td class="fw-light text-center">{{ $list->group1 }}</td>
                <td class="fw-light text-center">{{ $list->group2 ? $list->group2 : '_____' }}
                </td>
                <td class="fw-light text-center">{{ $list->group4 ? $list->group4 : '_____' }}
                </td>
                <td class="fw-light text-center">{{ $list->group5 ? $list->group5 : '_____' }}
                </td>
                <td class="fw-light text-center">{{ $list->postes ? $list->postes : '_____' }}
                </td>
                <td class="fw-light text-center">{{ $list->d5 ? $list->postes : '_____' }}
                </td>

                @if ($list->type_note != 1)
                    <td class="fw-light text-center">{{ $list->nstats }} </td>
                @else
                    <td class="fw-light text-center">{{ $list->centerjob }} <span class="text-danger"
                            style="font-size: 8px;">{{ $list->nstats }}</span></td>
                @endif
                <td class="fw-light text-center">{{ $list->dt_status ? $list->dt_status->diffInDays() : '_____' }}
                </td>
                <td scope="col"
                    class="text-center
                @if ($list->days_left < 0) text-bg-secondary
                @elseif($list->days_left >= 0 && $list->days_left < 6)
                table-danger
                @elseif($list->days_left >= 6 && $list->days_left < 10)
                    table-warning
                @else
                    table-success @endif
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
                <td class="fw-light text-center">{{ $user }}</td>
                <td class="fw-light text-center">{{ $company }}</td>
                <td class="fw-light text-center">{{ $status }}</td>
                <td class="fw-light text-center">{{ $dispatch }}</td>
                <td class="fw-light text-center">{{ $Completed }}</td>



            </tr>
        @endforeach
    </tbody>
</table>
