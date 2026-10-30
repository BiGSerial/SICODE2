@php
    use App\Helpers\DaysLeft;
    use App\Helpers\FileIcon;
    use Illuminate\Support\Facades\Storage;
    use Illuminate\Support\Str;

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
@endphp

<div>
    <x-show-loading />

    <div wire:ignore.self class="modal fade" id="modal_partial_info" tabindex="-1" aria-labelledby="modalPartialInfoLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content cpop cpop-review">
                @if ($form)
                    @php
                        $note         = $form->Note;
                        $files        = $form->Files ?? collect();
                        $groupedFiles = $files
                            ->groupBy(fn ($file) => mb_strtoupper($file->service->service ?? 'OUTROS'))
                            ->sortKeys();
                        $previewFiles = $files
                            ->filter(fn ($f) => in_array(mb_strtolower($f->ext), $imageExtensions, true))
                            ->sortBy('file_name')
                            ->values();
                        $modalScope = 'partial-' . $form->id;

                        $money     = fn (?float $v) => $v === null ? '—' : 'R$ ' . number_format($v, 2, ',', '.');
                        $planned   = (float) ($form->Orders?->sum('custPlanejado') ?? 0);
                        $value     = (float) $form->value;
                        $age       = (int) $form->created_at->copy()->startOfDay()->diffInDays(now()->startOfDay());
                        $due       = (new DaysLeft($note))->getLastDate();
                        $dueDays   = (new DaysLeft($note))->getDaysLeft();
                        $pepTone   = match ($pep['state']) { 'short' => 'red', 'ok' => 'green', default => 'muted' };
                        $pepLabel  = match ($pep['state']) { 'short' => $pep['reason'] === 'ads_exceeds' ? 'ADS acima do saldo' : 'Sem saldo disponível', 'ok' => 'Cobre o pedido', default => 'Sem saldo sincronizado' };
                        $coverage  = $pep['total'] !== null && $value > 0 ? min(100, max(0, $pep['total'] / $value * 100)) : null;
                        $statusNote = $note->type_note == 2 ? ($note->nstats ?? '-') : ($note->centerjob ?? '-');
                    @endphp

                    <header class="cpop-hero">
                        <div class="cpop-hero-main">
                            <div class="cpop-hero-eyebrow">Informe parcial · aguardando aprovação</div>
                            <h2 class="cpop-hero-title" id="modalPartialInfoLabel">Nota/OV {{ $note->note }}</h2>
                            <div class="cpop-hero-meta">
                                <span><i class="ri-map-pin-2-line"></i> {{ mb_strtoupper($note->lexp ?? '—') }}</span>
                                <span><i class="ri-building-2-line"></i> {{ mb_strtoupper($form->Company->name ?? '—') }}</span>
                                <span><i class="ri-time-line"></i> enviada {{ $form->created_at?->format('d/m/Y H:i') }} · há {{ $age }} {{ $age === 1 ? 'dia' : 'dias' }}</span>
                            </div>
                            <x-engineers.partial-steps :partial="$form" dark class="cpop-hero-steps" />
                            <div class="cpop-hero-chips">
                                <span class="cpop-chip">{{ $form->scopeLabel() }}</span>
                                @if ($note->rubrica)
                                    <span class="cpop-chip">{{ mb_strtoupper($note->rubrica) }}</span>
                                @endif
                                <span class="cpop-chip">{{ $files->count() }} {{ $files->count() === 1 ? 'anexo' : 'anexos' }}</span>
                            </div>
                        </div>
                        <button type="button" class="cpop-close cpop-close-light" data-bs-dismiss="modal" aria-label="Fechar"><i class="ri-close-line"></i></button>
                    </header>

                    <div class="cpop-body">
                        {{-- Indicadores --}}
                        <div class="cpop-kpis">
                            <div class="cpop-kpi tone-blue">
                                <i class="ri-money-dollar-circle-line"></i>
                                <div>
                                    <small>Valor da parcial (ADS)</small>
                                    <strong>{{ $money($value) }}</strong>
                                    <span>{{ $planned > 0 ? round($value / $planned * 100) . '% do planejado' : 'sem planejado' }}</span>
                                </div>
                            </div>
                            <div class="cpop-kpi tone-teal">
                                <i class="ri-hammer-line"></i>
                                <div>
                                    <small>Planejado da obra</small>
                                    <strong>{{ $money($planned) }}</strong>
                                    <span>{{ $form->Orders->count() }} {{ $form->Orders->count() === 1 ? 'ordem' : 'ordens' }}</span>
                                </div>
                            </div>
                            <div class="cpop-kpi tone-{{ $pepTone }}">
                                <i class="ri-wallet-3-line"></i>
                                <div>
                                    <small>Saldo do PEP</small>
                                    <strong>{{ $money($pep['total']) }}</strong>
                                    <span>{{ $pepLabel }}</span>
                                </div>
                            </div>
                            <div class="cpop-kpi tone-{{ $dueDays < 0 ? 'red' : ($dueDays <= 3 ? 'amber' : 'green') }}">
                                <i class="ri-calendar-check-line"></i>
                                <div>
                                    <small>Prazo da nota</small>
                                    <strong>{{ $due }}</strong>
                                    <span>{{ $dueDays < 0 ? abs($dueDays) . ' dia(s) em atraso' : ($dueDays === 0 ? 'vence hoje' : $dueDays . ' dia(s) restantes') }}</span>
                                </div>
                            </div>
                        </div>

                        @if ($pep['state'] === 'short')
                            <div class="cpop-alert tone-red" role="alert">
                                <i class="ri-error-warning-line"></i>
                                <div>
                                    <strong>{{ $pep['reason'] === 'ads_exceeds' ? 'O valor da parcial passa do saldo do PEP.' : 'O PEP desta obra está sem saldo disponível.' }}</strong>
                                    <span>
                                        {{ $pep['alert'] ? 'Este informe já está na lista de observação' . ($pep['alert']->status === 'open' ? ' e aguarda tratamento.' : ' (tratado).') : 'Avalie com a gestão do orçamento antes de aprovar.' }}
                                        <a href="{{ route('engineers.pep_alerts') }}" class="ms-1">Ver informes sem saldo</a>
                                    </span>
                                </div>
                            </div>
                        @endif

                        <div class="row g-3">
                            <div class="col-12 col-lg-7">
                                {{-- Obra e pedido --}}
                                <section class="cpop-section">
                                    <h3 class="cpop-section-title"><i class="ri-building-line"></i> Obra e pedido</h3>
                                    <dl class="cpop-facts">
                                        <div><dt>Cliente</dt><dd>{{ mb_strtoupper($note->client ?? '—') }}</dd></div>
                                        <div><dt>Município</dt><dd>{{ mb_strtoupper($note->lexp ?? '—') }}</dd></div>
                                        <div><dt>Material</dt><dd>{{ mb_strtoupper($note->material ?? '—') }}</dd></div>
                                        <div><dt>Status da nota</dt><dd>{{ $statusNote }}</dd></div>
                                        <div><dt>Responsável pelo envio</dt><dd>{{ $form->User->name ?? '—' }}</dd></div>
                                        <div><dt>Responsável informado</dt><dd>{{ $form->responsible ?: '—' }}</dd></div>
                                    </dl>

                                    @php $contractorNote = trim((string) $form->observation); @endphp
                                    <div class="cpop-callout {{ $contractorNote ? 'tone-amber' : 'tone-muted' }}">
                                        <div class="cpop-callout-head">
                                            <i class="ri-chat-quote-line"></i>
                                            <span>Observação da empreiteira</span>
                                            <em>{{ mb_strtoupper($form->Company->name ?? '—') }}</em>
                                        </div>
                                        <p class="cpop-callout-text">{{ $contractorNote ?: 'A empreiteira não registrou observação neste envio.' }}</p>
                                        @if ($form->responsible)
                                            <div class="cpop-callout-by"><i class="ri-user-3-line"></i> {{ $form->responsible }} · {{ $form->created_at?->format('d/m/Y H:i') }}</div>
                                        @endif
                                    </div>
                                </section>

                                {{-- Ordens e PEP --}}
                                <section class="cpop-section">
                                    <h3 class="cpop-section-title"><i class="ri-list-check-2"></i> Ordens e PEP</h3>
                                    @if ($form->Orders->isEmpty())
                                        <div class="cpop-muted">Nenhuma ordem vinculada a esta parcial.</div>
                                    @else
                                        <div class="table-responsive">
                                            <table class="table table-sm align-middle mb-0 cpop-table">
                                                <colgroup><col class="col-pct-24"><col class="col-pct-30"><col class="col-pct-23"><col></colgroup>
                                                <thead>
                                                    <tr>
                                                        <th scope="col">Ordem</th>
                                                        <th scope="col">PEP</th>
                                                        <th scope="col" class="text-end">Saldo</th>
                                                        <th scope="col" class="text-end">Planejado</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach ($form->Orders as $order)
                                                        @php $available = $pep['balances'][trim((string) $order->pep)] ?? null; @endphp
                                                        <tr wire:key="po-{{ $order->id }}">
                                                            <td class="fw-bold">{{ $order->ordem }}</td>
                                                            <td class="text-truncate" title="{{ $order->pep }}">{{ $order->pep ?: '—' }}</td>
                                                            <td class="text-end {{ $available !== null && $available <= 0 ? 'text-danger fw-bold' : '' }}">{{ $money($available) }}</td>
                                                            <td class="text-end">{{ $money((float) $order->custPlanejado) }}</td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                        <div class="cpop-muted mt-2">Só o PEP terminado em -2 carrega o orçamento; os demais aparecem sem saldo.</div>
                                        @if ($coverage !== null)
                                            <div class="cpop-cover mt-2" title="Quanto do valor da parcial o saldo do PEP cobre">
                                                <span class="cpop-cover-bar"><span class="tone-{{ $pepTone }}" style="--pct: {{ $coverage }}"></span></span>
                                                <small>Saldo cobre {{ round($coverage) }}% do valor da parcial</small>
                                            </div>
                                        @endif
                                    @endif
                                </section>
                            </div>

                            <div class="col-12 col-lg-5">
                                {{-- Anexos --}}
                                <section class="cpop-section h-100">
                                    <h3 class="cpop-section-title"><i class="ri-attachment-2"></i> Anexos <span class="cpop-count">{{ $files->count() }}</span></h3>
                                    @if ($files->isNotEmpty())
                                    <div class="work-files" data-work-files="{{ $modalScope }}" wire:ignore.self>
                                        <ul class="nav nav-pills work-file-tabs" id="{{ $modalScope }}-tabs" role="tablist">
                                            @foreach ($groupedFiles as $serviceName => $group)
                                                @php
                                                    $tabId = $modalScope . '-tab-' . $loop->index;
                                                    $paneId = $modalScope . '-pane-' . $loop->index;
                                                @endphp
                                                <li class="nav-item" role="presentation">
                                                    <button class="nav-link {{ $loop->first ? 'active' : '' }}"
                                                        id="{{ $tabId }}" data-bs-toggle="pill"
                                                        data-bs-target="#{{ $paneId }}" type="button" role="tab"
                                                        aria-controls="{{ $paneId }}"
                                                        aria-selected="{{ $loop->first ? 'true' : 'false' }}">
                                                        {{ $serviceName }}
                                                        <span class="badge rounded-pill text-bg-light ms-1">
                                                            {{ $group->count() }}
                                                        </span>
                                                    </button>
                                                </li>
                                            @endforeach
                                        </ul>

                                        <div class="tab-content mt-3" id="{{ $modalScope }}-tab-content">
                                            @foreach ($groupedFiles as $serviceName => $group)
                                                @php
                                                    $paneId = $modalScope . '-pane-' . $loop->index;
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
                                                        <div class="work-thumb-grid mb-3">
                                                            @foreach ($imageFiles as $file)
                                                                @php
                                                                    $hasPreviewImage = $canPreviewImage($file);
                                                                    $previewIndex = $previewFiles->search(fn ($previewFile) => $previewFile->id === $file->id);
                                                                @endphp
                                                                <div class="work-thumb work-thumb-image"
                                                                    title="Visualizar {{ $file->file_name }}">
                                                                    <button type="button" class="work-thumb-preview"
                                                                        data-work-preview-open
                                                                        data-preview-modal="#{{ $modalScope }}-preview-modal"
                                                                        data-preview-carousel="#{{ $modalScope }}-preview-carousel"
                                                                        data-preview-index="{{ $previewIndex }}">
                                                                        @if ($hasPreviewImage)
                                                                        <img src="{{ route('files.preview', ['file' => $file->id, 'thumbnail' => 1]) }}"
                                                                                alt="{{ $file->file_name }}" loading="lazy"
                                                                                onerror="this.classList.add('d-none'); this.nextElementSibling.classList.remove('d-none');">
                                                                            <span class="work-thumb-placeholder d-none">SEM IMAGEM</span>
                                                                        @else
                                                                            <span class="work-thumb-placeholder">SEM IMAGEM</span>
                                                                        @endif
                                                                        <span class="work-thumb-name">{{ $file->file_name }}</span>
                                                                    </button>
                                                                    <a class="work-thumb-download"
                                                                        href="{{ route('files.download', ['file' => $file->id]) }}"
                                                                        title="Baixar arquivo">
                                                                        <i class="ri-download-2-line"></i>
                                                                    </a>
                                                                </div>
                                                            @endforeach
                                                        </div>
                                                    @endif

                                                    @if ($documentFiles->isNotEmpty())
                                                        <div class="work-file-list">
                                                            @foreach ($documentFiles as $file)
                                                                <div class="work-file-row">
                                                                    <div class="work-file-main">
                                                                        <i class="{{ FileIcon::getIcon($file->ext)->icon }} work-file-icon"></i>
                                                                        <div>
                                                                            <strong>{{ $file->file_name }}</strong>
                                                                            <small>{{ mb_strtoupper($file->ext) }}</small>
                                                                        </div>
                                                                    </div>
                                                                    <div class="work-file-actions">
                                                                        <a class="btn btn-sm btn-outline-primary"
                                                                            href="{{ route('files.download', ['file' => $file->id]) }}"
                                                                            title="Baixar arquivo">
                                                                            <i class="ri-download-2-line"></i>
                                                                        </a>
                                                                    </div>
                                                                </div>
                                                            @endforeach
                                                        </div>
                                                    @endif
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @else
                                    <div class="work-empty-state">
                                        <i class="ri-folder-open-line"></i>
                                        <strong>NENHUM ARQUIVO ANEXADO</strong>
                                    </div>
                                @endif
                                </section>
                            </div>
                        </div>
                    </div>

                    <footer class="cpop-decision">
                        <div class="cpop-decision-head">
                            <span class="cpop-decision-icon"><i class="ri-draft-line"></i></span>
                            <div>
                                <h3 class="cpop-decision-title">Parecer da Engenharia <span class="cpop-required">obrigatório</span></h3>
                                <p class="cpop-decision-hint">Registre a análise: ela fica no histórico da parcial. <strong>Aprovar</strong> libera a obra para Fiscalização e Medição; <strong>rejeitar</strong> devolve para novo envio da empreiteira.</p>
                            </div>
                        </div>
                        <div class="cpop-decision-body">
                            <textarea id="engineerFeedback" class="form-control cpop-decision-text" wire:model.defer="engineer_feedback" rows="3"
                                aria-label="Parecer da Engenharia" placeholder="Escreva o parecer aqui…"></textarea>
                            <div class="cpop-decision-actions">
                                <button type="button" class="btn btn-danger btn-dash cpop-btn-reject" wire:click="toReject"><i class="ri-close-circle-line"></i> Rejeitar</button>
                                <button type="button" class="btn btn-success btn-dash cpop-btn-approve" wire:click="toApprove"><i class="ri-check-double-line"></i> Aprovar parcial</button>
                            </div>
                        </div>
                    </footer>
                @endif
            </div>
        </div>
    </div>

    @if ($form && $previewFiles->isNotEmpty())
        <div class="modal fade work-preview-modal" id="{{ $modalScope }}-preview-modal" tabindex="-1"
            aria-hidden="true" wire:ignore.self>
            <div class="modal-dialog modal-xl modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">VISUALIZAÇÃO RÁPIDA</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                    </div>
                    <div class="modal-body p-0">
                        <div id="{{ $modalScope }}-preview-carousel" class="carousel slide" data-bs-interval="false">
                            <div class="carousel-inner">
                                @foreach ($previewFiles as $file)
                                    @php
                                        $hasPreviewImage = $canPreviewImage($file);
                                    @endphp
                                    <div class="carousel-item {{ $loop->first ? 'active' : '' }}">
                                        <div class="work-preview-stage">
                                            @if ($hasPreviewImage)
                                                <img src="{{ route('files.preview', ['file' => $file->id]) }}"
                                                    alt="{{ $file->file_name }}"
                                                    onerror="this.classList.add('d-none'); this.nextElementSibling.classList.remove('d-none');">
                                                <div class="work-preview-placeholder d-none">SEM IMAGEM</div>
                                            @else
                                                <div class="work-preview-placeholder">SEM IMAGEM</div>
                                            @endif
                                        </div>
                                        <div class="work-preview-caption">
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
                                    data-bs-target="#{{ $modalScope }}-preview-carousel" data-bs-slide="prev">
                                    <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                                    <span class="visually-hidden">Anterior</span>
                                </button>
                                <button class="carousel-control-next" type="button"
                                    data-bs-target="#{{ $modalScope }}-preview-carousel" data-bs-slide="next">
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
</div>

@pushOnce('css')
    <style>
        .work-show-modal {
            border: 0;
            border-radius: 8px;
            overflow: hidden;
        }

        .work-summary-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: .75rem;
        }

        .work-summary-item,
        .work-card {
            border: 1px solid rgba(0, 0, 0, .08);
            border-radius: 8px;
            box-shadow: 0 2px 12px rgba(15, 23, 42, .06);
        }

        .work-summary-item {
            background: #fff;
            padding: .85rem 1rem;
        }

        .work-summary-item span,
        .work-info-row span,
        .work-file-row small {
            display: block;
            color: #6c757d;
            font-size: .72rem;
            font-weight: 700;
            letter-spacing: 0;
            text-transform: uppercase;
        }

        .work-summary-item strong {
            display: block;
            margin-top: .2rem;
            color: #212529;
            font-size: .98rem;
            overflow-wrap: anywhere;
        }

        .work-info-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: .65rem;
        }

        .work-info-row {
            min-height: 64px;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            background: #fbfbfc;
            padding: .65rem .75rem;
        }

        .work-info-row strong {
            display: block;
            margin-top: .25rem;
            overflow-wrap: anywhere;
        }

        .work-info-row-full {
            grid-column: 1 / -1;
        }

        .work-file-tabs {
            gap: .5rem;
            border-bottom: 1px solid #e9ecef;
            padding-bottom: .75rem;
        }

        .work-file-tabs .nav-link {
            border: 1px solid #d9dee3;
            border-radius: 8px;
            color: #495057;
            font-weight: 700;
            max-width: 100%;
            overflow-wrap: anywhere;
        }

        .work-file-tabs .nav-link.active {
            background: #1f6f54;
            border-color: #1f6f54;
            color: #fff;
        }

        .work-thumb-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(136px, 1fr));
            gap: .75rem;
        }

        .work-thumb {
            border: 1px solid #e1e5ea;
            border-radius: 8px;
            background: #fff;
            padding: .45rem;
            position: relative;
            text-align: left;
            transition: border-color .15s ease, box-shadow .15s ease, transform .15s ease;
        }

        .work-thumb-preview {
            display: block;
            width: 100%;
            border: 0;
            background: transparent;
            padding: 0;
            text-align: left;
        }

        .work-thumb:hover {
            border-color: #1f6f54;
            box-shadow: 0 8px 20px rgba(15, 23, 42, .12);
            transform: translateY(-1px);
        }

        .work-thumb img {
            display: block;
            width: 100%;
            aspect-ratio: 4 / 3;
            object-fit: cover;
            border-radius: 6px;
            background: #f1f3f5;
        }

        .work-thumb-placeholder {
            display: grid;
            place-items: center;
            width: 100%;
            aspect-ratio: 4 / 3;
            border-radius: 6px;
            background: #e9ecef;
            color: #6c757d;
            font-size: .78rem;
            font-weight: 800;
            text-align: center;
        }

        .work-thumb-name {
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

        .work-thumb-download {
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

        .work-thumb-download:hover {
            background: #1f6f54;
            color: #fff;
            transform: translateY(-1px);
        }

        .work-file-list {
            display: grid;
            gap: .5rem;
        }

        .work-file-row {
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

        .work-file-main {
            display: flex;
            align-items: center;
            gap: .65rem;
            min-width: 0;
        }

        .work-file-main strong {
            display: block;
            color: #212529;
            line-height: 1.25;
            overflow-wrap: anywhere;
        }

        .work-file-icon {
            color: #1f6f54;
            flex: 0 0 auto;
            font-size: 1.75rem;
        }

        .work-file-actions {
            display: flex;
            gap: .35rem;
            flex: 0 0 auto;
        }

        .work-empty-state {
            display: grid;
            place-items: center;
            min-height: 120px;
            border: 1px dashed #cfd6dd;
            border-radius: 8px;
            background: #fff;
            color: #6c757d;
            text-align: center;
            padding: 1rem;
        }

        .work-empty-state i {
            font-size: 2rem;
        }

        .work-preview-stage {
            display: grid;
            place-items: center;
            min-height: 70vh;
            background: #111827;
        }

        .work-preview-stage img {
            display: block;
            max-width: 100%;
            max-height: 70vh;
            object-fit: contain;
        }

        .work-preview-placeholder {
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

        .work-preview-caption {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            padding: .85rem 1rem;
            background: #fff;
        }

        .work-preview-caption strong {
            overflow-wrap: anywhere;
        }

        @media (max-width: 992px) {
            .work-summary-grid,
            .work-info-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 576px) {
            .work-summary-grid,
            .work-info-grid {
                grid-template-columns: 1fr;
            }

            .work-file-row,
            .work-preview-caption {
                align-items: stretch;
                flex-direction: column;
            }

            .work-file-actions {
                justify-content: flex-end;
            }
        }
    </style>
@endpushOnce

@pushOnce('script')
    <script>
        function restoreWorkFileTabs(root) {
            if (typeof bootstrap === 'undefined') {
                return;
            }

            (root || document).querySelectorAll('[data-work-files]').forEach(function (manager) {
                const scope = manager.getAttribute('data-work-files');
                const storedTab = sessionStorage.getItem('work-files-active-tab:' + scope);
                const button = storedTab ? document.getElementById(storedTab) : null;

                if (button && manager.contains(button)) {
                    bootstrap.Tab.getOrCreateInstance(button).show();
                    return;
                }

                const firstButton = manager.querySelector('[data-bs-toggle="pill"]');
                if (firstButton) {
                    bootstrap.Tab.getOrCreateInstance(firstButton).show();
                }
            });
        }

        document.addEventListener('shown.bs.tab', function (event) {
            const manager = event.target.closest('[data-work-files]');
            if (!manager || !event.target.id) {
                return;
            }

            sessionStorage.setItem('work-files-active-tab:' + manager.getAttribute('data-work-files'), event.target.id);
        });

        document.addEventListener('click', function (event) {
            const trigger = event.target.closest('[data-work-preview-open]');
            if (!trigger || typeof bootstrap === 'undefined') {
                return;
            }

            const modalSelector = trigger.getAttribute('data-preview-modal');
            const carouselSelector = trigger.getAttribute('data-preview-carousel');
            const index = parseInt(trigger.getAttribute('data-preview-index') || '0', 10);
            const modal = document.querySelector(modalSelector);
            const carousel = document.querySelector(carouselSelector);

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

        document.addEventListener('livewire:load', function () {
            restoreWorkFileTabs(document);

            if (window.Livewire && Livewire.hook) {
                Livewire.hook('message.processed', function () {
                    restoreWorkFileTabs(document);
                });
            }
        });

        document.addEventListener('hidden.bs.modal', function () {
            if (document.querySelector('.modal.show')) {
                document.body.classList.add('modal-open');
            }
        });

        document.addEventListener('show.bs.modal', function (event) {
            const target = event.target;
            if (target.id !== 'modal_partial_info' && !target.classList.contains('work-preview-modal')) {
                return;
            }
            const openCount = document.querySelectorAll('.modal.show').length;
            if (openCount === 0) return;

            const newZ = 1055 + openCount * 10;
            target.style.zIndex = newZ;

            setTimeout(function () {
                const backdrops = document.querySelectorAll('.modal-backdrop');
                const last = backdrops[backdrops.length - 1];
                if (last) last.style.zIndex = newZ - 5;
            }, 0);
        });
    </script>
@endpushOnce
