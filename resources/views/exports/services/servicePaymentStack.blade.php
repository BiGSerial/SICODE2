@php
    use Carbon\Carbon;
    use App\Custom\Notestatus;
    use App\Helpers\DaysLeft;
@endphp
<table class="table table-sm table-striped table-condensed table-hover">
    <thead class="table-dark">
        <tr>
            <th scope="col" class="fw-bold text-center">Note</th>
            <th scope="col" class="fw-bold text-center">Ordens</th>
            <th scope="col" class="fw-bold text-center">Qtd Equipamentos</th>
            <th scope="col" class="fw-bold text-center">Empreiteira</th>
            <th scope="col" class="fw-bold text-center">Municipio</th>
            <th scope="col" class="fw-bold text-center">Descrição</th>
            <th scope="col" class="fw-bold text-center">Dias Atribuido</th>
            <th scope="col" class="fw-bold text-center">Na Pilha</th>
            <th scope="col" class="fw-bold text-center">PrazoTotal</th>
            <th scope="col" class="fw-bold text-center">Status</th>

        </tr>
    </thead>
    <tbody>
        @foreach ($lists->sortBy([['priority', 'desc'], ['Note.days_left', 'asc']]) as $list)
            @php
                $daysLeft = new DaysLeft($list->Note);
            @endphp
            <tr wire:key="work-{{ $list->id }}"
                wire:dblclick="$emitTo('partner.show.show-work-form', 'show_form', {{ $list->Note->WorkForm }})"
                class="align-middle text-center align-middle @if ($list->block) table-primary @endif">
                <td class="fw-bold @if ($list->priority) text-danger fw-bold @endif">
                    {{ $list->Note->note }}
                    <span class="copy-text" data-value="{{ $list->Note->note }}" style="cursor: pointer;" tabindex="0"
                        data-bs-toggle="popover" data-bs-trigger="hover focus" data-bs-placement="top"
                        data-bs-content="Copiar Número da Nota"> <i class="ri-file-copy-line"></i></span>

                    @if ($list->priority)
                        <i class="ri-alert-fill align-middle"
                            wire:click.prevent="$emit('infoPriority', '{{ $list->id }}')" style="cursor: pointer;"
                            tabindex="0" data-bs-toggle="popover" data-bs-trigger="hover focus"
                            data-bs-placement="top" data-bs-title="Exibir Prioridade"
                            data-bs-content="Clique para visualizar a informação da prioridade desta nota/ov."></i>
                    @endif
                </td>

                <td class="fw-light text-center align-middle">
                    @if (isset($list->Note->WorkForm) && $list->Note->WorkForm->Orders->count())
                        @foreach ($list->Note->WorkForm->Orders as $order)
                            <p class="my-0 py-0">{{ $order->ordem }}</p>
                        @endforeach
                    @endif
                </td>
                <td class="fw-light text-center align-middle">

                    @if (isset($list->Note->WorkForm))
                        <span class="badge text-bg-dark">{{ $list->Note->WorkForm->Equipment->count() }}</span>
                    @endif
                </td>
                <td class="fw-light text-center align-middle">
                    {{ isset($list->Note->WorkForm) ? $list->Note->WorkForm->Company->name : '---' }}
                </td>
                <td class="fw-light text-center align-middle">{{ $list->Note->lexp }}</td>
                <td class="fw-light text-center align-middle">{{ $list->Note->material }}</td>
                <td class="fw-light text-center align-middle">
                    {{ Carbon::now()->diffInDays(Carbon::parse($list->att_at)->format('Y-m-d')) }}
                </td>
                <td class="fw-light text-center align-middle">
                    {{ isset($list->Note->WorkForm) ? Carbon::now()->diffInDays($list->Note->WorkForm->informed_at) : '---' }}
                </td>
                <td scope="col"
                    class="text-center
                @if ($daysLeft->getDaysLeft() < 0) text-bg-secondary
                @elseif($daysLeft->getDaysLeft() >= 0 && $daysLeft->getDaysLeft() < 6)
                table-danger
                @elseif($daysLeft->getDaysLeft() >= 6 && $daysLeft->getDaysLeft() < 10)
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
                    {{ 30 - $daysLeft->getDaysLeft() }}
                </td>
                <td class="fw-light text-center align-middle">
                    <span
                        class="badge {{ Notestatus::status($list->status)->colorbg }}">{{ Notestatus::status($list->status)->status }}</span>
                </td>

            </tr>
        @endforeach
    </tbody>
</table>
