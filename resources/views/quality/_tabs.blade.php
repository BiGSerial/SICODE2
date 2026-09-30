{{-- Abas do processo: Histórico, Discussão, Arquivos, Rodadas, Rejeições. --}}
@php
    use App\Support\QualityUi;

    $fileCount  = \App\Models\File::query()->where('note_id', $process->note_id)->count();
    $chatCount  = $process->Events->where('type', \App\Enum\QualityEventType::COMMENT_ADDED)->count();
    $roundCards = $process->Stages->groupBy(fn ($stage) => $stage->type->value . '-' . $stage->round_number);
@endphp
<div class="table-card" id="q-tabs" style="overflow: visible;">
    <ul class="nav q-tabs" role="tablist">
        <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#q-hist" type="button"><i class="ri-history-line"></i> Histórico</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#q-discussao" type="button"><i class="ri-chat-3-line"></i> Discussão @if ($chatCount)<span class="badge text-bg-primary">{{ $chatCount }}</span>@endif</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#q-arquivos" type="button"><i class="ri-gallery-line"></i> Arquivos <span class="badge text-bg-secondary">{{ $fileCount }}</span></button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#q-rodadas" type="button"><i class="ri-loop-right-line"></i> Rodadas <span class="badge text-bg-secondary">{{ $roundCards->count() }}</span></button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#q-rej" type="button"><i class="ri-arrow-go-back-line"></i> Rejeições @if ($process->Rejections->count())<span class="badge text-bg-danger">{{ $process->Rejections->count() }}</span>@endif</button></li>
    </ul>

    <div class="tab-content p-3">
        <div class="tab-pane fade show active" id="q-hist">@livewire('quality.process-timeline', ['processId' => $process->id], key('tl-' . $process->id))</div>
        <div class="tab-pane fade" id="q-discussao">@livewire('quality.process-chat', ['processId' => $process->id], key('chat-' . $process->id))</div>
        <div class="tab-pane fade" id="q-arquivos">@livewire('quality.process-files', ['processId' => $process->id], key('files-' . $process->id))</div>

        {{-- Rodadas --}}
        <div class="tab-pane fade" id="q-rodadas">
            @forelse ($roundCards->sortKeys() as $group => $stages)
                @php
                    $first = $stages->first();
                    $exec  = $stages->firstWhere('kind.value', 'EXECUTION');
                @endphp
                <div class="round-card">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div><span class="fw-bold">{{ $first->type->label() }}</span> <span class="chip chip-loop">↺ rodada {{ $first->round_number }}</span></div>
                        <span class="small text-muted">{{ $stages->min(fn ($s) => $s->dispatched_at ?? $s->created_at)?->format('d/m/Y H:i') }}</span>
                    </div>
                    <div class="round-steps">
                        @foreach ($stages->sortBy('id') as $stage)
                            @php
                                $who  = $stage->ExecutedBy?->name ?? $stage->AssignedUser?->name ?? $stage->ApprovedBy?->name;
                                $tone = in_array($stage->status->value, ['APPROVED', 'COMPLETED'], true) ? 'is-ok' : ($stage->status->value === 'REJECTED' ? 'is-bad' : '');
                            @endphp
                            <span class="round-step {{ $tone }}"><i class="{{ ['APPROVED' => 'ri-check-line', 'COMPLETED' => 'ri-check-line', 'REJECTED' => 'ri-close-line'][$stage->status->value] ?? 'ri-time-line' }}"></i><b>{{ $stage->level->label() }} · {{ $stage->kind->label() }}</b>@if ($who)<span>{{ \Illuminate\Support\Str::limit($who, 18) }}</span>@endif<span class="text-muted">{{ $stage->status->label() }}</span></span>
                        @endforeach
                    </div>
                    @if ($exec && $exec->submission_data)
                        <div class="mt-2 small text-muted">
                            @if (!empty($exec->submission_data['survey']))@php $sv = $exec->submission_data['survey']; @endphp Informe: postes {{ $sv['postes'] }} · DOE {{ $sv['doe'] }} · vegetação {{ $sv['ma'] }} · {{ $sv['conclusion'] }}@endif
                            @foreach ($exec->submission_data['orders'] ?? [] as $order)<span class="chip chip-where">{{ $order['order_number'] }} · {{ number_format((float) $order['total_cost'], 2, ',', '.') }}</span>@endforeach
                        </div>
                    @endif
                    @if ($exec && $exec->Files->count())
                        <div class="mt-2 d-flex flex-wrap gap-1">@foreach ($exec->Files as $stageFile)<a class="chip" href="{{ route('quality.process.file', [$process, $stageFile->file_id]) }}"><i class="ri-attachment-2"></i>{{ \Illuminate\Support\Str::limit($stageFile->File?->file_name, 28) }}</a>@endforeach</div>
                    @endif
                </div>
            @empty
                <div class="ob-empty"><i class="ri-loop-right-line"></i><div class="fw-bold">Nenhuma rodada ainda.</div></div>
            @endforelse
        </div>

        {{-- Rejeições --}}
        <div class="tab-pane fade" id="q-rej">
            @forelse ($process->Rejections as $rejection)
                @php $author = $rejection->Author?->name; @endphp
                <div class="rej-card">
                    <div class="d-flex justify-content-between flex-wrap gap-2">
                        <div class="d-flex align-items-center gap-2">
                            <span class="chat-avatar" style="width: 34px; height: 34px; background: {{ QualityUi::avatarTone($author) }}">{{ QualityUi::initials($author) }}</span>
                            <div><div class="fw-bold">{{ $author }} <span class="chip">{{ $rejection->level->label() }}</span></div><div class="small text-muted">{{ $rejection->type->short() }} · rodada {{ $rejection->round_number }} · devolvido a {{ $rejection->returned_to_level?->label() }}</div></div>
                        </div>
                        <span class="small text-muted">{{ $rejection->created_at?->format('d/m/Y H:i') }}</span>
                    </div>
                    <div class="d-flex flex-wrap gap-1 mt-2">
                        @foreach ($rejection->Items as $item)
                            <span class="chip chip-reason" title="{{ $item->observation }}"><i class="ri-price-tag-3-line"></i>{{ $item->Category?->name }}{{ $item->Subcategory ? ' · ' . $item->Subcategory->name : '' }}</span>
                        @endforeach
                    </div>
                    @foreach ($rejection->Items->filter(fn ($i) => $i->observation) as $item)<div class="small mt-1 text-muted">↳ {{ $item->Subcategory?->name ?? $item->Category?->name }}: {{ $item->observation }}</div>@endforeach
                    @if ($rejection->observation)<blockquote class="tl-quote">{{ $rejection->observation }}</blockquote>@endif
                </div>
            @empty
                <div class="ob-empty"><i class="ri-emotion-happy-line"></i><div class="fw-bold">Nenhuma rejeição registrada.</div></div>
            @endforelse
        </div>
    </div>
</div>
