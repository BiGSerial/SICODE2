@php
    use App\Services\Closure\{ClosureEventLabels, ClosureIssueWorkflowService};

    $statusMap = [
        'OPEN'       => ['Aberta', 'muted'],
        'REQUESTED'  => ['Aguardando seu retorno', 'amber'],
        'RETURNED'   => ['Devolvida para correção', 'red'],
        'RESPONDED'  => ['Respondida · aguardando validação', 'blue'],
        'VALIDATING' => ['Em validação pelo Encerramento', 'blue'],
        'RESOLVED'   => ['Resolvida', 'green'],
    ];
    [$statusLabel, $statusTone] = $statusMap[$issue->status] ?? [$issue->status, 'muted'];

    // Linha do tempo com a data de cada etapa e o tempo desde a etapa anterior.
    $timeline = [];
    $previous = null;

    foreach ($issue->Events as $event) {
        $lines = [];

        foreach ($event->meta['answers'] ?? [] as $answer) {
            $lines[] = $answer['index'] . '. ' . $answer['text'] . ' → ' . (ClosureIssueWorkflowService::ANSWER_LABELS[$answer['status']] ?? $answer['status']) . ($answer['note'] ? ' — ' . $answer['note'] : '');
        }

        $days = $previous ? (int) $previous->created_at->copy()->startOfDay()->diffInDays($event->created_at->copy()->startOfDay()) : null;

        $timeline[] = [
            'label'    => ClosureEventLabels::issueLabel($event->type),
            'at'       => $event->created_at,
            'by'       => $event->Actor?->name,
            'duration' => $days === null ? null : ($days === 0 ? 'no mesmo dia' : 'após ' . $days . ' ' . ($days === 1 ? 'dia' : 'dias')),
            'tone'     => ClosureEventLabels::issueTone($event->type),
            'note'     => trim(($event->meta['message'] ?? '') . ($lines ? "\n" . implode("\n", $lines) : '')) ?: null,
        ];

        $previous = $event;
    }

    $requirements = array_values($issue->requirements ?? []);
    $waitingSince = $issue->status === 'RETURNED' ? ($lastReturn?->created_at ?? $issue->requested_at) : $issue->requested_at;
@endphp
@php
    $stepNow    = ['OPEN' => 1, 'REQUESTED' => 1, 'RETURNED' => 1, 'RESPONDED' => 2, 'VALIDATING' => 2, 'RESOLVED' => 3][$issue->status] ?? 1;
    $steps      = [['ri-inbox-archive-line', 'Pedido recebido'], ['ri-reply-line', $issue->status === 'RETURNED' ? 'Corrigir e reenviar' : 'Seu retorno'], ['ri-shield-check-line', 'Validação do Encerramento'], ['ri-checkbox-circle-line', 'Concluída']];
    $answeredN  = collect($answers)->filter(fn ($a) => ($a['status'] ?? '') !== '')->count();
    $totalReq   = count($requirements);
@endphp
<div class="closure-dash partner-issue">
    <x-show-loading />

    <section class="pi-hero mb-3">
        <div class="pi-hero-main">
            <div class="min-w-0">
                <div class="pi-eyebrow"><i class="ri-building-4-line"></i> Obra {{ $issue->Order?->Note?->note }} · Ordem {{ $issue->Order?->ordem }}</div>
                <h2 class="pi-title">{{ $issue->Subcategory?->name ?: 'Pendência' }}</h2>
                <div class="pi-sub">{{ $issue->Category?->name }} · Pendência #{{ $issue->id }} · Ciclo {{ $issue->cycle }}@if ($issue->blocking) · <span class="pi-block"><i class="ri-lock-line"></i> trava a Ordem</span>@endif</div>
            </div>
            <div class="pi-actions">
                <span class="pi-status tone-{{ $statusTone }}">{{ $statusLabel }}</span>
                <button type="button" class="btn btn-light btn-sm" wire:click="exportIssue" wire:loading.attr="disabled" wire:target="exportIssue"><i class="ri-file-excel-2-line"></i> Exportar</button>
                <a href="{{ route('partner.closure.issues') }}" class="btn btn-outline-light btn-sm"><i class="ri-arrow-left-line"></i> Voltar</a>
            </div>
        </div>
        <ol class="pi-steps" aria-label="Andamento da pendência">
            @foreach ($steps as $i => [$icon, $label])
                <li class="{{ $i < $stepNow ? 'is-done' : ($i === $stepNow && $issue->status !== 'RESOLVED' ? 'is-current' : ($issue->status === 'RESOLVED' ? 'is-done' : '')) }}" @if ($i === $stepNow && $issue->status !== 'RESOLVED') aria-current="step" @endif>
                    <span class="pi-step-dot"><i class="{{ $i < $stepNow || $issue->status === 'RESOLVED' ? 'ri-check-line' : $icon }}"></i></span>
                    <span class="pi-step-label">{{ $label }}</span>
                </li>
            @endforeach
        </ol>
    </section>

    @if (!$observing)
        @if ($canRespond)
            <div class="pi-next is-action" role="status">
                <i class="ri-hand-heart-line"></i>
                <div class="flex-grow-1">
                    <strong>{{ $issue->status === 'RETURNED' ? 'O Encerramento devolveu para correção — é a sua vez.' : 'É a sua vez: responda esta pendência.' }}</strong>
                    <div class="small">{{ $totalReq ? "Responda os {$totalReq} item(ns) abaixo" : 'Descreva o que foi feito' }}, anexe as evidências e envie.@if ($waitingSince) Aguardando você <x-closure.age-chip :since="$waitingSince" prefix="há" />@endif</div>
                </div>
                <a href="#retorno" class="btn btn-light btn-sm fw-semibold">Ir para o retorno <i class="ri-arrow-down-line"></i></a>
            </div>
        @elseif (in_array($issue->status, ['RESPONDED', 'VALIDATING'], true))
            <div class="pi-next is-wait" role="status"><i class="ri-time-line"></i><div><strong>Retorno enviado. Agora é com o Encerramento.</strong><div class="small">Você será avisada quando for validado ou se precisar corrigir algo.</div></div></div>
        @elseif ($issue->status === 'RESOLVED')
            <div class="pi-next is-done" role="status"><i class="ri-checkbox-circle-line"></i><div><strong>Pendência resolvida.</strong><div class="small">Nada mais a fazer aqui. O histórico fica disponível abaixo.</div></div></div>
        @endif
    @endif

    @if (session()->has('success'))
        <div class="alert alert-success d-flex align-items-center gap-2" role="status"><i class="ri-checkbox-circle-line"></i> {{ session('success') }}</div>
    @endif

    @if ($observing)
        <div class="inbox-note mb-3" role="status">
            <i class="ri-eye-line" aria-hidden="true"></i>
            <span><strong>Modo observador:</strong> você vê esta pendência como a parceira ({{ $issue->DestinationCompany?->name }}) e não responde em nome dela.</span>
        </div>
    @endif

    <div class="row g-3">
        <div class="col-12 col-xl-7 d-flex flex-column gap-3">
            {{-- O que o Encerramento pediu --}}
            <div class="table-card p-3" id="pedido">
                <h3 class="chart-title mb-3"><i class="ri-file-list-3-line text-muted"></i> O que o Encerramento pediu</h3>

                @if ($lastReturn)
                    <div class="return-alert mb-3" role="alert">
                        <div class="fw-bold text-danger"><i class="ri-arrow-go-back-line"></i> Devolvida para correção em {{ $lastReturn->created_at?->format('d/m/Y H:i') }}</div>
                        <div class="mt-1 pre-line">{{ $lastReturn->meta['message'] ?? 'Sem motivo informado.' }}</div>
                    </div>
                @endif

                <dl class="request-meta mb-3">
                    <div><dt>Obra</dt><dd>{{ $issue->Order?->Note?->note }}</dd></div>
                    <div><dt>Ordem</dt><dd>{{ $issue->Order?->ordem }}</dd></div>
                    <div><dt>Categoria</dt><dd>{{ $issue->Category?->name }}</dd></div>
                    <div><dt>Assunto</dt><dd>{{ $issue->Subcategory?->name }}</dd></div>
                    <div><dt>Enviada em</dt><dd>{{ $issue->requested_at?->format('d/m/Y H:i') ?? '—' }}</dd></div>
                    <div><dt>{{ $waiting ? 'Aguardando você' : 'Situação' }}</dt><dd>@if ($waiting && $waitingSince)<x-closure.age-chip :since="$waitingSince" prefix="há" />@else {{ $statusLabel }} @endif</dd></div>
                    <div><dt>Trava a Ordem?</dt><dd>{{ $issue->blocking ? 'Sim — a Ordem só segue após o seu retorno' : 'Não' }}</dd></div>
                </dl>

                @if ($issue->description)
                    <div class="mb-3">
                        <div class="ctl-label">Descrição</div>
                        <div class="pre-line">{{ $issue->description }}</div>
                    </div>
                @endif

                <div class="ctl-label">O que precisa ser feito</div>
                @if ($requirements !== [])
                    <ol class="mb-0 ps-3">
                        @foreach ($requirements as $requirement)
                            <li class="mb-1">{{ $requirement }}</li>
                        @endforeach
                    </ol>
                @else
                    <div class="text-muted small">O Encerramento não listou exigências separadas; leia a descrição acima e responda com sua mensagem.</div>
                @endif
            </div>

            {{-- Retorno (formulário ou leitura) --}}
            <div class="table-card p-3" id="retorno">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                    <h3 class="chart-title mb-0"><i class="ri-reply-line text-muted"></i> {{ $canRespond ? 'Seu retorno' : 'Retorno enviado' }}</h3>
                    @if ($canRespond && $totalReq)
                        <span class="pi-count {{ $answeredN === $totalReq ? 'is-full' : '' }}">{{ $answeredN }} de {{ $totalReq }} item(ns) respondido(s)</span>
                    @endif
                </div>

                @if ($canRespond)
                    <form wire:submit.prevent="submit" class="d-flex flex-column gap-3">
                        @error('form') <div class="alert alert-danger mb-0" role="alert">{{ $message }}</div> @enderror

                        @foreach ($requirements as $position => $requirement)
                            <div class="answer-row" wire:key="answer-{{ $position }}">
                                <div class="d-flex gap-2 align-items-start mb-2">
                                    <span class="answer-num">{{ $position + 1 }}</span>
                                    <div class="fw-semibold">{{ $requirement }}</div>
                                </div>
                                <div class="d-flex flex-wrap gap-2 mb-2" role="radiogroup" aria-label="Situação do item {{ $position + 1 }}">
                                    @foreach (ClosureIssueWorkflowService::ANSWER_LABELS as $value => $label)
                                        <input type="radio" class="btn-check" id="ans-{{ $position }}-{{ $value }}" value="{{ $value }}" wire:model="answers.{{ $position }}.status">
                                        <label class="btn btn-sm {{ $value === 'DONE' ? 'btn-outline-success' : ($value === 'NA' ? 'btn-outline-secondary' : 'btn-outline-danger') }}" for="ans-{{ $position }}-{{ $value }}">{{ $label }}</label>
                                    @endforeach
                                </div>
                                @if (($answers[$position]['status'] ?? '') !== '')
                                    <textarea class="form-control form-control-sm" rows="2" maxlength="1000" wire:model.defer="answers.{{ $position }}.note"
                                        placeholder="{{ ($answers[$position]['status'] ?? '') === 'DONE' ? 'Observação (opcional)' : 'Explique o motivo (obrigatório)' }}" aria-label="Observação do item {{ $position + 1 }}"></textarea>
                                @endif
                            </div>
                        @endforeach

                        <x-closure.form-field label="Mensagem para o Encerramento" name="message" :hint="$requirements === [] ? 'Descreva o que foi feito.' : 'Opcional: complemento geral ao que você respondeu item a item.'" :required="$requirements === []">
                            <textarea id="message" class="form-control" rows="4" maxlength="5000" wire:model.defer="message" placeholder="Escreva o seu retorno…"></textarea>
                        </x-closure.form-field>

                        <div class="file-drop" x-data="{ up: false, pct: 0 }" x-on:livewire-upload-start="up = true; pct = 0" x-on:livewire-upload-progress="pct = $event.detail.progress" x-on:livewire-upload-finish="up = false" x-on:livewire-upload-error="up = false">
                            <label for="attachments" class="fw-bold small mb-1 d-block"><i class="ri-attachment-2"></i> Evidências (fotos, PDF, planilhas…)</label>
                            <input id="attachments" type="file" class="form-control form-control-sm" multiple wire:model="attachments">
                            <div class="small text-muted mt-1">Até 10 MB por arquivo · PDF, imagens, Excel, Word, CSV, TXT ou ZIP. Os arquivos só são gravados quando você clicar em <strong>Enviar retorno</strong>.</div>
                            <div x-show="up" x-cloak class="mt-2" role="status" aria-live="polite">
                                <div class="d-flex justify-content-between small text-primary"><span><i class="ri-loader-4-line ri-spin"></i> Carregando arquivo(s)… não feche a página</span><strong x-text="pct + '%'"></strong></div>
                                <div class="progress" style="height: 6px;"><div class="progress-bar" :style="'width:' + pct + '%'"></div></div>
                            </div>
                            @foreach ($errors->get('attachments.*') as $messages)
                                @foreach ($messages as $message)<div class="text-danger small mt-1" role="alert"><i class="ri-error-warning-line"></i> {{ $message }}</div>@endforeach
                            @endforeach
                            @if (!empty($attachments))
                                <div class="small fw-bold mt-2 text-success"><i class="ri-checkbox-circle-line"></i> {{ count($attachments) }} arquivo(s) pronto(s) para enviar</div>
                                <div class="d-flex flex-wrap gap-2 mt-1">
                                    @foreach ($attachments as $index => $file)
                                        <span class="file-chip" wire:key="att-{{ $index }}"><i class="ri-file-line"></i><span>{{ $file->getClientOriginalName() }}</span><small class="text-muted">{{ number_format($file->getSize() / 1048576, 1, ',', '.') }} MB</small>
                                            <button type="button" class="btn-close" aria-label="Remover {{ $file->getClientOriginalName() }}" wire:click="removeAttachment({{ $index }})"></button>
                                        </span>
                                    @endforeach
                                </div>
                            @endif
                        </div>

                        <div class="d-flex justify-content-end">
                            <button type="submit" class="btn btn-success btn-dash px-4" wire:loading.attr="disabled" wire:target="submit,attachments"><i class="ri-send-plane-line" wire:loading.remove wire:target="submit"></i><i class="ri-loader-4-line ri-spin" wire:loading wire:target="submit"></i> <span wire:loading.remove wire:target="submit">Enviar retorno</span><span wire:loading wire:target="submit">Enviando…</span></button>
                        </div>
                    </form>
                @elseif ($lastReply)
                    <div class="small text-muted mb-2">Enviado em {{ $lastReply->created_at?->format('d/m/Y H:i') }} por {{ $lastReply->Actor?->name }}.</div>
                    @foreach ($lastReply->meta['answers'] ?? [] as $answer)
                        <div class="answer-row mb-2">
                            <div class="d-flex gap-2 align-items-start"><span class="answer-num">{{ $answer['index'] }}</span>
                                <div><div class="fw-semibold">{{ $answer['text'] }}</div>
                                    <x-closure.status-badge :label="ClosureIssueWorkflowService::ANSWER_LABELS[$answer['status']] ?? $answer['status']" :tone="$answer['status'] === 'DONE' ? 'green' : ($answer['status'] === 'NA' ? 'muted' : 'red')" />
                                    @if ($answer['note'])<div class="small mt-1 pre-line">{{ $answer['note'] }}</div>@endif
                                </div></div>
                        </div>
                    @endforeach
                    @if (!empty($lastReply->meta['message']))
                        <div class="pre-line">{{ $lastReply->meta['message'] }}</div>
                    @endif
                    @if ($replyFiles->isNotEmpty())
                        <div class="ctl-label mt-3">Evidências deste retorno ({{ $replyFiles->count() }})</div>
                        <div class="d-flex flex-wrap gap-2">
                            @foreach ($replyFiles as $replyFile)
                                <a target="_blank" rel="noopener" class="file-chip" href="{{ route('files.evidence.preview', ['file' => $replyFile->id]) }}"><i class="ri-file-line"></i><span>{{ $replyFile->original_name }}</span><small class="text-muted">{{ number_format(($replyFile->size ?? 0) / 1048576, 1, ',', '.') }} MB</small></a>
                            @endforeach
                        </div>
                    @endif
                @else
                    <x-closure.empty-state icon="ri-time-line" title="{{ $observing ? 'Visão somente leitura' : 'Ainda sem retorno' }}" text="{{ $observing ? 'A parceira responde por aqui; você acompanha o que ela enviar.' : 'Quando houver um retorno enviado, ele aparece aqui.' }}" />
                @endif
            </div>
        </div>

        <div class="col-12 col-xl-5 d-flex flex-column gap-3">
            <div class="table-card p-3">
                <h3 class="chart-title mb-3"><i class="ri-history-line text-muted"></i> Histórico desta pendência</h3>
                <x-closure.timeline :items="$timeline" />
            </div>

            <div class="table-card p-3">
                <h3 class="chart-title mb-3"><i class="ri-attachment-2 text-muted"></i> Arquivos <span class="od-count">{{ $files->count() }}</span></h3>
                @forelse ($files as $item)
                    <div class="d-flex justify-content-between align-items-center gap-2 py-1 border-bottom" wire:key="file-{{ $item['file']->id }}">
                        <a target="_blank" rel="noopener" href="{{ route('files.evidence.preview', ['file' => $item['file']->id]) }}" class="file-chip"><i class="ri-file-line"></i><span>{{ $item['file']->original_name }}</span></a>
                        <span class="small text-muted text-nowrap">{{ $item['by'] }} · {{ $item['at']?->format('d/m/Y') }}</span>
                    </div>
                @empty
                    <x-closure.empty-state icon="ri-folder-open-line" title="Nenhum arquivo" text="As evidências enviadas aparecem aqui." />
                @endforelse
            </div>
        </div>
    </div>
</div>
