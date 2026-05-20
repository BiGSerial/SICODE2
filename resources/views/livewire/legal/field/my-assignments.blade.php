<div>
    {{-- KPI Cards --}}
    <div class="row g-3 mb-4">
        <div class="col-4">
            <x-legal.kpi-card title="Aguardando Aceite" :value="$kpis['pending']" color="warning" icon="bi-bell"
                              wire:click="setTab('pending')" />
        </div>
        <div class="col-4">
            <x-legal.kpi-card title="Em Andamento" :value="$kpis['in_progress']" color="primary" icon="bi-hourglass-split"
                              wire:click="setTab('in_progress')" />
        </div>
        <div class="col-4">
            <x-legal.kpi-card title="Para Corrigir" :value="$kpis['correction']" color="danger" icon="bi-arrow-return-left"
                              wire:click="setTab('correction')" />
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
            <h5 class="mb-0 fw-bold" style="color: var(--legal-primary)">
                <i class="bi bi-person-check-fill me-2"></i>Minhas Atribuições Jurídicas
            </h5>
            <input type="text" class="form-control form-control-sm" style="width:250px"
                   placeholder="Buscar processo ou assunto..." wire:model.debounce.400ms="search" />
        </div>
        <div class="card-body">
            {{-- Abas --}}
            <ul class="nav nav-tabs mb-4">
                @foreach([
                    ['pending',     'Pendentes',     $kpis['pending']],
                    ['in_progress', 'Em Andamento',  $kpis['in_progress']],
                    ['correction',  'Para Corrigir', $kpis['correction']],
                    ['answered',    'Respondidas',   null],
                    ['all',         'Todas',         null],
                ] as [$key, $label, $count])
                    <li class="nav-item">
                        <button class="nav-link {{ $tab === $key ? 'active' : '' }}" wire:click="setTab('{{ $key }}')">
                            {{ $label }}
                            @if($count !== null && $count > 0)
                                <span class="badge {{ $key === 'correction' ? 'bg-danger' : 'bg-secondary' }} ms-1">{{ $count }}</span>
                            @endif
                        </button>
                    </li>
                @endforeach
            </ul>

            {{-- Lista de Cards --}}
            @forelse($assignments as $assignment)
                @php
                    $demand  = $assignment->legalDemand;
                    $status  = $assignment->status instanceof \BackedEnum ? $assignment->status->value : $assignment->status;
                    $isOverdue = $demand?->source_due_at && \Carbon\Carbon::parse($demand->source_due_at)->isPast();
                    $cardClass = match(true) {
                        $status === 'returned_for_correction' => 'legal-card--correction',
                        $isOverdue                            => 'legal-card--overdue',
                        default                               => '',
                    };
                @endphp
                <div class="legal-card {{ $cardClass }}">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div class="d-flex gap-2">
                            @if($demand?->source_type)
                                <span class="badge bg-secondary">{{ $demand->source_type }}</span>
                            @endif
                            <x-legal.status-badge :status="$assignment->status" />
                        </div>
                        @if($demand?->source_due_at)
                            <x-legal.due-date-chip :date="$demand->source_due_at" />
                        @endif
                    </div>

                    <div class="fw-semibold">
                        {{ $demand?->source_case_number ?? $demand?->source_process_number ?? 'S/N' }}
                    </div>
                    @if($demand?->legalCase?->company_name)
                        <div class="text-muted small">{{ $demand->legalCase->company_name }}</div>
                    @endif
                    @if($demand?->subject)
                        <div class="small mt-1">{{ Str::limit($demand->subject, 80) }}</div>
                    @endif

                    {{-- Bloco de devolução --}}
                    @if($status === 'returned_for_correction')
                        <div class="mt-2 border-start border-danger border-3 ps-3">
                            <div class="small fw-semibold text-danger">Devolvida para Correção</div>
                            @if($assignment->correction_note)
                                <div class="small">"{{ $assignment->correction_note }}"</div>
                            @endif
                            @if($assignment->corrected_at ?? $assignment->updated_at)
                                <div class="small text-muted">
                                    Devolvida em {{ \Carbon\Carbon::parse($assignment->updated_at)->format('d/m/Y H:i') }}
                                    por {{ $assignment->sentBy?->name ?? '—' }}
                                </div>
                            @endif
                        </div>
                    @else
                        <div class="small text-muted mt-2">
                            Enviado por: {{ $assignment->sentBy?->name ?? '—' }}
                            em {{ \Carbon\Carbon::parse($assignment->created_at)->format('d/m/Y H:i') }}
                        </div>
                        @if($assignment->message)
                            <div class="small border-start border-2 ps-2 mt-1 text-muted">
                                "{{ Str::limit($assignment->message, 100) }}"
                            </div>
                        @endif
                        @if($assignment->due_at)
                            <div class="small mt-1">
                                Prazo para resposta: <x-legal.due-date-chip :date="$assignment->due_at" />
                            </div>
                        @endif
                    @endif

                    <div class="d-flex justify-content-end gap-2 mt-3">
                        @if(in_array($status, ['sent']))
                            <button class="btn btn-sm btn-warning" wire:click="confirmReceipt({{ $assignment->id }})">
                                <i class="bi bi-check-lg me-1"></i>Confirmar Recebimento
                            </button>
                        @endif
                        <a href="{{ route('legal.field.response', $assignment->id) }}"
                           class="btn btn-sm {{ $status === 'returned_for_correction' ? 'btn-danger' : 'btn-primary' }}">
                            <i class="bi bi-pencil me-1"></i>
                            {{ $status === 'returned_for_correction' ? 'Corrigir e Reenviar' : 'Abrir e Responder' }}
                        </a>
                    </div>
                </div>
            @empty
                @php
                    $emptyMessages = [
                        'pending'     => ['icon' => 'bi-check-circle', 'msg' => 'Nenhuma solicitação aguardando seu aceite. Bom trabalho!'],
                        'in_progress' => ['icon' => 'bi-inbox',        'msg' => 'Nenhuma atribuição em andamento no momento.'],
                        'correction'  => ['icon' => 'bi-patch-check',  'msg' => 'Nenhuma devolução para corrigir. Continue assim!'],
                        'answered'    => ['icon' => 'bi-check-all',    'msg' => 'Nenhuma atribuição respondida no filtro atual.'],
                        'all'         => ['icon' => 'bi-inbox',        'msg' => 'Nenhuma atribuição encontrada.'],
                    ];
                    $em = $emptyMessages[$tab] ?? $emptyMessages['all'];
                @endphp
                <x-legal.empty-state :icon="$em['icon']" :message="$em['msg']" />
            @endforelse

            <x-legal.pagination :paginator="$assignments" />
        </div>
    </div>

    <x-show-loading />
</div>
