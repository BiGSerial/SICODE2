@php
    use Carbon\Carbon;
    use Carbon\CarbonInterval;
    use App\Custom\Notestatus;
@endphp
<div wire:poll>
    <div class="card">
        <h4 class="card-header">
            NOTAS EM TRANSFERENCIA - <span class="fs-6 fw-bold align-middle">{{ $live_transfer->count() }}</span>
        </h4>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-sm table-condensed table-striped">

                    <thead>
                        <tr>
                            <th scope="col">Note</th>
                            <th scope="col">De</th>
                            <th scope="col">Para</th>
                            <th scope="col">Serviço</th>
                            <th scope="col">Motivo</th>
                            <th scope="col">Situacao</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if ($live_transfer->count())
                            @foreach ($live_transfer as $transfer)
                                <tr>
                                    @php
                                        $to = explode(' ', $transfer->To->name);
                                        $to = $to[0] . ' ' . end($to);
                                        $from = explode(' ', $transfer->From->name);
                                        $from = $from[0] . ' ' . end($from);
                                    @endphp
                                    <td class="fw-bold">{{ $transfer->Production->Note->note }}</td>
                                    <td class="fw-bold">{{ $from }}</td>
                                    <td class="fw-bold">{{ $to }}</td>
                                    <td>{{ $transfer->Service->service }}</td>
                                    <td>{{ $transfer->info }}</td>
                                    <td><span
                                            class="badge {{ Notestatus::status($transfer->status)->colorbg }}">{{ Notestatus::status($transfer->status)->status }}</span>
                                    </td>
                                </tr>
                            @endforeach
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
