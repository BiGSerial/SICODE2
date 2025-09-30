@php
    use Carbon\Carbon;
    use Carbon\CarbonInterval;
    use App\Custom\Notestatus;
    use App\Helpers\FileIcon;
    use App\Helpers\DaysLeft;
    use Illuminate\Support\Str;
    use App\Helpers\FilesCustom;
@endphp

<div class="modal fade" id="modalApproveReclaim" tabindex="-1" aria-labelledby="modalEntityProtocolLabel" aria-hidden="true"
    wire:ignore.self>
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content  rounded-4 shadow-lg" style="font-family: 'Nunito', sans-serif;">

            <!-- HEADER -->
            <div class="modal-header bg-primary bg-opacity-25 text-primary">
                <h5 class="modal-title d-flex align-items-center gap-2">
                    <i class="bi bi-file-earmark-text-fill text-primary"></i>
                    Resolução de Retorno Interno
                    <span
                        class="badge bg-success bg-opacity-25 border border-success">{{ $item?->externals->first()->entity?->nick }}</span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                    aria-label="Close"></button>
            </div>

            <!-- BODY -->
            <div class="modal-body">
                <div class="row g-4">

                    <!-- COLUNA ESQUERDA -->
                    <div class="col-lg-6 d-flex flex-column gap-4">

                        <!-- DADOS DA NOTA -->
                        <div class="card border border-secondary">
                            <div class="card-header border-bottom border-secondary">
                                <h6 class="mb-0">Dados da Nota <span
                                        class="badge bg-secondary">#{{ $item?->note?->note }}</span>
                                </h6>
                            </div>
                            <div class="card-body small">
                                <div class="row g-3">
                                    <div class="col-6">
                                        <div class="text-muted">Cliente</div>
                                        <div class="fw-bold">{{ $item?->note?->client }}</div>
                                    </div>
                                    <div class="col-6">
                                        <div class="text-muted">rubrica</div>
                                        <span
                                            class="badge bg-warning text-dark fs-6">{{ $item?->note?->rubrica }}</span>
                                    </div>
                                    <div class="col-6">
                                        <div class="text-muted">Prazo</div>
                                        @php
                                            if ($item) {
                                                $daysLeft = new DaysLeft($item?->note);
                                            } else {
                                                $daysLeft = null;
                                            }
                                        @endphp
                                        <div class="fw-bold">{{ $daysLeft?->getLastDate() }}</div>
                                    </div>
                                    {{-- <div class="col-6">
                                        <div class="text-muted">Prioridade</div>
                                        <span class="badge bg-danger fs-6">Alta</span>
                                    </div> --}}
                                </div>
                            </div>
                        </div>

                        <!-- RECLAMAÇÃO -->
                        <div class="card border border-secondary">
                            <div class="card-header border-bottom border-secondary">
                                <h6 class="mb-0">Informações da Reclamação</h6>
                            </div>
                            <div class="card-body small">
                                <div class="row g-3">
                                    <div class="col-6">
                                        <div class="text-muted">Quem abriu</div>
                                        <div class="fw-semibold">{{ $item?->externals?->last()?->User?->name }}</div>
                                    </div>
                                    <div class="col-6">
                                        <div class="text-muted">Quando</div>
                                        <div class="fw-semibold">{{ $item?->created_at?->format('d/m/Y H:i') }}</div>
                                    </div>
                                    <div class="col-6">
                                        <div class="text-muted">Motivo</div>
                                        <div class="fw-semibold">{{ $item?->category }}</div>
                                    </div>
                                    <div class="col-6">
                                        <div class="text-muted">Categoria</div>
                                        <div class="fw-semibold">Atendimento / SLA</div>
                                    </div>
                                    <div class="col-12">
                                        <div class="text-muted">Descrição</div>
                                        <div class="p-3 bg-secondary bg-opacity-25 border border-secondary  rounded">
                                            {{ $item?->comments?->first()?->message }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>

                    <!-- COLUNA DIREITA -->
                    <div class="col-lg-6 d-flex flex-column gap-4">

                        <!-- ARQUIVOS -->
                        <div class="card border border-secondary">
                            <div class="card-header border-bottom border-secondary">
                                <h6 class="mb-0">Arquivos da Nota</h6>
                            </div>

                            @if ($item?->note?->files?->count())
                                @php
                                    /// Agrupa e renomeia “Sem Serviço” para “Outros”
                                    $grouped = $item->note->Files
                                        ->sortBy('file_name')
                                        ->groupBy(fn($f) => $f->Service->service ?? 'Outros');

                                    // Pega todas as chaves, exclui “Outros”, ordena e depois
                                    // só adiciona “Outros” se ele existir no agrupamento
                                    $services = $grouped->keys()->filter(fn($k) => $k !== 'Outros')->sort()->toArray();

                                    if ($grouped->has('Outros')) {
                                        $services[] = 'Outros';
                                    }
                                @endphp

                                <div class="accordion" id="filesByServiceAccordion">
                                    @foreach ($services as $service)
                                        @php
                                            $files = $grouped[$service];
                                            $slug = Str::slug($service);
                                        @endphp
                                        <div class="accordion-item border-secondary"
                                            wire:key="service-{{ $slug }}">
                                            <h2 class="accordion-header" id="heading{{ $slug }}">
                                                <button
                                                    class="accordion-button edp-bg-sprucegreen-20 text-white
                                                        {{ $openServiceId !== $slug ? 'collapsed' : '' }}"
                                                    type="button" data-bs-toggle="collapse"
                                                    data-bs-target="#collapse{{ $slug }}"
                                                    aria-expanded="{{ $openServiceId === $slug }}"
                                                    aria-controls="collapse{{ $slug }}">
                                                    {{ $service }}
                                                </button>
                                            </h2>
                                            <div id="collapse{{ $slug }}"
                                                class="accordion-collapse collapse
                                                    {{ $openServiceId === $slug ? 'show' : '' }}"
                                                aria-labelledby="heading{{ $slug }}"
                                                data-bs-parent="#filesByServiceAccordion" x-data
                                                x-init="$el.addEventListener('shown.bs.collapse', () => Livewire.emit('setOpenService', '{{ $slug }}'))">
                                                <div class="accordion-body p-0">
                                                    <div class="table-responsive">
                                                        <table class="table table-sm table-striped table-hover mb-0">
                                                            <thead class="table-dark">
                                                                <tr>
                                                                    <th class="text-center">
                                                                        <input
                                                                            class="form-check-input border border-1 border-secondary"
                                                                            type="checkbox">
                                                                    </th>
                                                                    <th class="text-center">Arquivo</th>
                                                                    <th class="text-center">Data</th>
                                                                    <th class="text-center">Tam</th>
                                                                    <th></th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                @foreach ($files as $file)
                                                                    @php $exists = Storage::exists($file->path); @endphp
                                                                    <tr wire:key="file-{{ $file->id }}">
                                                                        <td class="text-center align-middle">
                                                                            <input
                                                                                class="form-check-input border border-1 border-secondary"
                                                                                type="checkbox"
                                                                                value="{{ $file->id }}"
                                                                                wire:model.defer="selectedFiles">
                                                                        </td>
                                                                        <td class="text-start align-middle"
                                                                            style="cursor:pointer;"
                                                                            wire:click.prevent="downloadFile({{ $file->id }})">
                                                                            <i
                                                                                class="{{ FileIcon::getIcon($file->ext)->icon }} me-1"></i>
                                                                            {{ $file->file_name }}
                                                                        </td>
                                                                        <td class="text-center align-middle">
                                                                            {{ $file->created_at->format('d/m/Y H:i:s') }}
                                                                        </td>
                                                                        <td class="text-center align-middle">
                                                                            {{ $exists ? number_format(Storage::size($file->path) / 1024, 0) . ' KB' : '---' }}
                                                                        </td>
                                                                        <td class="text-center align-middle">
                                                                            @can('admin')
                                                                                <i class="ri-pencil-fill text-primary fs-5"
                                                                                    style="cursor:pointer;"
                                                                                    wire:click.prevent="$emitTo('files.manager.fileedit','editFile',{{ $file }}) ">
                                                                                </i>
                                                                                <i class="ri-delete-bin-2-line text-danger fs-5"
                                                                                    style="cursor:pointer;"
                                                                                    wire:click.prevent="$emitTo('files.manager.fileedit', 'deleteFile', {{ $file }})">

                                                                                </i>
                                                                            @endcan
                                                                        </td>
                                                                    </tr>
                                                                @endforeach
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach


                                    <div class="card-footer text-end">
                                        <button class="btn btn-sm btn-primary" wire:click.prevent="zipFiles">
                                            <i class="bx bxs-cloud-download"></i> Baixar Selecionados
                                        </button>
                                    </div>
                                @else
                                    <div class="card-body">
                                        <h6 class="text-center text-muted">SEM ARQUIVOS</h6>
                                    </div>
                            @endif
                        </div>
                    </div>

                    <!-- RESPOSTA -->
                    <div class="card border border-secondary">
                        <div class="card-header border-bottom border-secondary">
                            <h6 class="mb-0">Resposta ao Reclaim</h6>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-12">
                                    <div class="text-muted">Respondido por</div>
                                    <div class="fw-semibold">{{ $item?->production?->user?->name }}</div>
                                </div>
                                <div class="col-6">
                                    <div class="text-muted">Data da resposta</div>
                                    <div class="fw-semibold">
                                        {{ $item?->production?->completed_at?->format('d/m/Y H:i') }}</div>
                                </div>
                                <div class="col-6">
                                    <div class="text-muted">Status</div>
                                    <div class="fw-semibold">
                                        @if ($item?->production)
                                            <span
                                                class="badge {{ Notestatus::status($item->production->status)->colorbg }}">{{ Notestatus::status($item->production->status)->status }}</span>
                                        @else
                                            <span class="badge text-bg-secondary">Pendente</span>
                                        @endif
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="text-muted">Justificativa</div>
                                    <div class="p-3 bg-secondary bg-opacity-25 border border-secondary rounded">
                                        {{ $item?->production?->analise?->info }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>

        <!-- FOOTER -->
        <div
            class="modal-footer border-top border-secondary d-flex justify-content-between bg-primary bg-opacity-50 text-white">
            <div class="d-flex align-items-center gap-2">
                <i class="bi bi-exclamation-triangle-fill"></i> Revise as informações antes de concluir
            </div>
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-outline-light" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-outline-danger"
                    wire:click.prevent="toRejectApprove">Recusar</button>
                <button type="button" class="btn btn-primary" wire:click.prevent="toConfirmApprove">Aprovar</button>
            </div>
        </div>

    </div>
</div>
</div>
