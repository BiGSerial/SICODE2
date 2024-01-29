@php
    use Carbon\Carbon;
    use App\Custom\Notestatus;
@endphp
<table class="table table-sm table-striped table-condensed">
    <thead class="table-dark">
        <tr>

            <th scope="col" class="fw-bold text-center">Note</th>
            <th scope="col" class="fw-bold text-center">DOE</th>
            <th scope="col" class="fw-bold text-center">MMGD</th>
            <th scope="col" class="fw-bold text-center">Grp2</th>
            <th scope="col" class="fw-bold text-center">Rubrica</th>
            <th scope="col" class="fw-bold text-center">Centro</th>
            <th scope="col" class="fw-bold text-center">Municipio</th>
            <th scope="col" class="fw-bold text-center">Zona</th>
            <th scope="col" class="fw-bold text-center">Descrição</th>
            <th scope="col" class="fw-bold text-center">Empresa</th>
            <th scope="col" class="fw-bold text-center">Usuário</th>
            <th scope="col" class="fw-bold text-center">Dias Despachado</th>
            <th scope="col" class="fw-bold text-center">Dias Atribuido</th>
            <th scope="col" class="fw-bold text-center">Prazo Real</th>
            <th scope="col" class="fw-bold text-center">Status</th>

        </tr>
    </thead>
    <tbody>
        @foreach ($lists as $list)
            <tr
                class="align-middle 
                @if ($list->block) table-primary @endif 
                
                ">

                <td class="fw-bold @if ($list->priority) text-danger fw-bold @endif">
                    {{ $list->Note->note }}
                    <span class="copy-text" data-value="{{ $list->Note->note }}" style="cursor: pointer;"> <i
                            class="ri-file-copy-line"></i></span>

                    @if ($list->priority)
                        <i class="ri-alert-fill text-danger align-middle"
                            wire:click.prevent="$emit('infoPriority', '{{ $list->id }}')"
                            style="cursor: pointer;"></i>
                    @endif
                </td>
                <td class="fw-bold text-success text-center">
                    @if ($list->Note->doe)
                        SIM
                    @endif
                </td>
                <td class="fw-light text-center @if ($list->priority) text-danger fw-bold @endif">
                    {{ $list->Note->mmgd ? 'MMGD' : '' }}</td>
                <td class="fw-light text-center @if ($list->priority) text-danger fw-bold @endif">
                    {{ $list->Note->group2 }}</td>

                <td class="fw-light text-center @if ($list->priority) text-danger fw-bold @endif">
                    {{ $list->Note->rubrica }}</td>
                <td class="fw-light text-center @if ($list->priority) text-danger fw-bold @endif">
                    {{ $list->centerjob }}</td>
                <td class="fw-light text-center @if ($list->priority) text-danger fw-bold @endif">
                    {{ $list->Note->lexp }}</td>
                <td class="fw-light text-center @if ($list->priority) text-danger fw-bold @endif">
                    {{ $list->Note->group1 }}</td>
                <td class="fw-light text-center @if ($list->priority) text-danger fw-bold @endif">
                    @if ($list->Note->rubrica == 'BT Zero')
                        {{ $list->Note->numPedido }}
                    @else
                        {{ $list->Note->material }}
                    @endif
                </td>

                <td class="fw-light text-center @if ($list->priority) text-danger fw-bold @endif">

                    {{ $list->Company ? explode(' ', $list->Company->name)[0] : '-' }}</td>
                <td class="fw-light text-center @if ($list->priority) text-danger fw-bold @endif">
                    @php
                        $nome = $list->User ? explode(' ', $list->User->name) : '----';
                        if (is_array($nome)) {
                            $nome = $nome[0] . ' ' . end($nome);
                        }
                    @endphp
                    {{ $nome }}</td>
                <td class="fw-light text-center @if ($list->priority) text-danger fw-bold @endif">
                    {{ Carbon::now()->diffInDays(Carbon::parse($list->dispatch_at)->format('Y-m-d')) }}
                </td>
                <td class="fw-light text-center @if ($list->priority) text-danger fw-bold @endif">
                    {{ Carbon::now()->diffInDays(Carbon::parse($list->att_at)->format('Y-m-d')) }}
                </td>
                <td scope="col"
                    class="text-center 
                @if ($list->Note->days_left < 0) text-bg-secondary
                @elseif($list->Note->days_left >= 0 && $list->Note->days_left < 6)
                table-danger
                @elseif($list->Note->days_left >= 6 && $list->Note->days_left < 10)
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
                    {{ 30 - $list->Note->days_left }}
                </td>
                {{-- <td class="fw-light text-center">
                    <span
                        class="badge {{ Notestatus::status($list->status)->colorbg }}">{{ Notestatus::status($list->status)->status }}</span>
                </td> --}}
                <td class="fw-light text-center">
                    {{ Notestatus::status($list->status)->status }}

                </td>


            </tr>
        @endforeach
    </tbody>
</table>
