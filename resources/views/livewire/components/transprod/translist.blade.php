@php
    use Carbon\Carbon;
    use App\Custom\Notestatus;
@endphp

<div>
    @if (!$lists->count())
        <div class="card mt-3">
            <div class="card-body">
                <h4 class="text-center my-4">SEM INTENÇÕES DE TRANSFERÊNCIAS</h4>
            </div>
        </div>
    @else
        <div class="card mt-3">

            <h4 class="card-header text-bg-primary">TRANSFERÊNCIAS DE PRODUÇÃO</h4>

            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm table-stripped table-condensed">
                        <thead class="table-dark">
                            <tr>
                                <th scope="col">De</th>
                                <th scope="col-1"></th>
                                <th scope="col">PARA</th>
                                <th scope="col">NOTA/OV</th>
                                <th scope="col">STATUS/SAP</th>
                                <th scope="col">SITUAÇÃO</th>
                                <th scope="col">MOTIVO</th>
                                <th scope="col"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($lists as $list)
                                <tr class="align-middle">
                                    <td>
                                        @if ($list->from === Auth()->User()->id)
                                            <span class="fw-bold">VOCÊ</span>
                                        @else
                                            {{ explode(' ', $list->From->name)[0] }}
                                        @endif

                                    </td>
                                    <td>
                                        @if ($list->from === Auth()->User()->id)
                                            <i class="ri-arrow-right-circle-fill text-success aling-middle fs-4"></i>
                                        @elseif ($list->to === Auth()->User()->id)
                                            <i class="ri-arrow-right-circle-fill text-danger aling-middle fs-4"></i>
                                        @endif

                                    </td>
                                    <td>
                                        @if ($list->to === Auth()->User()->id)
                                            <span class="fw-bold">VOCÊ</span>
                                        @else
                                            {{ explode(' ', $list->To->name)[0] }}
                                        @endif

                                    </td>
                                    <td>{{ $list->Production->Note->note }}</td>
                                    <td>{{ $list->Production->status_note }}/{{ $list->Production->Note->nstats }}</td>

                                    <td><span
                                            class="badge {{ Notestatus::status($list->status)->colorbg }}">{{ Notestatus::status($list->status)->status }}</span>
                                    </td>
                                    <td>{{ $list->info }}</td>
                                    <td>

                                        @if ($list->to === Auth()->User()->id && !$list->read_to)
                                            <button class="btn btn-sm btn-success col"
                                                wire:click.prevent="to_accept({{ $list->id }})"><i
                                                    class="ri-checkbox-circle-line fs-5"></i></button>
                                            <button class="btn btn-sm btn-danger col"
                                                wire:click.prevent="to_rejectt({{ $list->id }})"><i
                                                    class="ri-close-circle-line fs-5"></i></button>
                                        @elseif ($list->from === Auth()->User()->id && !$list->read_from)
                                            <button class="btn btn-sm btn-primary col"
                                                wire:click.prevent="to_ok({{ $list->id }})">OK</button>
                                        @endif

                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif
</div>
