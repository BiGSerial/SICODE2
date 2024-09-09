@php
    use Carbon\Carbon;
    use App\Custom\Notestatus;
@endphp
<table class="table table-sm table-striped table-condensed">
    <thead class="table-dark">
        <tr>

            <th class="align-middle text-center">Nota</th>
            <th class="align-middle text-center">Ordem</th>
            <th class="align-middle text-center">MOA</th>
            <th class="align-middle text-center">Emp SAP</th>
            <th class="align-middle text-center">Emp Info</th>
            <th class="align-middle text-center">Dt Informe</th>
            <th scope="col" class="fw-bold text-center">Rubrica</th>
            <th scope="col" class="fw-bold text-center">Municipio</th>
            <th scope="col" class="fw-bold text-center">Emp Pag Usr</th>
            <th scope="col" class="fw-bold text-center">Usuário</th>
            <th scope="col" class="fw-bold text-center">Dias Despachado</th>
            <th scope="col" class="fw-bold text-center">Dias Atribuido</th>
            <th scope="col" class="fw-bold text-center">Status</th>

        </tr>
    </thead>
    <tbody>
        @php
            $soma = 0;
        @endphp
        @foreach ($lists as $list)
            <tr wire:key="line-{{ $list->id }}"
                class="align-middle
                    @if ($list->block) table-primary @endif

                    ">

                <td class="fw-bold @if ($list->priority) text-danger fw-bold @endif">

                    @if ($list->d5)
                        <span class="badge text-bg-primary fs-6">{{ $list->Note->note }}
                            (RI)
                        </span>
                    @else
                        {{ $list->Note->note }}
                        <span class="copy-text" data-value="{{ $list->Note->note }}" style="cursor: pointer;"> <i
                                class="ri-file-copy-line"></i></span>
                    @endif


                    @if ($list->priority)
                        <i class="ri-alert-fill text-danger align-middle"
                            wire:click.prevent="$emit('infoPriority', '{{ $list->id }}')"
                            style="cursor: pointer;"></i>
                    @endif
                </td>
                <td class="text-center align-middle">
                    @if ($list->Note->WorkForm->Orders->count())
                        @foreach ($list->Note->WorkForm->Orders as $order)
                            <p class="my-0 py-0">
                                {{ $order->ordem }}
                            </p>
                        @endforeach
                    @endif

                </td>
                <td class="text-center align-middle fw-bold">
                    @if ($list->Note->WorkForm->Orders->count())
                        @foreach ($list->Note->WorkForm->Orders as $order)
                            @php
                                $soma += $order->moaberto;
                            @endphp
                            <p class="my-0 py-0">
                                R$ {{ number_format($order->moaberto, 2, ',', '.') }}
                            </p>
                        @endforeach
                    @endif

                </td>

                <td class="text-center align-middle">
                    @if (isset($list->Note->WorkForm) && $list->Note->WorkForm->Orders->count())
                        @foreach ($list->Note->WorkForm->Orders as $order)
                            <p class="my-0py-0">
                                {{ $order->Operations->count() && isset($order->Operations->where('operacao', '0010')->first()->cenTrab) ? explode(' ', $order->Operations->where('operacao', '0010')->first()->cenTrab)[0] : '---' }}
                            </p>
                        @endforeach
                    @endif

                </td>


                <td class="fw-light text-center">
                    {{ $list->Note->WorkForm ? $list->Note->WorkForm->Company->name : '---' }}
                </td>

                <td class="text-center align-middle fw-bold">
                    @if ($list->Note->WorkForm)
                        {{ date('d/m/Y H:i:s', strToTime($list->Note->WorkForm->informed_at)) }}
                    @endif

                </td>


                <td class="fw-light text-center @if ($list->priority) text-danger fw-bold @endif">
                    {{ $list->Note->rubrica }}</td>

                <td class="fw-light text-center @if ($list->priority) text-danger fw-bold @endif">
                    {{ $list->Note->lexp }}</td>




                <td class="fw-light text-center @if ($list->priority) text-danger fw-bold @endif">

                    {{ $list->Company ? $list->Company->name : '-' }}</td>
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

                {{-- <td class="fw-light text-center">
                        <span
                            class="badge {{ Notestatus::status($list->status)->colorbg }}">{{ Notestatus::status($list->status)->status }}</span>
                    </td> --}}
                <td class="fw-light text-center">
                    {{ Notestatus::status($list->status)->status }}
                    {{-- @livewire('components.status.statusview', ['status' => $list->status, 'idstatus' => $list->id, 'note_id' => $list->note_id, key($list->id)]) --}}
                    {{-- <livewire:components.status.statusview :status="$list->status" :idstatus="$list->id" :note_id="$list->note_id"
                        :wire:key="'status-view-' . $list->id" /> --}}

                    {{-- @livewire('components.status.statusview', ['status' => $list->status, 'idstatus' => $list->id, 'note_id' => $list->note_id], key('statusView-{{ $list->id }}')) --}}
                </td>



            </tr>
        @endforeach
    </tbody>
    <tfoot>
        <tr class="table-dark align-middle">


            <td></td>
            <td class="text-end">Total:</td>
            <td class="fw-bold"> R$ {{ number_format($soma, 2, ',', '.') }}</td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>



        </tr>
    </tfoot>
</table>
