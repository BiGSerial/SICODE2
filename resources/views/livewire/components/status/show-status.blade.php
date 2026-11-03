@php
    use App\Custom\Notestatus;
    use App\Support\Ops\Tone;
@endphp
<div>
    {{-- Modal no padrão das listas operacionais (docs/PADRAO_LISTAS_OPERACIONAIS.md §11). --}}
    <div wire:ignore.self class="modal fade ops-modal" id="statusView" tabindex="-1" aria-labelledby="statusViewLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content closure-dash ops-list">
                @if ($status)
                    <div class="modal-header">
                        <h1 class="modal-title" id="statusViewLabel">
                            <span class="modal-eyebrow">Histórico de status</span>
                            {{ $production->Note->note }}
                            <x-ops.badge :tone="Tone::forProductionStatus($status->status)" :label="Notestatus::status($status->status)->status" />
                        </h1>
                        <button type="button" class="btn-close btn-close-white ms-auto" data-bs-dismiss="modal" aria-label="Fechar"></button>
                    </div>

                    <div class="modal-body">
                        <div class="ops-modal-card tone-{{ Tone::forProductionStatus($status->status) }}">
                            <div class="ops-modal-card-head"><i class="{{ Notestatus::status($status->status)->icon }}" aria-hidden="true"></i> Informação do registro</div>
                            <div class="ops-modal-card-body">{{ $status->info ?: 'Sem informação registrada.' }}</div>
                            <div class="ops-modal-meta">
                                <div><i class="ri-calendar-line" aria-hidden="true"></i><strong>{{ date('d/m/Y H:i:s', strtotime($status->created_at)) }}</strong></div>
                                <div><i class="ri-user-line" aria-hidden="true"></i>{{ $status->User->name }}</div>
                                <div><i class="ri-stack-line" aria-hidden="true"></i>{{ $status->Service ? $status->Service->service : '---' }}</div>
                            </div>
                        </div>
                    </div>
                @endif

                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Fechar</button>
                </div>
            </div>
        </div>
    </div>
</div>
