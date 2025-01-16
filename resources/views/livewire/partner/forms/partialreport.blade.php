@php
    use App\Custom\Partial\Rules;
@endphp
<div>
    <x-show-loading />
    <div class="d-flex justify-content-center mt-1">
        <div class="card text-center" style="width: 600px;">
            <div class="card-header fw-bold">
                BUSCAR OBRAS
            </div>
            <div class="card-body">
                <form class="d-flex justify-content-center">
                    <input class="form-control me-2 border border-secondary" type="search" wire:model.defer="search"
                        placeholder="Digite numero Nota, OV, Ordem ou Diagrama" style="max-width: 400px;">
                    <button class="btn btn-primary" wire:click.prevent="search">Buscar</button>
                </form>
            </div>
        </div>
    </div>

    @if (!$note)


        @if ($notes)
            <div class="d-flex justify-content-center mt-1">
                <div class="card text-bg-danger">
                    <div class="card-body text-center">
                        <h5 class="fw-bold">LEMBRE-SE DE INFORMAR APENAS OBRA PARCIAL</h5>
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-center mt-3">
                <div class="card text-center" style="width: 600px;">
                    <div class="card-header fw-bold">
                        LISTA DE OBRAS
                    </div>
                    <table class="table table-striped table-condensed">
                        <thead>
                            <tr>
                                <th class="fw-bold">Nota</th>
                                <th class="fw-bold">Ordem</th>
                                <th class="fw-bold">Qtd Parc</th>
                                <th class="fw-bold">Bloqueado</th>
                                <th class="fw-bold">Motivo</th>
                                <th class="fw-bold"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($notes as $tNote)
                                @php
                                    $block = false;
                                    $reason = '';

                                    if ($tNote->WorkForm) {
                                        $block = true;
                                        $reason = 'CONCLUÍDO';
                                    }

                                    if ($status = Rules::checkBlock($tNote->id)) {
                                        $block = true;

                                        if ($status == 1) {
                                            $reason = 'EM APROVAÇÃO';
                                        } elseif ($status == 2) {
                                            $reason = 'TEMPO MÍNIMO';
                                        } elseif ($status == 3) {
                                            if (!$tNote->partials->last()->supervision) {
                                                $reason = 'AGUARDANDO FISCALIZAÇÃO';
                                            } else {
                                                $reason = 'AGUARDANDO PAGAMENTO';
                                            }
                                        }
                                    }
                                @endphp
                                <tr class="align-middle" wire:key='{{ $tNote->id }}'>
                                    <td class="text-center fw-bold">{{ $tNote->note }}</td>
                                    <td class="text-center">
                                        @if ($tNote->orders->count())
                                            @foreach ($tNote->orders->filter(function ($order) {
        return !(strpos($order->statusSist, 'ENT') === 0 || strpos($order->statusSist, 'ENC') === 0);
    }) as $order)
                                                <p class="my-0 py-0">{{ $order->ordem }}</p>
                                            @endforeach
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <span class="badge text-bg-dark">{{ $tNote->partials->count() }}</span>
                                    </td>
                                    <td class="text-center">
                                        @if ($block)
                                            <span class="fw-bold text-danger">SIM</span>
                                        @else
                                            <span class="fw-bold text-success">NÃO</span>
                                        @endif
                                    </td>
                                    <td class="text-center fw-bold">{{ $reason }}</td>
                                    <td class="text-center">
                                        @if (!$block)
                                            <i class="text-success fw-bold ri-play-circle-line fs-4 align-middle"
                                                style="cursor: pointer;"
                                                wire:click.prevent="getNote({{ $tNote->id }})"></i>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    @endif

    @if ($note)
        <div class="card">
            <div class="card-header edp-bg-sprucegreen-70 text-edp-verde">
                <h4>INFORME DE ENTREGA DE OBRA</h4>
            </div>
            <div class="card-body">
                <div class="card mb-3">
                    <h5 class="card-header edp-bg-sprucegreen-70 text-edp-verde">DADOS DA NOTA</h5>
                    <table class="table table-condensed table-sm table-striped-columns">
                        <tbody>
                            <tr>
                                <td class="align-middle text-end" style="width: 150px;">Nota/Ov</td>
                                <td class="align-middle fw-bold">{{ $note->note }}</td>
                            </tr>
                            <tr>
                                <td class="align-middle text-end" style="width: 150px;">Ordens</td>
                                <td class="align-middle text-primary fw-bold">
                                    @if ($note->orders->count())
                                        @foreach ($note->orders->filter(function ($order) {
        return !(strpos($order->statusSist, 'ENT') === 0 || strpos($order->statusSist, 'ENC') === 0);
    }) as $order)
                                            <p class="my-0 py-0">{{ $order->ordem }}</p>
                                        @endforeach
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <td class="align-middle text-end" style="width: 150px;">Rubrica</td>
                                <td class="align-middle">{{ $note->rubrica }}</td>
                            </tr>
                            <tr>
                                <td class="align-middle text-end" style="width: 150px;">Município</td>
                                <td class="align-middle">{{ $note->lexp }}</td>
                            </tr>
                            <tr>
                                <td class="align-middle text-end" style="width: 150px;">Group1</td>
                                <td class="align-middle">{{ $note->group1 }}</td>
                            </tr>
                            <tr>
                                <td class="align-middle text-end" style="width: 150px;">Group2</td>
                                <td class="align-middle">{{ $note->group2 }}</td>
                            </tr>
                            <tr>
                                <td class="align-middle text-end" style="width: 150px;">Group3</td>
                                <td class="align-middle">{{ $note->group3 }}</td>
                            </tr>
                            <tr>
                                <td class="align-middle text-end" style="width: 150px;">Group1</td>
                                <td class="align-middle">{{ $note->group5 }}</td>
                            </tr>
                            <tr>
                                <td class="align-middle text-end" style="width: 150px;">Centro Trabalho</td>
                                <td class="align-middle">{{ $note->centerjob }}</td>
                            </tr>
                            <tr>
                                <td class="align-middle text-end" style="width: 150px;">Status Atual</td>
                                <td class="align-middle">{{ $note->nstats }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>


        <div class="card mt-3" style="max-width: 600px;" x-data="{ isUploading: false, progress: 0 }"
            x-on:livewire-upload-start="isUploading = true" x-on:livewire-upload-finish="isUploading = false"
            x-on:livewire-upload-error="isUploading = false"
            x-on:livewire-upload-progress="progress = $event.detail.progress">
            <h5 class="card-header edp-bg-sprucegreen-70 text-edp-verde">CARREGAR A ADS (Obrigatório)</h5>
            <div class="card-body">
                <div class="input-group mb-1">
                    <input type="file" class="form-control" wire:model="file" accept=".xlsx">
                    <button class="btn btn-primary position-relative" wire:click.prevent="processFile" @disabled(!$file)>
                        <span wire:loading.remove wire:target="processFile">Processar</span>
                        <span wire:loading wire:target="processFile" class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                    </button>
                </div>
                <div class="progress mb-2" x-show="isUploading">
                    <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar"
                        :style="`width: ${progress}%`">
                        <span x-text="progress + '%'"></span>
                    </div>
                </div>
            </div>
        </div>
    @endif

</div>
