@php
    use App\Helpers\SelectOptions;
@endphp
<div>
    <x-show-loading />
    <div wire:ignore.self class="modal fade" id="formProductionModal" tabindex="-1"
        aria-labelledby="formProductionModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-fullscreen">
            @if ($production)
                <div class="modal-content">
                    <div class="modal-header edp-bg-sprucegreen-70 text-edp-verde">
                        <h1 class="modal-title fs-5" id="formProductionModalLabel">
                            {{ mb_strToUpper($production->Service->service) }} - {{ $production->Note->note }}</h1>
                        <button type="button" class="btn-close btn-succes" data-bs-dismiss="modal"
                            aria-label="Close"></button>
                    </div>
                    <div class="modal-body edp-bg-stategrey-50">
                        <div class="container">
                            <div class="card">
                                <h5 class="card-header py-1 my-0 edp-bg-sprucegreen-70 text-edp-verde">INFORMAÇÕES</h5>
                                <div class="table-responsive">
                                    <table class="table table-sm table-condensed table-striped-columns">
                                        <tbody>
                                            <tr>
                                                <td class="fw-bold col-2 align-middle">NOTA/OV:</td>
                                                <td class="align-middle fw-bold">{{ $production->Note->note }}</td>
                                            </tr>
                                            <tr>
                                                <td class="fw-bold col-2 align-middle">ORDEM:</td>
                                                <td class="align-middle">
                                                    @if (isset($production->Note->WorkForm) && $production->Note->WorkForm->Orders->count())
                                                        @foreach ($production->Note->WorkForm->Orders as $order)
                                                            <p class="my-1 py-0">{{ $order->ordem }}</p>
                                                        @endforeach
                                                    @elseif (isset($production->Note->RamalForm) && $production->Note->RamalForm->Orders->count())
                                                        @foreach ($production->Note->RamalForm->Orders as $order)
                                                            <p class="my-1 py-0">{{ $order->ordem }}</p>
                                                        @endforeach
                                                    @endif
                                                </td>
                                            </tr>
                                            <tr>
                                                <td class="fw-bold col-2 align-middle">RUBRICA:</td>
                                                <td class="align-middle text-uppercase">{{ $production->Note->rubrica }}
                                                </td>
                                            </tr>
                                            <tr>
                                                <td class="fw-bold col-2 align-middle text-uppercase">MUNICIPIO:</td>
                                                <td class="align-middle">{{ $production->Note->lexp }}</td>
                                            </tr>

                                            <tr class="mt-3">
                                                <td class="fw-bold col-2 align-middle text-uppercase">MUDANÇA NO
                                                    PROJETO:</td>
                                                <td class="align-middle">
                                                    @if (isset($production->Note->WorkForm))
                                                        {{ $production->Note->WorkForm->changes ? 'SIM' : 'NÃO' }}
                                                    @endif

                                                </td>
                                            </tr>
                                            <tr>
                                                <td class="fw-bold col-2 align-middle text-uppercase">Data Informada:
                                                </td>
                                                <td class="align-middle">
                                                    @if (isset($production->Note->WorkForm))
                                                        {{ date('d/m/Y', strToTime($production->Note->WorkForm->date)) }}
                                                    @endif

                                                </td>
                                            </tr>
                                            <tr>
                                                <td class="fw-bold col-2 align-middle text-uppercase">Data Sicode:</td>
                                                <td class="align-middle">
                                                    @if (isset($production->Note->WorkForm))
                                                        {{ date('d/m/Y H:i:s', strToTime($production->Note->WorkForm->informed_at)) }}
                                                    @endif
                                                </td>
                                            </tr>

                                            <tr>
                                                <td class="fw-bold col-2 align-middle text-uppercase">Equipe WPA:</td>
                                                <td class="align-middle">
                                                    @if (isset($production->Note->WorkForm))
                                                        {{ $production->Note->WorkForm->team }}
                                                    @endif
                                                </td>
                                            </tr>
                                            <tr>
                                                <td class="fw-bold col-2 align-middle text-uppercase">Responsável
                                                    Execução:</td>
                                                <td class="align-middle">
                                                    @if (isset($production->Note->WorkForm))
                                                        {{ $production->Note->WorkForm->responsible }}
                                                    @endif
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            @if ($production->Note->RamalForm && !$production->Note->WorkForm)
                                <div class="card text-bg-danger">
                                    <h4 class="text-center fw-bold my-2">FAVOR NÃO CONFIRMAR A 20 NO SAP</h4>
                                </div>
                            @endif

                            <div class="card">
                                <h5 class="card-header py-1 my-0 edp-bg-sprucegreen-70 text-edp-verde">RESOLUÇÃO</h5>
                                <div class="card-body">
                                    <div class="mb-3 col-md-1">
                                        <label for="ativos" class="form-label">Qtd Ativos</label>
                                        <input type="number" id="ativos" class="form-control border-secondary"
                                            wire:model.defer="analise.postes">
                                    </div>
                                    <div class="mb-3 col-md-3">
                                        <label for="resultado" class="form-label">Resultado</label>
                                        <select id="resultado" class="form-select border-secondary"
                                            wire:model.defer="analise.conclusion">
                                            <option value="">Selecione...</option>
                                            @foreach (SelectOptions::getPublicationOptions() as $item)
                                                <option value="{{ $item->value }}">{{ $item->info }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="mb-3 col-md-6">
                                        <label for="info" class="form-label">Observação</label>
                                        <textarea type="number" id="info" rows="5" class="form-control border-secondary"
                                            wire:model.defer="analise.info"></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer edp-bg-stategrey-100">
                        <button type="button" class="btn btn-secondary" wire:click.prevent="saveForm()">SALVAR</button>
                        <button type="button" class="btn btn-info" wire:click.prevent="waitingForm()">ESPERAR</button>
                        <button type="button" class="btn btn-warning"
                            wire:click="$emitTo('components.pausenote.pausenote2', 'stop_note', {{ $production }})">PAUSAR</button>
                        @if ($production->Note->WorkForm)
                            <button type="button" class="btn btn-success"
                                wire:click.prevent="to_finish()">ENCERRAR</button>
                        @elseif($production->Note->RamalForm)
                            <button type="button" class="btn btn-success"
                                wire:click.prevent="to_Publish()">ENCERRAR</button>
                        @endif
                    </div>
                </div>
            @endif
        </div>
    </div>
    {{-- Livewire Components --}}
    @livewire('components.pausenote.pausenote2', key('PauseNotes2'))

    <script>
        // Capturando o evento de fechamento do modal
        document.getElementById('formProductionModal').addEventListener('hidden.bs.modal', () => {

            document.getElementById('formProductionModal').removeAttribute('data-backdrop');
            Livewire.emitTo('services.publication.forms.jobform', 'closeAll');

        });
    </script>
</div>
>
