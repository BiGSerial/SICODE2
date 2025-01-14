@php
    use Carbon\Carbon;
    use Carbon\CarbonInterval;
    use App\Custom\Notestatus;
    use App\Custom\WpaStatus;
    use App\Models\Notetimeline;
@endphp
<table>
    <thead>
        <tr>
            <th scope="col" class="fw-bold text-center">Note</th>
            <th scope="col" class="fw-bold text-center">DD</th>
            <th scope="col" class="fw-bold text-center">stsDD</th>
            <th scope="col" class="fw-bold text-center">MMGD</th>
            <th scope="col" class="fw-bold text-center">Grp2</th>
            <th scope="col" class="fw-bold text-center">Rubrica</th>
            <th scope="col" class="fw-bold text-center">Região</th>
            <th scope="col" class="fw-bold text-center">Municipio</th>
            <th scope="col" class="fw-bold text-center">Zona</th>
            <th scope="col" class="fw-bold text-center">Descrição</th>
            <th scope="col" class="fw-bold text-center">Usuário</th>
            <th scope="col" class="fw-bold text-center">Empresa</th>

            <th scope="col" class="fw-bold text-center">Dias Despachado</th>
            <th scope="col" class="fw-bold text-center">Dias Atribuido</th>
            <th scope="col" class="fw-bold text-center">Prazo Real</th>
            <th scope="col" class="fw-bold text-center">Status</th>
            <th scope="col" class="fw-bold text-center">Desc Status</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($lists as $list)
            <tr>

                <td class="fw-bold">
                    {{ $list->Note->note }}
                </td>
                <td class="fw-light text-center">
                    @if ($list->Wpas->count())
                        {{ $list->Wpas()->get()->last()->dd }}
                    @else
                        -----
                    @endif
                </td>
                <td class="fw-light text-center @if ($list->priority) text-danger fw-bold @endif">
                    {{-- @php
                        $wpa = '';
                        if ($list->Wpas->count()) {
                            $wpa = WpaStatus::status(
                                $list
                                    ->Wpas()
                                    ->get()
                                    ->last()->stats,
                                $list
                                    ->Wpas()
                                    ->get()
                                    ->last()->execstats,
                                $list
                                    ->Wpas()
                                    ->get()
                                    ->last()->completed_at,
                            );
                        }
                    @endphp --}}
                    @if ($list->Wpas->count())
                        {{ WpaStatus::status($list->Wpas()->get()->last()->stats, $list->Wpas()->get()->last()->stats, null)->info }}
                    @else
                        -----
                    @endif

                </td>
                <td class="fw-bold text-danger text-center">
                    {{ $list->Note->mmgd ? 'MMGD' : '' }}
                </td>
                <td class="fw-bold @if ($list->priority) text-danger fw-bold text-center @endif">
                    {{ $list->Note->group2 ? $list->Note->group2 : '____' }}
                </td>
                <td class="fw-light text-center @if ($list->priority) text-danger fw-bold @endif">
                    {{ $list->Note->rubrica }}</td>
                <td class="fw-light text-center @if ($list->priority) text-danger fw-bold @endif">
                    {{ $cities->where('rdMunicipio', $list->Note->nexp)->first() ? $cities->where('rdMunicipio', $list->Note->nexp)->first()->regiao : '----' }}
                </td>
                <td class="fw-light text-center @if ($list->priority) text-danger fw-bold @endif">
                    {{ $list->Note->lexp }}</td>
                <td class="fw-light text-center @if ($list->priority) text-danger fw-bold @endif">
                    {{ $list->Note->group1 }}</td>
                <td class="fw-light text-center @if ($list->priority) text-danger fw-bold @endif">
                    {{ $list->Note->material }}</td>
                <td class="fw-light text-center @if ($list->priority) text-danger fw-bold @endif">
                    {{ $list->User ? $list->User->name : '----' }}</td>
                <td class="fw-light text-center @if ($list->priority) text-danger fw-bold @endif">

                    {{ $list->Company ? explode(' ', $list->Company->name)[0] : '-' }}</td>

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
            ">
                    {{ 30 - $list->Note->days_left }}
                </td>

                <td class="fw-light text-center">
                    @if ($list->transferred && $list->block_wpa)
                        Aguardando Despacho
                    @else
                        {{ Notestatus::status($list->status)->status }}
                    @endif
                </td>
                @php
                    if (
                        $status = Notetimeline::where('note_id', $list->note_id)
                            ->where('service_id', $list->service_id)
                            ->where('status', $list->status)
                            ->orderBy('created_at', 'DESC')
                            ->with('User')
                            ->first()
                    ) {
                        $info = $status->info;
                    } else {
                        $info = '';
                    }
                @endphp
                <td>
                    {{ $info }}
                </td>
            </tr>
        @endforeach
    </tbody>
</table>
