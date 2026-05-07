@php
    use App\Custom\Viabilitiesstatus;
    use App\Custom\Notestatus;
    use App\Helpers\SelectOptions;
    use Carbon\Carbon;
    use App\Helpers\DaysLeft;
    use App\Helpers\FileIcon;
    use Illuminate\Support\Facades\Storage;
    use Illuminate\Support\Str;

@endphp
<div>
    <x-show-loading />

    <div wire:ignore.self class="modal fade" id="rejectProject" tabindex="-1" aria-labelledby="rejectProjectLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-xl bg-edp-gray">
            @if ($note)
                @php
                    $imageExtensions = ['jpg', 'jpeg', 'png', 'webp', 'gif', 'bmp', 'tif', 'tiff'];
                    $canPreviewImage = function ($file) use ($imageExtensions) {
                        if (!in_array(mb_strtolower($file->ext), $imageExtensions, true)) {
                            return false;
                        }

                        $rawPath = ltrim((string) $file->path, '/');
                        $storageCandidates = array_values(array_unique(array_filter([
                            $rawPath,
                            Str::startsWith($rawPath, 'storage/') ? Str::after($rawPath, 'storage/') : null,
                        ])));

                        foreach ($storageCandidates as $candidate) {
                            if (Storage::exists($candidate) || Storage::disk('public')->exists($candidate)) {
                                return true;
                            }
                        }

                        $fsCandidates = array_values(array_unique(array_filter([
                            public_path($rawPath),
                            public_path('storage/' . $rawPath),
                            storage_path('app/public/' . $rawPath),
                            storage_path('app/' . $rawPath),
                        ])));

                        foreach ($fsCandidates as $candidate) {
                            if (is_file($candidate)) {
                                return true;
                            }
                        }

                        return false;
                    };

                    $attachedFiles = $note->files ?? collect();
                    $groupedFiles = $attachedFiles
                        ->groupBy(fn ($file) => mb_strtoupper($file->service->service ?? 'OUTROS'))
                        ->sortKeys();
                    $previewFiles = $attachedFiles
                        ->filter(fn ($file) => in_array(mb_strtolower($file->ext), $imageExtensions, true))
                        ->sortBy('file_name')
                        ->values();
                    $fileModalScope = 'reject-files-' . $note->id;
                @endphp
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
                                            <h5 class="my-0 py-1 text-uppercase">Informações da Nota</h5>
                                        </div>
                                        <div class="card-body">
                                            <p class="py-0 my-0"><strong>Número da Nota:</strong> {{ $note->note }}
                                            </p>

                                            @php
                                                $orders = $note->orders
                                                    ->map(function ($order) {
                                                        return $order->ordem .
                                                            ' (' .
                                                            explode(' ', $order->statusSist)[0] .
                                                            ')';
                                                    })
                                                    ->toArray();
                                                $ordens = implode(', ', $orders);
                                            @endphp
                                            <p class="py-0 my-0"><strong>Ordens:</strong> <span
                                                    class="{{ strpos($ordens, 'ENT') !== false || strpos($ordens, 'ENCE') !== false ? 'text-danger' : '' }}">{{ $ordens }}</span>
                                            </p>
                                            <p class="py-0 my-0"><strong>Status:</strong> {{ $note->nstats }}</p>
                                            <p class="py-0 my-0"><strong>CentroTrabalho:</strong>
                                                {{ $note->centerjob }}</p>
                                            <p class="py-0 my-0"><strong>Material:</strong>
                                                {{ $note->material }}</p>
                                            <p class="py-0 my-0"><strong>Rubrica:</strong> {{ $note->rubrica }}</p>
                                            <p class="py-0 my-0"><strong>Municipio:</strong> {{ $note->lexp }}</p>
                                            <p class="py-0 my-0"><strong>Data Status:</strong> <span
                                                    class="fw-bold text-primary">{{ $note->dt_status->format('d/m/Y H:i') }}</span>
                                            </p>
                                            <p class="py-0 my-0"><strong>Tácito:</strong> <span
                                                    class="fw-bold text-danger">
                                                    {{ $note->approval?->tacit ? 'SIM' : 'NÃO' }}</span>
                                            </p>
                                            @php
                                                $businessDays = $note->approval->created_at
                                                    ->startOfDay()
                                                    ->diffInDaysFiltered(function (Carbon $date) {
                                                        return $date->isWeekday(); // Segunda a sexta
                                                    }, now()->startOfDay());

                                            @endphp
                                            <p class="py-0 my-0"><strong>Data em Validação:</strong>
                                                <span class="fw-bold text-primary">
                                                    {{ $note->approval?->created_at->format('d/m/Y H:i:s') }}</span>
                                                <span class="fw-bold text-danger">
                                                    ({{ $businessDays }} dias úteis)</span>
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
                                                                                class="fw-bold text-primary">{{ $reclaim->Production->att_at?->format('d/m/Y H:i') }}</span>
                                                                        @endif
                                                                    </td>
                                                                </tr>
                                                                <tr>
                                                                    <td class="text-end fw-bold col-3">Status:</td>
                                                                    <td class="col align-middle">
                                                                        @if ($reclaim->production)
                                                                            <span
                                                                                class="badge {{ Notestatus::status($reclaim->Production->status)->colorbg }}">{{ Notestatus::status($reclaim->Production->status)->status }}</span>
                                                                        @endif
                                                                    </td>
                                                                </tr>
                                                                <tr>
                                                                    <td class="text-end fw-bold col-3">Data Conclusao:
                                                                    </td>
                                                                    <td class="col align-middle">
                                                                        @if ($reclaim->Production)
                                                                            <span
                                                                                class="fw-bold text-success">{{ $reclaim->Production->completed_at?->format('d/m/Y H:i') }}</span>
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
                                        @if ($attachedFiles->isNotEmpty())
                                            <div class="reject-files" data-reject-files="{{ $fileModalScope }}">
                                                <ul class="nav nav-pills gap-2 mb-3" role="tablist">
                                                    @foreach ($groupedFiles as $serviceName => $group)
                                                        @php
                                                            $tabId = $fileModalScope . '-tab-' . $loop->index;
                                                            $paneId = $fileModalScope . '-pane-' . $loop->index;
                                                        @endphp
                                                        <li class="nav-item" role="presentation">
                                                            <button class="nav-link py-1 px-3 {{ $loop->first ? 'active' : '' }}"
                                                                id="{{ $tabId }}" type="button"
                                                                data-bs-toggle="pill" data-bs-target="#{{ $paneId }}"
                                                                role="tab" aria-controls="{{ $paneId }}"
                                                                aria-selected="{{ $loop->first ? 'true' : 'false' }}">
                                                                {{ $serviceName }}
                                                                <span class="badge text-bg-light ms-1">{{ $group->count() }}</span>
                                                            </button>
                                                        </li>
                                                    @endforeach
                                                </ul>

                                                <div class="tab-content">
                                                    @foreach ($groupedFiles as $serviceName => $group)
                                                        @php
                                                            $paneId = $fileModalScope . '-pane-' . $loop->index;
                                                            $serviceFiles = $group->sortBy('file_name')->values();
                                                            $imageFiles = $serviceFiles
                                                                ->filter(fn ($file) => in_array(mb_strtolower($file->ext), $imageExtensions, true))
                                                                ->values();
                                                            $documentFiles = $serviceFiles
                                                                ->reject(fn ($file) => in_array(mb_strtolower($file->ext), $imageExtensions, true))
                                                                ->values();
                                                        @endphp
                                                        <div class="tab-pane fade {{ $loop->first ? 'show active' : '' }}"
                                                            id="{{ $paneId }}" role="tabpanel" tabindex="0">
                                                            @if ($imageFiles->isNotEmpty())
                                                                <div class="reject-thumb-grid mb-3">
                                                                    @foreach ($imageFiles as $file)
                                                                        @php
                                                                            $hasPreviewImage = $canPreviewImage($file);
                                                                            $previewIndex = $previewFiles->search(fn ($previewFile) => $previewFile->id === $file->id);
                                                                        @endphp
                                                                        <div class="reject-thumb" title="Visualizar {{ $file->file_name }}">
                                                                            <button type="button" class="reject-thumb-preview"
                                                                                data-reject-preview-open
                                                                                data-preview-modal="#{{ $fileModalScope }}-preview-modal"
                                                                                data-preview-carousel="#{{ $fileModalScope }}-preview-carousel"
                                                                                data-preview-index="{{ $previewIndex }}">
                                                                                @if ($hasPreviewImage)
                                                                                    <img src="{{ route('files.preview', ['file' => $file->id]) }}"
                                                                                        alt="{{ $file->file_name }}" loading="lazy"
                                                                                        onerror="this.classList.add('d-none'); this.nextElementSibling.classList.remove('d-none');">
                                                                                    <span class="reject-thumb-placeholder d-none">SEM IMAGEM</span>
                                                                                @else
                                                                                    <span class="reject-thumb-placeholder">SEM IMAGEM</span>
                                                                                @endif
                                                                                <span class="reject-thumb-name">{{ $file->file_name }}</span>
                                                                            </button>
                                                                            <a class="reject-thumb-download"
                                                                                href="{{ route('files.download', ['file' => $file->id]) }}"
                                                                                title="Baixar arquivo">
                                                                                <i class="ri-download-2-line"></i>
                                                                            </a>
                                                                        </div>
                                                                    @endforeach
                                                                </div>
                                                            @endif

                                                            @if ($documentFiles->isNotEmpty())
                                                                <div class="reject-file-list">
                                                                    @foreach ($documentFiles as $file)
                                                                        <div class="reject-file-row">
                                                                            <div class="reject-file-main">
                                                                                <i class="{{ FileIcon::getIcon($file->ext)->icon }} reject-file-icon"></i>
                                                                                <div>
                                                                                    <strong>{{ $file->file_name }}</strong>
                                                                                    <small>{{ mb_strtoupper($file->ext) }}</small>
                                                                                </div>
                                                                            </div>
                                                                            <a class="btn btn-sm btn-outline-primary"
                                                                                href="{{ route('files.download', ['file' => $file->id]) }}"
                                                                                title="Baixar arquivo">
                                                                                <i class="ri-download-2-line"></i>
                                                                            </a>
                                                                        </div>
                                                                    @endforeach
                                                                </div>
                                                            @endif
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        @else
                                            <div class="reject-empty-files">
                                                <i class="ri-folder-open-line"></i>
                                                <span>Sem arquivos anexados.</span>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <div class="col-12">
                                <div class="card">
                                    <div class="card-header edp-bg-sprucegreen-70 text-edp-verde">
                                        <h5 class="my-0 py-1">RESPONDER A ANALISE</h5>
                                    </div>
                                    <div class="card-body">
                                        @if (count($retornoInternos) && !$retornoInternos->last()->completed)
                                            <div class="card text-bg-danger">
                                                <div class="card-body">
                                                    <h4 class="text-center">ATENÇÃO</h4>
                                                    <p class="text-center">
                                                        Esta obra ainda aguarda retorno da <strong>Resolução
                                                            Interna</strong> enviado em:
                                                        <strong
                                                            class="text-warning">{{ $retornoInternos->last()->created_at->format('d/m/Y H:i') }}</strong>
                                                        para
                                                        <strong class="text-warning text-uppercase">
                                                            {{ $retornoInternos->last()->service->service }}</strong>
                                                        com o motivo de <strong
                                                            class="text-warning">{{ $retornoInternos->last()->category }}</strong>.
                                                        <br>
                                                        Aguarde ou prossiga se realmente considerar nescessário.
                                                    </p>
                                                </div>
                                            </div>
                                        @endif
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
                        <button type="button" class="btn btn-primary" wire:click.prevent="preReject">Enviar</button>
                    </div>
                </div>

                @if ($previewFiles->isNotEmpty())
                    <div wire:ignore.self class="modal fade reject-preview-modal"
                        id="{{ $fileModalScope }}-preview-modal" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-xl modal-dialog-centered">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">VISUALIZAÇÃO DE IMAGEM</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"
                                        aria-label="Fechar"></button>
                                </div>
                                <div class="modal-body p-0">
                                    <div id="{{ $fileModalScope }}-preview-carousel" class="carousel slide"
                                        data-bs-interval="false">
                                        <div class="carousel-inner">
                                            @foreach ($previewFiles as $file)
                                                @php
                                                    $hasPreviewImage = $canPreviewImage($file);
                                                @endphp
                                                <div class="carousel-item {{ $loop->first ? 'active' : '' }}">
                                                    <div class="reject-preview-stage">
                                                        @if ($hasPreviewImage)
                                                            <img src="{{ route('files.preview', ['file' => $file->id]) }}"
                                                                alt="{{ $file->file_name }}"
                                                                onerror="this.classList.add('d-none'); this.nextElementSibling.classList.remove('d-none');">
                                                            <div class="reject-preview-placeholder d-none">SEM IMAGEM</div>
                                                        @else
                                                            <div class="reject-preview-placeholder">SEM IMAGEM</div>
                                                        @endif
                                                    </div>
                                                    <div class="reject-preview-caption">
                                                        <strong>{{ $file->file_name }}</strong>
                                                        <a class="btn btn-sm btn-outline-primary"
                                                            href="{{ route('files.download', ['file' => $file->id]) }}">
                                                            <i class="ri-download-2-line me-1"></i>Baixar
                                                        </a>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                        @if ($previewFiles->count() > 1)
                                            <button class="carousel-control-prev" type="button"
                                                data-bs-target="#{{ $fileModalScope }}-preview-carousel"
                                                data-bs-slide="prev">
                                                <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                                                <span class="visually-hidden">Anterior</span>
                                            </button>
                                            <button class="carousel-control-next" type="button"
                                                data-bs-target="#{{ $fileModalScope }}-preview-carousel"
                                                data-bs-slide="next">
                                                <span class="carousel-control-next-icon" aria-hidden="true"></span>
                                                <span class="visually-hidden">Próxima</span>
                                            </button>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
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

@pushOnce('css')
    <style>
        .reject-thumb-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(136px, 1fr));
            gap: .75rem;
        }

        .reject-thumb {
            position: relative;
            border: 1px solid #e1e5ea;
            border-radius: 8px;
            background: #fff;
            padding: .45rem;
            transition: border-color .15s ease, box-shadow .15s ease, transform .15s ease;
        }

        .reject-thumb:hover {
            border-color: #1f6f54;
            box-shadow: 0 8px 20px rgba(15, 23, 42, .12);
            transform: translateY(-1px);
        }

        .reject-thumb-preview {
            display: block;
            width: 100%;
            border: 0;
            background: transparent;
            padding: 0;
            text-align: left;
        }

        .reject-thumb img,
        .reject-thumb-placeholder {
            width: 100%;
            aspect-ratio: 4 / 3;
            border-radius: 6px;
            background: #f1f3f5;
        }

        .reject-thumb img {
            display: block;
            object-fit: cover;
        }

        .reject-thumb-placeholder {
            display: grid;
            place-items: center;
            color: #6c757d;
            font-size: .78rem;
            font-weight: 800;
            text-align: center;
        }

        .reject-thumb-name {
            display: -webkit-box;
            -webkit-box-orient: vertical;
            -webkit-line-clamp: 2;
            min-height: 2.5em;
            margin-top: .45rem;
            overflow: hidden;
            color: #343a40;
            font-size: .78rem;
            font-weight: 700;
            line-height: 1.25;
            overflow-wrap: anywhere;
        }

        .reject-thumb-download {
            display: grid;
            place-items: center;
            position: absolute;
            top: .65rem;
            right: .65rem;
            width: 32px;
            height: 32px;
            border: 1px solid rgba(255, 255, 255, .7);
            border-radius: 8px;
            background: rgba(33, 37, 41, .78);
            color: #fff;
            font-size: 1rem;
            text-decoration: none;
            transition: background .15s ease, transform .15s ease;
        }

        .reject-thumb-download:hover {
            background: #1f6f54;
            color: #fff;
            transform: translateY(-1px);
        }

        .reject-file-list {
            display: grid;
            gap: .5rem;
        }

        .reject-file-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: .75rem;
            min-height: 58px;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            background: #fff;
            padding: .55rem .65rem;
        }

        .reject-file-main {
            display: flex;
            align-items: center;
            gap: .65rem;
            min-width: 0;
        }

        .reject-file-main strong {
            display: block;
            color: #212529;
            line-height: 1.25;
            overflow-wrap: anywhere;
        }

        .reject-file-main small {
            color: #6c757d;
            font-weight: 700;
        }

        .reject-file-icon {
            flex: 0 0 auto;
            color: #1f6f54;
            font-size: 1.75rem;
        }

        .reject-empty-files {
            display: grid;
            place-items: center;
            min-height: 110px;
            border: 1px dashed #cfd6dd;
            border-radius: 8px;
            background: #fff;
            color: #6c757d;
            font-weight: 700;
            text-align: center;
        }

        .reject-empty-files i {
            font-size: 2rem;
        }

        .reject-preview-modal {
            z-index: 1065;
        }

        .modal-backdrop.show:nth-of-type(2) {
            z-index: 1060;
        }

        .reject-preview-stage {
            display: grid;
            place-items: center;
            min-height: 70vh;
            background: #111827;
        }

        .reject-preview-stage img {
            display: block;
            max-width: 100%;
            max-height: 70vh;
            object-fit: contain;
        }

        .reject-preview-placeholder {
            display: grid;
            place-items: center;
            width: min(620px, 90%);
            min-height: 360px;
            border: 1px dashed #6b7280;
            border-radius: 8px;
            background: #374151;
            color: #f8f9fa;
            font-size: 1.25rem;
            font-weight: 800;
            text-align: center;
        }

        .reject-preview-caption {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            padding: .85rem 1rem;
            background: #fff;
        }

        .reject-preview-caption strong {
            overflow-wrap: anywhere;
        }

        @media (max-width: 576px) {
            .reject-file-row,
            .reject-preview-caption {
                align-items: stretch;
                flex-direction: column;
            }
        }
    </style>
@endpushOnce

@pushOnce('script')
    <script>
        document.addEventListener('click', function(event) {
            const trigger = event.target.closest('[data-reject-preview-open]');

            if (!trigger || typeof bootstrap === 'undefined') {
                return;
            }

            const modal = document.querySelector(trigger.getAttribute('data-preview-modal'));
            const carousel = document.querySelector(trigger.getAttribute('data-preview-carousel'));
            const index = parseInt(trigger.getAttribute('data-preview-index') || '0', 10);

            if (!modal || !carousel) {
                return;
            }

            bootstrap.Carousel.getOrCreateInstance(carousel, {
                interval: false,
                ride: false,
                touch: true
            }).to(index);

            bootstrap.Modal.getOrCreateInstance(modal, {
                backdrop: true,
                keyboard: true,
                focus: true
            }).show();
        });

        document.addEventListener('hidden.bs.modal', function() {
            if (document.querySelector('.modal.show')) {
                document.body.classList.add('modal-open');
            }
        });
    </script>
@endpushOnce
