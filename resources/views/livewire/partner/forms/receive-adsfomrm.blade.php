<div wire:ignore.self class="modern-ads-delivery">
    <x-show-loading />

    <div class="modern-ads-shell">
        <div class="card shadow-sm rounded-4 glass-card mx-auto" style="max-width: 56rem;">
            <div class="card-body p-4 p-md-5">
                <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
                    <div>
                        <span class="badge badge-soft">Entrega de ADS</span>
                        <h4 class="fw-bold mt-2 mb-1">Localizar obra para entrega</h4>
                        <p class="text-muted mb-0">Busque por nota, OV, ordem ou diagrama.</p>
                    </div>
                    <div class="search-wrap w-100 w-md-auto">
                        <div class="input-group input-group-lg">
                            <span class="input-group-text bg-transparent search-addon">
                                <i class="ri-search-line"></i>
                            </span>
                            <input class="form-control bg-transparent search-input" type="search" wire:model.defer="search"
                                placeholder="Digite número da nota, OV, ordem ou diagrama">
                            <button class="btn btn-primary" wire:click.prevent="search">Buscar</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @if (!$note && $notes)
            <div class="card border-0 shadow-sm rounded-4 mx-auto mt-3 warning-card" style="max-width: 56rem;">
                <div class="card-body d-flex align-items-start gap-3 p-3 p-md-4">
                    <i class="ri-error-warning-line fs-3 text-danger"></i>
                    <div>
                        <h6 class="mb-1">Atenção</h6>
                        <p class="mb-0">Informe apenas obras com entrega final de ADS.</p>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm rounded-4 mx-auto mt-3" style="max-width: 56rem;">
                <div class="card-header bg-white border-0 p-4 pb-2">
                    <h5 class="fw-bold mb-0">Obras encontradas</h5>
                </div>
                <div class="card-body pt-2">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle modern-table mb-0">
                            <thead>
                                <tr>
                                    <th>Nota</th>
                                    <th>Ordens</th>
                                    <th>Dt solicitação ADS</th>
                                    <th>Bloqueado</th>
                                    <th>Motivo</th>
                                    <th class="text-center">Ação</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($notes as $tNote)
                                    @php
                                        $block = false;
                                        $reason = '';

                                        if (!$tNote->WorkForm) {
                                            $block = true;
                                            $reason = 'SEM INFORME DE OBRA';
                                        } elseif ($tNote->WorkForm->rejected) {
                                            $block = true;
                                            $latestReturn = $tNote->WorkForm->LatestReturnwork;
                                            $rejectCategory = trim((string) ($latestReturn?->category ?? ''));
                                            $rejectObs = trim((string) ($latestReturn?->text_obs ?? ''));
                                            $rejectUser = trim((string) ($latestReturn?->User?->name ?? ''));
                                            $rejectUserEmail = trim((string) ($latestReturn?->User?->email ?? ''));
                                            $rejectAt = $latestReturn?->created_at?->format('d/m/Y H:i');
                                            $reason = 'INFORME REJEITADO';
                                        } else {
                                            $adsForm = $tNote->WorkForm->Adsform;
                                            $hasOldAds = $tNote->OldAds->isNotEmpty();
                                            $hasAdsFile = $adsForm ? $adsForm->Files->isNotEmpty() : false;
                                            $hasTacitDelivered = (bool) ($adsForm?->tacit_delivered_at);

                                            if ($hasOldAds || $hasAdsFile || $hasTacitDelivered) {
                                                $block = true;
                                                $reason = 'DOCUMENTAÇÃO JÁ ENTREGUE';
                                            }
                                        }
                                    @endphp
                                    <tr wire:key="{{ $tNote->id }}">
                                        <td class="fw-bold">{{ $tNote->note }}</td>
                                        <td>
                                            @if ($tNote->orders->count())
                                                @foreach ($tNote->orders->filter(function ($order) {
        return !(strpos($order->statusSist, 'ENT') === 0 || strpos($order->statusSist, 'ENC') === 0);
    }) as $order)
                                                    <span class="badge bg-light text-dark border mb-1">{{ $order->ordem }}</span>
                                                @endforeach
                                            @endif
                                        </td>
                                        <td>
                                            {{ $tNote->TempAdsInfos->isNotEmpty() ? $tNote->TempAdsInfos->last()->sended_at->format('d/m/Y') : '---' }}
                                        </td>
                                        <td>
                                            @if ($block)
                                                <span class="badge bg-danger-subtle text-danger-emphasis">SIM</span>
                                            @else
                                                <span class="badge bg-success-subtle text-success-emphasis">NÃO</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if ($tNote->WorkForm?->rejected)
                                                <div class="card border-danger-subtle shadow-sm mb-0">
                                                    <div class="card-header bg-danger-subtle py-2 px-3 border-0">
                                                        <div class="fw-bold text-danger mb-1">INFORME REJEITADO</div>
                                                        <div class="small text-muted">
                                                            Motivo: {{ $rejectCategory !== '' ? $rejectCategory : 'Não informado' }}
                                                        </div>
                                                    </div>
                                                    <div class="card-body py-2 px-3">
                                                        <div class="small">
                                                            {{ $rejectObs !== '' ? $rejectObs : 'Sem observação registrada.' }}
                                                        </div>
                                                    </div>
                                                    <div class="card-footer bg-white py-2 px-3 border-0 border-top">
                                                        <div class="d-flex align-items-center gap-2 text-muted small">
                                                            @if ($rejectUserEmail !== '')
                                                                @php
                                                                    $teamsWebUrl = 'https://teams.microsoft.com/l/chat/0/0?users=' . urlencode($rejectUserEmail);
                                                                    $teamsDesktopUrl = 'msteams://teams.microsoft.com/l/chat/0/0?users=' . urlencode($rejectUserEmail);
                                                                @endphp
                                                                <a href="{{ $teamsWebUrl }}"
                                                                    target="_blank" rel="noopener noreferrer"
                                                                    class="text-decoration-none text-reset"
                                                                    style="color: inherit;"
                                                                    title="Conversar no Teams com {{ $rejectUserEmail }}"
                                                                    onclick="openTeamsDesktopWithFallback(event, '{{ $teamsDesktopUrl }}', '{{ $teamsWebUrl }}')">
                                                                    <i class="ri-microsoft-fill"></i>
                                                                </a>
                                                                <a href="{{ $teamsWebUrl }}"
                                                                    target="_blank" rel="noopener noreferrer"
                                                                    class="text-decoration-none text-reset"
                                                                    style="color: inherit;"
                                                                    title="Conversar no Teams com {{ $rejectUserEmail }}"
                                                                    onclick="openTeamsDesktopWithFallback(event, '{{ $teamsDesktopUrl }}', '{{ $teamsWebUrl }}')">
                                                                    {{ $rejectUser !== '' ? $rejectUser : 'Usuário não identificado' }}
                                                                </a>
                                                            @else
                                                                <i class="ri-microsoft-fill"></i>
                                                                <span>{{ $rejectUser !== '' ? $rejectUser : 'Usuário não identificado' }}</span>
                                                            @endif
                                                            <span>•</span>
                                                            <span>{{ $rejectAt ?? 'Data não registrada' }}</span>
                                                        </div>
                                                    </div>
                                                </div>
                                            @else
                                                <span class="fw-semibold">{{ $reason }}</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            @if (!$block)
                                                <button type="button" class="btn btn-sm btn-outline-primary"
                                                    wire:click.prevent="getNote({{ $tNote->id }})">
                                                    Selecionar
                                                </button>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endif

        @if ($note)
            <div class="card shadow-sm rounded-4 mx-auto mt-3 modern-hero" style="max-width: 56rem;">
                <div class="card-body p-4 d-flex flex-column flex-md-row justify-content-between gap-3">
                    <div>
                        <span class="badge badge-soft">Envio de documento</span>
                        <h4 class="fw-bold mt-2 mb-1">Entrega de ADS</h4>
                        <p class="text-muted mb-0">Revise os dados antes de confirmar o envio.</p>
                    </div>
                    <div class="text-md-end">
                        <div class="small text-muted">Nota selecionada</div>
                        <div class="fs-5 fw-bold">{{ $note->note }}</div>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm rounded-4 mx-auto mt-3" style="max-width: 56rem;">
                <div class="card-header bg-white border-0 p-4 pb-2">
                    <h5 class="mb-0 fw-bold">Dados da obra</h5>
                </div>
                <div class="card-body pt-2">
                    <div class="table-responsive">
                        <table class="table table-sm table-striped-columns align-middle mb-0">
                            <tbody>
                                <tr>
                                    <td class="text-end fw-bold" style="width: 180px;">Nota/Ov</td>
                                    <td>{{ $note->note }}</td>
                                    <td class="text-end fw-bold" style="width: 180px;">Status Atual</td>
                                    <td>{{ $note->nstats }}</td>
                                </tr>
                                <tr>
                                    <td class="text-end fw-bold">Ordens</td>
                                    <td>
                                        @if ($note->orders->count())
                                            @foreach ($note->orders->filter(function ($order) {
        return !(strpos($order->statusSist, 'ENT') === 0 || strpos($order->statusSist, 'ENC') === 0);
    }) as $order)
                                                <span class="badge bg-light text-dark border mb-1">{{ $order->ordem }}</span>
                                            @endforeach
                                        @endif
                                    </td>
                                    <td class="text-end fw-bold">Rubrica</td>
                                    <td>{{ $note->rubrica }}</td>
                                </tr>
                                <tr>
                                    <td class="text-end fw-bold">Município</td>
                                    <td>{{ $note->lexp }}</td>
                                    <td class="text-end fw-bold">Centro Trabalho</td>
                                    <td>{{ $note->centerjob }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm rounded-4 mx-auto mt-3" style="max-width: 56rem;" x-data="{ isUploading: false, progress: 0 }"
                x-on:livewire-upload-start="isUploading = true" x-on:livewire-upload-finish="isUploading = false"
                x-on:livewire-upload-error="isUploading = false" x-on:livewire-upload-progress="progress = $event.detail.progress">
                <div class="card-header bg-white border-0 p-4 pb-2">
                    <h5 class="mb-0 fw-bold">Arquivo da ADS</h5>
                </div>
                <div class="card-body pt-2">
                    <div class="input-group mb-2">
                        <input type="file" class="form-control" wire:model="file" accept=".xlsx,.xls">
                        <button class="btn btn-primary" wire:click.prevent="processFile" @disabled(!$file)>
                            <span wire:loading.remove wire:target="processFile">Processar</span>
                            <span wire:loading wire:target="processFile" class="spinner-border spinner-border-sm" role="status"
                                aria-hidden="true"></span>
                        </button>
                    </div>

                    @error('file')
                        <div class="text-danger mt-1">{{ $message }}</div>
                    @enderror

                    <div class="progress mb-1" x-show="isUploading">
                        <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar"
                            :style="`width: ${progress}%`">
                            <span x-text="progress + '%'" class="small"></span>
                        </div>
                    </div>
                </div>
            </div>

            @if ($process && $myAds && $myAds->exists())
                <div wire:key="ADS_FORM" class="card shadow-sm rounded-4 mx-auto mt-3" style="max-width: 56rem;">
                    <div class="card-header bg-white border-0 p-4 pb-2">
                        <h5 class="mb-0 fw-bold">Dados extraídos da ADS</h5>
                    </div>
                    <div class="card-body pt-2">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="info-item">
                                    <span class="text-muted small d-block">Nota/Ov</span>
                                    <strong>{{ $myAds->getNote() }}</strong>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="info-item">
                                    <span class="text-muted small d-block">Empreiteira</span>
                                    <strong>{{ $myAds->getCompany() }}</strong>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="info-item">
                                    <span class="text-muted small d-block">Contrato</span>
                                    <strong>{{ $myAds->getContract() }}</strong>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="info-item">
                                    <span class="text-muted small d-block">Centro</span>
                                    <strong>{{ $myAds->getCenter() }}</strong>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="info-item">
                                    <span class="text-muted small d-block">Depósito</span>
                                    <strong>{{ $myAds->getDeposit() }}</strong>
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="info-item">
                                    <span class="text-muted small d-block">Tipo de envio</span>
                                    <strong>{{ $myAds->getPartial() ? 'PARCIAL' : 'FINAL' }}</strong>
                                </div>
                            </div>

                            <div class="col-md-12">
                                <div class="form-floating">
                                    <textarea class="form-control" style="height: 100px" placeholder="Observação"
                                        wire:model.defer="observation"></textarea>
                                    <label>Observação para engenharia</label>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-floating">
                                    <input type="text" class="form-control" wire:model.defer="responsible"
                                        placeholder="Nome completo do responsável">
                                    <label>Responsável <span class="text-danger">*</span></label>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-floating">
                                    <input type="decimal" class="form-control money" wire:model.defer="amount"
                                        placeholder="0,00">
                                    <label>Valor da ADS (R$) <span class="text-danger">*</span></label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card-body border-top">
                        @livewire('files.manager.create-ads-files', ['note' => $note, 'service' => 'CFINAL'], key('ADS_final_files'))
                    </div>

                    <div class="card-footer bg-white border-0 p-4 pt-0">
                        @if ($hasAsbuiltFile)
                            <div class="card border-warning border-2 shadow-sm mb-3">
                                <div class="card-header bg-warning-subtle d-flex align-items-center gap-2">
                                    <i class="ri-alert-line fs-5 text-warning-emphasis"></i>
                                    <strong>ASBUILT complementar anexado</strong>
                                </div>
                                <div class="card-body">
                                    <p class="mb-0">
                                        O ASBUILT não substitui o arquivo anterior nem respalda alteração da informação
                                        validada e confirmada na declaração de entrega de obras.
                                    </p>
                                </div>
                            </div>
                        @endif

                        <div class="d-grid d-md-flex justify-content-md-end gap-2">
                            <button type="button" class="btn btn-primary px-4" wire:click="toSave">
                                Confirmar envio
                            </button>
                        </div>
                    </div>
                </div>
            @endif
        @endif
    </div>
</div>

@push('script')
    <script>
        window.openTeamsDesktopWithFallback = window.openTeamsDesktopWithFallback || function(event, desktopUrl, webUrl) {
            if (event) {
                event.preventDefault();
            }

            // Tentativa 1: deep-link para app desktop.
            window.open(desktopUrl, '_blank', 'noopener,noreferrer');

            // Fallback: abre web em nova aba/janela.
            setTimeout(function() {
                window.open(webUrl, '_blank', 'noopener,noreferrer');
            }, 600);
        };
    </script>
@endpush

@push('css')
    <style>
        :root {
            --ads-bg: #f3f7fb;
            --ads-card: #ffffff;
            --ads-ink: #0f172a;
            --ads-muted: #64748b;
            --ads-accent: #0b7285;
            --ads-accent-2: #0ea5a4;
            --ads-border: rgba(15, 23, 42, 0.08);
        }

        .modern-ads-delivery {
            background: radial-gradient(900px 350px at 10% 0%, #eaf3ff 0%, transparent 65%),
                radial-gradient(900px 450px at 90% 10%, #e7fff6 0%, transparent 60%),
                var(--ads-bg);
            border-radius: 16px;
            padding: 18px;
        }

        .modern-ads-shell {
            max-width: 58rem;
            margin: 0 auto;
        }

        .glass-card {
            background: rgba(255, 255, 255, 0.85);
            border: 1px solid var(--ads-border);
            backdrop-filter: blur(6px);
        }

        .badge-soft {
            background: rgba(14, 165, 164, 0.15);
            color: #0f766e;
            border: 1px solid rgba(14, 165, 164, 0.3);
            padding: 6px 10px;
            border-radius: 999px;
            font-weight: 600;
        }

        .modern-hero {
            border: 1px solid var(--ads-border);
            background: linear-gradient(135deg, rgba(11, 114, 133, 0.12), rgba(14, 165, 164, 0.06));
        }

        .modern-table tbody tr:hover {
            background-color: rgba(14, 165, 164, 0.08);
        }

        .warning-card {
            border-left: 4px solid #dc3545;
        }

        .info-item {
            border: 1px solid var(--ads-border);
            border-radius: 12px;
            padding: 12px;
            background: #fff;
            height: 100%;
        }

        .search-wrap {
            min-width: 320px;
        }

        .search-wrap .search-input {
            border: 1px solid #94a3b8 !important;
            border-left: 0 !important;
            background: #fff !important;
        }

        .search-wrap .search-addon {
            border: 1px solid #94a3b8 !important;
            border-right: 0 !important;
            background: #fff !important;
            color: #64748b;
        }

        .search-wrap .search-input:focus {
            border-color: #0ea5a4 !important;
            box-shadow: 0 0 0 0.2rem rgba(14, 165, 164, 0.16);
        }

        @media (max-width: 768px) {
            .search-wrap {
                min-width: auto;
            }
        }
    </style>
@endpush
