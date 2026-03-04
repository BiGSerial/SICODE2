<div>
    <x-show-loading />

    <div class="modal fade d5-view-modal" id="finishFiveModal" tabindex="-1" aria-hidden="true" wire:ignore.self>
        <div class="modal-dialog modal-dialog-centered modal-xl">
            <div class="modal-content d5-modal-card">
                <div class="modal-header d5-modal-header">
                    <div>
                        <h5 class="modal-title mb-1">
                            <i class="ri-file-list-3-line me-1"></i> Detalhes da D5
                        </h5>
                        <div class="small opacity-75">
                            D5 {{ $five?->note_d5 ?? '---' }} | Nota {{ $five?->note?->note ?? '---' }}
                        </div>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Fechar"></button>
                </div>

                <div class="modal-body p-0">
                    @if (!empty($five))
                        @php
                            $activity = $trackingMeta['activity'] ?? [];
                            $assignee = $trackingMeta['assignee'] ?? [];
                            $allFiles = $five?->evidenceFiles ?? collect();
                        @endphp

                        <div class="d5-overview row g-0">
                            <div class="col-12 col-lg-8 border-end">
                                <div class="p-3 p-lg-4">
                                    <div class="d5-badges mb-3">
                                        <span class="badge {{ $activity['color'] ?? 'text-bg-secondary' }}">
                                            {{ $activity['label'] ?? 'Sem status' }}
                                        </span>
                                        @if ($five->isPassive)
                                            <span class="badge text-bg-info">Passiva</span>
                                        @endif
                                        @if ($assignee['has_assignee'] ?? false)
                                            <span class="badge text-bg-light border">
                                                Responsável: {{ $assignee['name'] }}
                                            </span>
                                        @else
                                            <span class="badge text-bg-danger">Sem responsável atribuído</span>
                                        @endif
                                    </div>

                                    <div class="d5-grid mb-3">
                                        <div class="d5-cell">
                                            <div class="d5-k">Empreiteira</div>
                                            <div class="d5-v">{{ $five?->company?->name ?? '---' }}</div>
                                        </div>
                                        <div class="d5-cell">
                                            <div class="d5-k">Rubrica</div>
                                            <div class="d5-v">{{ $five?->note?->rubrica ?? '---' }}</div>
                                        </div>
                                        <div class="d5-cell">
                                            <div class="d5-k">Motivo</div>
                                            <div class="d5-v">{{ $five?->reason ?? '---' }}</div>
                                        </div>
                                        <div class="d5-cell">
                                            <div class="d5-k">Codificação</div>
                                            <div class="d5-v">{{ $five?->codify ?? '---' }}</div>
                                        </div>
                                        <div class="d5-cell">
                                            <div class="d5-k">Local de instalação</div>
                                            <div class="d5-v">{{ $five?->loc_install ?? '---' }}</div>
                                        </div>
                                        <div class="d5-cell">
                                            <div class="d5-k">PEP</div>
                                            <div class="d5-v">{{ $five?->pep ?? '---' }}</div>
                                        </div>
                                    </div>

                                    <div class="d5-desc mb-3">
                                        {{ $five?->description ?? 'Sem descrição.' }}
                                    </div>

                                    <h6 class="d5-section-title"><i class="ri-attachment-2 me-1"></i> Arquivos da D5</h6>
                                    <x-files.attachments :files="$allFiles" :downloadAction="'dowloadFile'" :showHeader="false"
                                        :card="false" class="d5-attachments-lite" />
                                </div>
                            </div>

                            <div class="col-12 col-lg-4">
                                <div class="p-3 p-lg-4">
                                    <h6 class="d5-section-title">
                                        <i class="ri-git-commit-line me-1"></i> Timeline de Eventos
                                    </h6>

                                    @if (empty($eventTimeline))
                                        <div class="d5-empty">Sem eventos de timeline para esta D5.</div>
                                    @else
                                        <div class="d5-events-scroll">
                                            @foreach ($eventTimeline as $event)
                                                <div class="d5-event-item">
                                                    <div class="d5-event-icon">
                                                        <i class="{{ $event['icon'] }}"></i>
                                                    </div>
                                                    <div class="flex-grow-1">
                                                        <div class="d5-event-title">{{ $event['event'] }}</div>
                                                        <div class="d5-event-meta">
                                                            @php
                                                                $eventWhen = $event['when'] ?? null;
                                                                if ($eventWhen instanceof \Carbon\CarbonInterface) {
                                                                    $eventWhenLabel = $eventWhen->format('d/m/Y H:i');
                                                                } elseif (is_string($eventWhen) && trim($eventWhen) !== '') {
                                                                    try {
                                                                        $eventWhenLabel = \Illuminate\Support\Carbon::parse($eventWhen)->format('d/m/Y H:i');
                                                                    } catch (\Throwable $e) {
                                                                        $eventWhenLabel = '---';
                                                                    }
                                                                } else {
                                                                    $eventWhenLabel = '---';
                                                                }
                                                            @endphp
                                                            {{ $eventWhenLabel }}
                                                            <span class="text-muted">| {{ $event['stage'] }}</span>
                                                        </div>
                                                        <div class="d5-event-meta">
                                                            Responsável: {{ $event['owner'] ?? '---' }}
                                                            @if ($event['actor'])
                                                                <span class="text-muted">| Ação por {{ $event['actor'] }}</span>
                                                            @endif
                                                            @if ($event['inferred'])
                                                                <span class="badge text-bg-warning ms-2">Inferido</span>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif

                                    <h6 class="d5-section-title mt-4"><i class="ri-chat-1-line me-1"></i> Última observação</h6>
                                    <div class="d5-note">
                                        {{ $five?->comments?->last()?->message ?? 'Sem observações registradas.' }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="p-4">
                            <div class="d5-empty text-center">Nenhuma informação carregada.</div>
                        </div>
                    @endif
                </div>

                <div class="modal-footer">
                    <button class="btn btn-outline-secondary" data-bs-dismiss="modal">Fechar</button>
                </div>
            </div>
        </div>
    </div>

    <style>
        .d5-view-modal .modal-dialog {
            max-width: min(1600px, 96vw);
            margin: 1rem auto;
        }

        .d5-view-modal .d5-modal-card {
            border: 0;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 22px 50px rgba(15, 23, 42, .26);
        }

        .d5-view-modal .d5-modal-header {
            background: linear-gradient(120deg, #0f172a, #0f766e 75%);
            color: #f8fafc;
            border-bottom: 0;
        }

        .d5-view-modal .d5-overview {
            background: linear-gradient(180deg, #f8fafc 0%, #ffffff 100%);
        }

        .d5-view-modal .d5-badges {
            display: flex;
            gap: .4rem;
            flex-wrap: wrap;
        }

        .d5-view-modal .d5-badges .badge {
            max-width: 100%;
            white-space: normal;
            word-break: break-word;
            overflow-wrap: anywhere;
        }

        .d5-view-modal .d5-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: .75rem;
        }

        .d5-view-modal .d5-cell {
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            padding: .6rem .75rem;
            background: #ffffff;
        }

        .d5-view-modal .d5-k {
            font-size: .74rem;
            text-transform: uppercase;
            letter-spacing: .06em;
            color: #6b7280;
            margin-bottom: .15rem;
        }

        .d5-view-modal .d5-v {
            font-weight: 600;
            color: #1f2937;
        }

        .d5-view-modal .d5-desc,
        .d5-view-modal .d5-note {
            border: 1px dashed #d1d5db;
            border-radius: 10px;
            padding: .75rem;
            background: #f9fafb;
            color: #374151;
            white-space: pre-wrap;
        }

        .d5-view-modal .d5-section-title {
            font-size: .9rem;
            text-transform: uppercase;
            letter-spacing: .05em;
            color: #475569;
            margin-bottom: .7rem;
            font-weight: 700;
        }

        .d5-view-modal .d5-events-scroll {
            max-height: 380px;
            overflow-y: auto;
            padding-right: .2rem;
            scrollbar-width: thin;
            scrollbar-color: #cbd5e1 transparent;
        }

        .d5-view-modal .d5-events-scroll::-webkit-scrollbar {
            width: 6px;
        }

        .d5-view-modal .d5-events-scroll::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 10px;
        }

        .d5-view-modal .d5-event-item {
            display: flex;
            gap: .65rem;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            background: #fff;
            padding: .55rem .6rem;
            margin-bottom: .55rem;
        }

        .d5-view-modal .d5-event-icon {
            width: 26px;
            height: 26px;
            border-radius: 8px;
            background: #eff6ff;
            color: #1d4ed8;
            display: grid;
            place-items: center;
            flex-shrink: 0;
        }

        .d5-view-modal .d5-event-title {
            font-size: .86rem;
            font-weight: 700;
            color: #1f2937;
            line-height: 1.2;
            margin-bottom: .1rem;
        }

        .d5-view-modal .d5-event-meta {
            font-size: .76rem;
            color: #64748b;
            line-height: 1.25;
        }

        .d5-view-modal .d5-empty {
            border: 1px dashed #d1d5db;
            border-radius: 10px;
            background: #f8fafc;
            color: #6b7280;
            padding: .85rem;
        }

        /* Hardening para evitar quebra de layout dentro do componente de anexos no modal */
        .d5-view-modal .d5-attachments-lite .attachments-comp-grid {
            gap: .75rem;
        }

        .d5-view-modal .d5-attachments-lite .attachments-comp-image-item {
            overflow: hidden;
        }

        .d5-view-modal .d5-attachments-lite .attachments-comp-image {
            width: 100%;
            height: 160px;
            object-fit: cover;
            background: #f1f5f9;
            color: transparent;
            font-size: 0;
            text-indent: -9999px;
            overflow: hidden;
            display: block;
        }

        .d5-view-modal .d5-attachments-lite .card-body {
            min-width: 0;
        }

        .d5-view-modal .d5-attachments-lite .card-body small {
            display: block;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 100%;
        }

        .d5-view-modal .d5-attachments-lite .attachments-comp-file-item {
            min-width: 0;
        }

        .d5-view-modal .d5-attachments-lite .attachments-comp-file-item .text-truncate {
            max-width: 100%;
        }

        @media (max-width: 992px) {
            .d5-view-modal .d5-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</div>
