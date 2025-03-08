@php
    use App\Custom\Viabilitiesstatus;
    use App\Custom\Notestatus;
    use Carbon\Carbon;
@endphp
<table class="table table-sm table-condensed table-striped-columns">
    <thead>
        <tr>
            <th scope="col" class="text-center">Origem</th>
            <th scope="col" class="text-center">Nota</th>
            <th scope="col" class="text-center">Rubrica</th>
            <th scope="col" class="text-center">Municipio</th>
            <th scope="col" class="text-center">Grupo 5</th>
            <th scope="col" class="text-center">Material</th>
            <th scope="col" class="text-center">Solicitante</th>
            <th scope="col" class="text-center">Empresa Solicitante</th>
            <th scope="col" class="text-center">Categoria</th>
            <th scope="col" class="text-center">Texto</th>
            <th scope="col" class="text-center">Data Envio</th>
            <th scope="col" class="text-center">Em Atividade</th>
            <th scope="col" class="text-center">Status</th>
            <th scope="col" class="text-center">Responsável</th>
            <th scope="col" class="text-center">Empresa Responsável</th>
        </tr>
    </thead>
    <tbody class="table-group-divider">
        @if ($lists)
            @foreach ($lists as $list)
                @php
                    $vencido = false;
                    $vencimento = Carbon::now()->subHours(24)->toDateTimeString();
                    if ($list->updated_at < $vencimento) {
                        $vencido = true;
                    }
                @endphp

                <tr wire:key="row-{{ $list->id }}">
                    <td class="text-center align-middle fw-bold">
                        @if ($list->Approvals->isNotEmpty())
                            ANALISE PROJETO
                        @elseif ($list->Waiting)
                            CONTRATAÇÃO
                        @elseif ($list->Viabilities->isNotEmpty())
                            VIABILIDADE
                        @endif
                    </td>
                    <td class="text-center align-middle fw-bold">{{ $list->Note->note }}</td>
                    <td class="text-center align-middle">{{ $list->Note->rubrica }}</td>
                    <td class="text-center align-middle">{{ $list->Note->lexp }}</td>
                    <td class="text-center align-middle">{{ $list->Note->group5 }}</td>
                    <td class="text-center align-middle">{{ $list->Note->material }}</td>
                    <td class="text-center align-middle">
                        @if ($list->Approvals->isNotEmpty())
                            {{ $list->Approvals->last()->User->name }}
                        @elseif ($list->Waiting)
                            {{ $list->Waiting->User->name }}
                        @elseif ($list->Viabilities->isNotEmpty())
                            {{ $list->Viabilities->last()->Engineer->name }}
                        @endif
                    </td>
                    <td class="text-center align-middle">
                        @if ($list->Approvals->isNotEmpty())
                            {{ $list->Approvals->last()->User ? $list->Approvals->last()->User->Employee->Contract->company->name : '' }}
                        @elseif ($list->Waiting)
                            {{ $list->Waiting->User ? $list->Waiting->User->Employee->Contract->company->name : '' }}
                        @elseif ($list->Viabilities->isNotEmpty())
                            {{ $list->Viabilities->last()->Engineer ? $list->Viabilities->last()->Engineer->Employee->Contract->company->name : '' }}
                        @endif
                    </td>
                    <td class="text-center align-middle">{{ $list->category }}</td>
                    <td class="text-center align-middle">
                        {{ $list->Comments->count() ? $list->Comments->last()->message : '' }}
                    </td>
                    <td class="text-center align-middle">
                        {{ Carbon::parse($list->created_at)->format('d/m/Y H:i') }}
                    </td>
                    <td
                        class="text-center align-middle
                    @if ($vencido) text-bg-danger @endif
                    ">
                        {{ Carbon::parse($list->created_at)->diffForHumans(Carbon::now(), ['locale' => 'pt_br', 'syntax' => \Carbon\CarbonInterface::DIFF_ABSOLUTE]) }}
                    </td>
                    <td class="text-center align-middle">
                        @if ($list->Production)
                            {{ Notestatus::status($list->Production->status)->status }}
                        @else
                            <span class="badge text-bg-secondary">
                                Aguardando Atribuição</span>
                        @endif

                    </td>
                    <td class="text-center align-middle">
                        {{ $list->Production ? ($list->Production->User ? $list->Production->User->name : 'Desconhecido') : '' }}
                    </td>
                    <td class="text-center align-middle">
                        {{ $list->Production ? ($list->Production->Company ? $list->Production->Company->name : 'Desconhecido') : '' }}
                    </td>

                </tr>
            @endforeach
        @endif

    </tbody>
</table>
