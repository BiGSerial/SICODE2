@php
    use App\Custom\Viabilitiesstatus;
    use App\Custom\Notestatus;
    use App\Helpers\SelectOptions;
    use Carbon\Carbon;
    use App\Helpers\DaysLeft;
@endphp
<div>
    <x-show-loading />

    <div wire:ignore.self class="modal fade" id="rejectProject" tabindex="-1" aria-labelledby="rejectProjectLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-xl bg-edp-gray">
            @if ($note)
                <div class="modal-content">
                    <div class="modal-header edp-bg-sprucegreen-70 text-edp-verde">
                        <h5 class="modal-title" id="rejectProjectLabel">VALIDAR PROJETO DE {{ $note->note }}</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                            aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="container-fluid">
                            <div class="row">
                                <div class="col-md-6">
                                    <!-- Informações do Produto/Serviço Sendo Devolvido -->
                                    <div class="card mb-3">
                                        <div class="card-header edp-bg-sprucegreen-70 text-edp-verde">
                                            Informações da Nota
                                        </div>
                                        <div class="card-body">
                                            <p><strong>Número da Nota:</strong> {{ $note->note }}</p>
                                            <p><strong>Rubrica:</strong> {{ $note->rubrica }}</p>
                                            @php
                                                $ordens = $note->orders
                                                    ? implode(', ', $note->orders->pluck('ordem')->toArray())
                                                    : '';
                                            @endphp
                                            <p><strong>Ordens:</strong> {{ $ordens }}</p>
                                            <p><strong>Municipio:</strong> {{ $note->lexp }}</p>
                                            <p><strong>Data Status:</strong> <span
                                                    class="fw-bold text-primary">{{ $note->dt_status->format('d/m/Y H:i') }}</span>
                                            </p>
                                            <p><strong>Tácito:</strong> <span class="fw-bold text-danger">
                                                    {{ $note->approval?->tacit ? 'SIM' : 'NÃO' }}</span>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">

                                    @if ($retornoInternos)
                                        @foreach ($retornoInternos as $reclaim)
                                            <div class="card">
                                                <h5
                                                    class="card-header py-1 my-0 edp-bg-sprucegreen-70 text-edp-verde d-flex justify-content-between align-items-center">
                                                    <span>RETORNO INTERNO</span>
                                                    @if (!$retornoInternos->last()->completed)
                                                        <button type="button" class="btn btn-sm btn-primary"
                                                            wire:click="preCancelReclaims">
                                                            Cancelar Rejeição
                                                        </button>
                                                    @endif
                                                </h5>
                                                <div class="table-responsive">
                                                    <table class="table table-sm table-condensed table-striped-columns">
                                                        <tbody>

                                                            <tr>
                                                                <td class="text-end fw-bold col-3">Serviço:</td>
                                                                <td class="col text-uppercase fw-bold">
                                                                    {{ $reclaim->service->service }}
                                                                </td>
                                                            </tr>
                                                            <tr>
                                                                <td class="text-end fw-bold col-3">Motivo:</td>
                                                                <td class="col">
                                                                    {{ $reclaim->category }}
                                                                </td>
                                                            </tr>
                                                            <tr>
                                                                <td class="text-end fw-bold col-3">Solicitação:</td>
                                                                <td class="col">
                                                                    @if ($reclaim->Comments->isNotEmpty())
                                                                        @foreach ($reclaim->Comments as $comment)
                                                                            <p class="my-1 py-0">
                                                                                {{ $comment->message }}
                                                                            </p>
                                                                        @endforeach
                                                                    @endif
                                                                </td>
                                                            </tr>
                                                            <tr>
                                                                <td class="text-end fw-bold col-3">Data Envio:</td>
                                                                <td class="col align-middle">
                                                                    <span class="fw-bold text-primary align-middle">
                                                                        {{ $reclaim->created_at->format('d/m/Y H:i') }}
                                                                    </span>

                                                                </td>
                                                            </tr>
                                                            @if ($reclaim->completed)
                                                                <tr>
                                                                    <td colspan="2"
                                                                        class="edp-bg-sprucegreen-70 text-edp-verde">
                                                                        RETORNO DE PRODUÇÃO
                                                                    </td>
                                                                </tr>
                                                                <tr>
                                                                    <td class="text-end fw-bold col-3">Data Att:</td>
                                                                    <td class="col align-middle">
                                                                        @if ($reclaim->production)
                                                                            <span
                                                                                class="fw-bold text-primary">{{ $reclaim->Production->att_at->format('d/m/Y H:i') }}</span>
                                                                        @endif
                                                                    </td>
                                                                </tr>
                                                                <tr>
                                                                    <td class="text-end fw-bold col-3">Data Conclusao:
                                                                    </td>
                                                                    <td class="col align-middle">
                                                                        @if ($reclaim->Production)
                                                                            <span
                                                                                class="fw-bold text-success">{{ $reclaim->Production->completed_at->format('d/m/Y H:i') }}</span>
                                                                        @endif
                                                                    </td>
                                                                </tr>
                                                                <tr>
                                                                    <td class="text-end fw-bold col-3">Resposta:</td>
                                                                    <td class="col align-middle">
                                                                        @if ($reclaim->production && $reclaim->production->analise)
                                                                            @php
                                                                                $texts = [];
                                                                                if (
                                                                                    $reclaim->Production->Analise->info
                                                                                ) {
                                                                                    $texts = explode(
                                                                                        "\n",
                                                                                        $reclaim->Production->Analise
                                                                                            ->info,
                                                                                    );
                                                                                }
                                                                            @endphp
                                                                            @foreach ($texts as $text)
                                                                                <p class="my-0 py-0 mx-2">
                                                                                    {{ $text }}
                                                                                </p>
                                                                            @endforeach
                                                                        @endif
                                                                    </td>
                                                                </tr>
                                                                <tr>
                                                                    <td class="text-end fw-bold col-3">Atendido Por:
                                                                    </td>
                                                                    <td class="col align-middle">
                                                                        @if ($reclaim->Production)
                                                                            <p class="my-1 py-0">
                                                                                {{ $reclaim->Production->User->name }}
                                                                            </p>
                                                                            <p class="my-1 py-0 text-primary">
                                                                                {{ $reclaim->Production->User->email }}
                                                                            </p>
                                                                        @endif
                                                                    </td>
                                                                </tr>
                                                            @endif
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        @endforeach

                                        @if ($retornoInternos->isNotEmpty())
                                            <div class="d-flex justify-content-between align-items-center my-2">
                                                <button type="button" class="btn btn-sm btn-secondary"
                                                    @if ($retornoInternos->onFirstPage()) disabled @else wire:click="previousPage" @endif>
                                                    Retroceder
                                                </button>
                                                <span>Página {{ $retornoInternos->currentPage() }} de
                                                    {{ $retornoInternos->lastPage() }}</span>
                                                <button type="button" class="btn btn-sm btn-secondary"
                                                    @if (!$retornoInternos->hasMorePages()) disabled @else wire:click="nextPage" @endif>
                                                    Avançar
                                                </button>

                                            </div>
                                        @endif
                                    @endif
                                </div>
                            </div>
                            <div class="col-12">

                                <div class="card  mb-3">
                                    <h5 class="card-header py-1 my-0 edp-bg-sprucegreen-70 text-edp-verde">ARQUIVOS
                                        ANEXADOS
                                    </h5>
                                    <div class="card-body py-2 px-3">
                                        @livewire('components.files.show-files-pool', ['files' => $note->files], key('files-pool-files-{{ $note->id }}'))
                                    </div>
                                </div>
                            </div>

                            <div class="col-12">
                                <div class="card">
                                    <div class="card-header edp-bg-sprucegreen-70 text-edp-verde">
                                        RESPONDER A ANALISE
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-4">
                                                <label for="select1" class="form-label">Selecione uma Decisão:</label>
                                                <select class="form-select border-secondary" wire:model="decision">
                                                    <option selected value="">Selecione uma Opção</option>
                                                    <option value="APROVADO">Aprovado</option>
                                                    <option value="REPROVADO">Reprovado</option>
                                                </select>
                                                @error('decision')
                                                    <small class="text-danger">{{ $message }}</small>
                                                @enderror
                                            </div>
                                            @if ($decision == 'APROVADO')
                                                <div class="col-8">
                                                    <div class="card text-bg-danger">
                                                        <div class="card-body">
                                                            <p class="fw-bold py-2 text-center">
                                                                Ao Aprovar, essa obra estará disponível para
                                                                contratação,
                                                                não sendo mais possível reverter para analise
                                                            </p>
                                                        </div>
                                                    </div>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>

                            @if ($decision == 'REPROVADO')
                                <div class="row">
                                    <div class="col-md-6">
                                        @if ($production)
                                            <div class="card mb-3">
                                                <div class="card-header edp-bg-sprucegreen-70 text-edp-verde">
                                                    Retorno para {{ $production->service->service }}
                                                </div>
                                                <div class="card-body">
                                                    <p><strong>Usuário:</strong> {{ $production->User->name }}</p>
                                                    <p><strong>Data:</strong>
                                                        {{ $production->completed_at->format('d/m/Y H:i:s') }}</p>
                                                </div>
                                            </div>
                                        @else
                                            <div class="card mb-3">
                                                <div class="card-body">
                                                    <h5 class="text-center">NENHUM USUÁRIO ENCONTRADO</h5>
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                    <div class="col-md-6">
                                        <!-- Formulário de Devolução -->
                                        <div class="mb-3">
                                            <label for="tipoServico" class="form-label">Tipo de Rejeição: <span
                                                    class="text-danger">*</span></label>
                                            <select class="form-select border border-secondary" id="tipoServico"
                                                wire:model.defer='category'>
                                                <option value="">Selecione...</option>
                                                @foreach (SelectOptions::getRejectOptions() as $reclaimOption)
                                                    <option value="{{ $reclaimOption->value }}">
                                                        {{ $reclaimOption->info }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="mb-3">
                                            <label for="categoriaDevolucao" class="form-label">Devolver para: <span
                                                    class="text-danger">*</span></label>
                                            <select class="form-select border border-secondary" id="service"
                                                wire:model="service">
                                                <option value="">Selecione...</option>
                                                @foreach ($serviceList as $tService)
                                                    <option value="{{ $tService->uuid }}">{{ $tService->service }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="mb-3">
                                            <label for="motivoDevolucao" class="form-label">Motivo Detalhado: <span
                                                    class="text-danger">*</span></label>
                                            <textarea class="form-control border border-secondary" id="motivoDevolucao" rows="5"
                                                wire:model.defer="details" placeholder="Detalhar informação a atividade a ser feita."></textarea>
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        @livewire('files.manager.create-gen-files', ['note' => $note, 'service' => 'REJEITAR'], key('files-note'))
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        {{ $hasFile }}
                        <button type="button" class="btn btn-primary" wire:click.prevent="preReject">Enviar</button>
                    </div>
                </div>
            @endif
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var rejectModal = document.getElementById('rejectProject');
            rejectModal.addEventListener('hidden.bs.modal', function(event) {
                livewire.emitTo('responsible.actions.reject-project', 'clearAll');
            });
        });
    </script>
</div>
