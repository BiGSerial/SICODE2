<div>
    <div class="card">
        <h4 class="card-header">
            RECLAMAÇÃO AGUARDANDO RESOLUÇÃO
        </h4>
        <div class="table-responsive">

            @if ($list->count() > 0)
                <table class="table table-striped table-bordered">
                    <thead>
                        <tr class="text-center align-middle">

                            <th scope="col-1" class="col-1">Numero Reclamação</th>
                            <th scope="col-1" class="col-1">Tipo:</th>
                            <th scope="col-1" class="col-1">Numero Medida:</th>
                            <th class="col-1">Abertura Reclamação</th>
                            <th class="col-1">Conclusão Desejada</th>
                            <th class="col-1">Data da Medida</th>
                            <th class="col-1">Note Ref</th>
                            <th class="col-1">Enviado Em</th>
                            <th class="col-1">Dias Atv</th>
                            <th class="col-1">Enviado Por:</th>
                            <th class="col">Obs:</th>
                            <th class="col-1"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($list as $item)
                            @php
                                $user = $item->Assignments->where('user_id', Auth::id())->first();
                                $status = $status = [
                                    'class' => '',
                                    'message' => '',
                                    'days' => '',
                                ];
                                if ($days = $user?->started_at->startOfDay()->diffInDays()) {
                                    if ($days > 5) {
                                        $status = [
                                            'class' => 'text-bg-danger',
                                            'message' => 'VENCIDO',
                                            'days' => $days,
                                        ];
                                    } elseif ($days < 2 || $days == 0) {
                                        $status = [
                                            'class' => 'text-bg-success',
                                            'message' => 'PRAZO',
                                            'days' => $days,
                                        ];
                                    } else {
                                        $status = [
                                            'class' => 'text-bg-warning',
                                            'message' => 'VENCENDO',
                                            'days' => $days,
                                        ];
                                    }
                                }
                            @endphp
                            <tr class="text-center align-middle">

                                <td>{{ $item->protest->nota }}</td>
                                <td class='fw-bold'>{{ $item->protest->tipoNota }}</td>
                                <td>{{ $item->med_id }}</td>
                                <td class="fw-bold">{{ $item->protest->dtAberturaNota->format('d/m/Y') }}</td>
                                <td class="fw-bold">{{ $item->protest?->dtConclusaoDesej?->format('d/m/Y') }}</td>
                                <td class="fw-bold">{{ $item->dtCriacaoMedida?->format('d/m/Y') }}</td>
                                <td>{{ $item->Notes->isNotEmpty() ? $item->Notes?->last()?->note : 'SEM NOTA REFERÊNCIA' }}
                                </td>
                                <td class="fw-bold text-primary">{{ $user?->started_at->format('d/m/Y H:i') }}</td>
                                <td class="{{ $status['class'] }}">
                                    {{ $user?->started_at->startOfDay()->diffInDays() }}</td>

                                <td>{{ $item->Assignments->where('responsible', true)->first()?->User->name }}</td>
                                <td>{{ $item->comments->isNotEmpty() ? $item->comments->first()->message : 'SEM OBSERVAÇÃO' }}
                                </td>
                                <td><a href="{{ route('protests.services.view', $item->id) }}"><i
                                            class="ri-play-circle-fill fs-4 align-middle text-success"
                                            style="cursor: pointer;"></i></a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <div class="alert alert-info">
                    Nenhum registro encontrado.
                </div>
            @endif
        </div>
    </div>
</div>
