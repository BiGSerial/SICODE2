@php
    use Carbon\Carbon;
    use App\Custom\Notestatus;
@endphp

<div>
    <x-show-loading />

    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-start my-1">
                <input type="text" class="form-control w-25 border border-secondary" placeholder="Buscar..."
                    wire:model="search">


                <select class="form-select form-select-sm ms-2 w-25 border border-secondary" wire:model="company">
                    <option value="">Selecione uma opção</option>
                    @if ($companies)
                        @foreach ($companies as $company)
                            <option value="{{ $company->id }}">{{ $company->name }}</option>
                        @endforeach
                    @endif
                </select>
            </div>
        </div>

    </div>
    <div class="card">
        <div class="card-header edp-bg-seoweedgreen-100 py-1 d-flex justify-content-between align-items-center">
            <h4 class="my-0 text-white">Informes Digitados sem Informes Final</h4>
            <button class="btn btn-sm btn-primary" title="Exportar para Excel" wire:click.prevent="export_excel">
                <i class="ri-file-excel-2-line fs-5"></i>
            </button>
        </div>

        @if ($lists)
            {{-- @dump($lists[0]) --}}
            <table class="table table-stripped table-condensed table-sm table-hover">
                <thead>
                    <tr class="text-center">
                        <th>Nota</th>
                        <th>Files</th>
                        <th>Empresa</th>
                        <th>Usuário</th>
                        <th>Informe Digitado</th>
                        <th>Informe Empreiteira</th>
                        <th>Publicação Parcial</th>
                        <th>Publicação Final</th>
                        <th>Atualização</th>

                    </tr>
                </thead>
                <tbody>
                    @foreach ($lists as $list)
                        @php

                            // ------------------------------
                            // Lógica para $daysWorkForm
                            // ------------------------------
                            $daysWorkForm = null;

                            if ($list->WorkForm && !$list->WorkForm->rejected) {
                                $daysWorkForm = $list->WorkForm?->informed_at?->format('d/m/Y');
                            } else {
                                $createdAt = Carbon::parse($list->RamalForm->created_at)->startOfDay();
                                $now = Carbon::now()->startOfDay();
                                $daysDiff = $createdAt->diffInDays($now);
                                $daysWorkForm = $createdAt->isToday() ? 0 : $daysDiff;
                            }

                            $WorkDaysColor = '';

                            if (is_int($daysWorkForm)) {
                                if ($daysWorkForm > 6) {
                                    $WorkDaysColor = 'text-bg-danger';
                                } elseif ($daysWorkForm <= 2) {
                                    $WorkDaysColor = 'text-bg-success';
                                } else {
                                    $WorkDaysColor = 'text-bg-warning';
                                }
                            }

                            // ------------------------------
                            // Lógica para $ProdParcialDays
                            // ------------------------------
                            $ProdParcialDays = '';
                            $partialColors = '';

                            if ($list->productions && $list->productions->isNotEmpty()) {
                                $lastProduction = $list->productions->last();

                                if ($lastProduction && $lastProduction->partial_at) {
                                    $ProdParcialDays = $lastProduction->partial_at->format('d/m/Y');
                                } elseif (
                                    $lastProduction &&
                                    $lastProduction->att_at &&
                                    !($lastProduction->status == 28) &&
                                    !$list->RamalForm->rejected &&
                                    !$list->Workform
                                ) {
                                    $ProdParcialDays = $lastProduction->att_at->diffInDays(Carbon::now());
                                } else {
                                    $ProdParcialDays = '---';
                                }
                            } elseif (!$list->productions) {
                                if ($list->RamalForm && $list->RamalForm->informed_at && !$list->RamalForm->rejected) {
                                    $ProdParcialDays = $list->RamalForm->informed_at->diffInDays(Carbon::now());
                                } else {
                                    $ProdParcialDays = '---';
                                }
                            } else {
                                $ProdParcialDays = '---';
                            }

                            if (is_int($ProdParcialDays)) {
                                if ($ProdParcialDays > 4) {
                                    $partialColors = 'text-bg-danger';
                                } elseif ($ProdParcialDays <= 1) {
                                    $partialColors = 'text-bg-success';
                                } else {
                                    $partialColors = 'text-bg-warning';
                                }
                            }

                            // ------------------------------
                            // Lógica para $ProdFinalDays
                            // ------------------------------
                            $ProdFinalDays = '';
                            $finalColors = '';

                            if ($list->productions && $list->productions->isNotEmpty() && $list->Workform) {
                                $lastProduction = $list->productions->last();

                                if ($lastProduction && $lastProduction->completed_at) {
                                    $ProdFinalDays = $lastProduction->completed_at->format('d/m/Y');
                                } elseif ($list->Workform && !$list->Workform->rejected) {
                                    $ProdFinalDays = $list->Workform->informed_at->diffInDays(Carbon::now());
                                } else {
                                    $ProdFinalDays = '---';
                                }
                            } elseif ($list->Workform && !$list->Workform->rejected) {
                                $ProdFinalDays = $list->Workform->informed_at->diffInDays(Carbon::now());
                            } else {
                                $ProdFinalDays = '---';
                            }

                            if (is_int($ProdFinalDays)) {
                                if ($ProdFinalDays > 4) {
                                    $finalColors = 'text-bg-danger';
                                } elseif ($ProdFinalDays <= 1) {
                                    $finalColors = 'text-bg-success';
                                } else {
                                    $finalColors = 'text-bg-warning';
                                }
                            }

                            // Agora você tem:
                            // $daysWorkForm (data formatada ou número de dias)
                            // $WorkDaysColor (classe CSS para a cor)
                            // $ProdParcialDays (data/hora formatada, número de dias ou '---')
                            // $ProdFinalDays (data formatada ou número de dias ou null)
                            // $finalColors (classe CSS para a cor)

                            $rowClass = '';

                            if ($list->RamalForm?->rejected || $list->WorkForm?->rejected) {
                                $rowClass = 'table-warning';
                            } elseif ($list->RamalForm && $list->WorkForm && !$list->WorkForm->rejected) {
                                $rowClass = 'table-success';
                            }

                        @endphp
                        <tr class="text-center align-middle"
                            wire:dblClick="$emitTo('btzero.view.compare-form', 'showCompareForm', {{ $list }})"
                            style="cursor: pointer;" data-bs-toggle="tooltip" data-bs-placement="left"
                            data-bs-title="Duplo Clique para abrir a comparação">

                            <td class="fw-bold {{ $rowClass }}">{{ $list->note }}</td>
                            <td class="text-center {{ $rowClass }}">
                                @if ($list->files)
                                    <x-files.select-download-list :files="$list->files" />
                                @endif
                            <td class="{{ $rowClass }}">
                                {{ $list->RamalForm ? $list->RamalForm->Company->name : '---' }}</td>
                            <td class="{{ $rowClass }}">
                                {{ $list->RamalForm ? $list->RamalForm->User->name : '---' }}</td>
                            <td class="{{ $rowClass }}">
                                {{ $list->RamalForm ? Carbon::parse($list->RamalForm->created_at)->format('d/m/Y') : 'Não Informado' }}
                            </td>
                            <td class="{{ $rowClass }} {{ $WorkDaysColor }} borde_end border-1">

                                {{ is_int($daysWorkForm) ? $daysWorkForm . ' dias' : $daysWorkForm }}
                            </td>
                            <td class="{{ $rowClass }} {{ $partialColors }} borde_end border-1">

                                {{ is_int($ProdParcialDays) ? $ProdParcialDays . ' dias' : $ProdParcialDays }}
                            </td>
                            <td class="{{ $rowClass }} {{ $finalColors }} borde_end border-1">
                                {{ is_int($ProdFinalDays) ? $ProdFinalDays . ' dias' : $ProdFinalDays }}

                            </td>
                            <td class="{{ $rowClass }}">
                                @if ($list->productions && $list->productions->last())
                                    @if ($list->productions->last()->status == 5)
                                        <span class="badge bg-success">Publicado</span>
                                    @elseif ($list->productions->last()->status == 28)
                                        <span class="badge bg-success">Publicado Parcial</span>
                                    @else
                                        <span
                                            class="badge {{ Notestatus::status($list->productions->last()->status)->colorbg }}">{{ Notestatus::status($list->productions->last()->status)->status }}</span>
                                    @endif
                                @else
                                    <span class="badge bg-primary">Não Publicado</span>
                                @endif
                            </td>





                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <div class="card-body">
                <h5 class="text-center">SEM INFORMES</h5>
            </div>
        @endif
    </div>

    <div class="d-flex justify-content-center mt-3">
        {{ $lists->links() }}
        <div class="mt-2">
            <p class="text-center">
                Exibindo de {{ $lists->firstItem() }} até {{ $lists->lastItem() }} de um total de
                {{ $lists->total() }} registros
            </p>
        </div>
    </div>

    {{-- Componentes Livewire --}}
    @livewire('btzero.view.compare-form', key('btZeroCompareForm'))
</div>
