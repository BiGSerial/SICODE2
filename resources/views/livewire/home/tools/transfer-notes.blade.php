<div wire:poll.10s='toUpdateIdCount'>
    @if ($transferNotes->isEmpty())
        <div class="text-center py-4">
            <div class="mb-3">
                <i class="ri-emotion-happy-line" style="font-size: 3rem; color: #dee2e6;"></i>
            </div>
            <p class="text-muted mb-0">Nenhuma transferência pendente encontrada.</p>
        </div>
    @else
        @foreach ($transferNotes as $note)
            <div class="transfer-item">
                <div class="transfer-header">
                    <div class="transfer-user">
                        <div class="user-avatar">
                            <i class="ri-user-line"></i>
                        </div>
                        <div class="user-info">
                            <div class="user-name">{{ $note->From?->name }}</div>
                            <div class="user-role">{{ $note->From?->company?->name }}</div>
                        </div>
                    </div>
                    <div class="transfer-time">
                        <i class="ri-time-line me-1"></i>
                        {{ $note->created_at->diffForHumans() }}
                    </div>
                </div>

                <div class="transfer-content">
                    <div class="service-badge">
                        <i class="{{ $note->service?->icon ?? 'ri-hammer-line' }} me-1"></i>
                        {{ $note->service?->service }}
                    </div>
                    <div class="transfer-info">
                        {!! nl2br(e($note->info)) !!}
                    </div>
                </div>

                <div class="transfer-actions">
                    @if ($note->status === 19)
                        <button class="btn-action btn-accept" data-bs-toggle="tooltip" title="Aceitar Transferência"
                            wire:click="acceptTransfer({{ $note->id }})">
                            <i class="ri-check-line"></i>
                        </button>
                        <button class="btn-action btn-reject" data-bs-toggle="tooltip" title="Rejeitar Transferência">
                            <i class="ri-close-line"></i>
                        </button>
                    @endif
                </div>
            </div>
        @endforeach
    @endif

    @push('css')
        <style>
            .transfer-item {
                background: rgba(102, 126, 234, 0.02);
                border: 1px solid rgba(102, 126, 234, 0.1);
                border-radius: 12px;
                padding: 1.25rem;
                margin-bottom: 1rem;
                transition: all 0.3s ease;
                position: relative;
                overflow: hidden;
            }

            .transfer-item::before {
                content: '';
                position: absolute;
                left: 0;
                top: 0;
                bottom: 0;
                width: 4px;
                background: linear-gradient(135deg, #667eea, #764ba2);
                border-radius: 0 2px 2px 0;
            }

            .transfer-item:hover {
                transform: translateY(-2px);
                box-shadow: 0 4px 20px rgba(102, 126, 234, 0.15);
                background: rgba(102, 126, 234, 0.05);
            }

            .transfer-item:last-child {
                margin-bottom: 0;
            }

            .transfer-header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-bottom: 1rem;
            }

            .transfer-user {
                display: flex;
                align-items: center;
                gap: 0.75rem;
            }

            .user-avatar {
                width: 40px;
                height: 40px;
                border-radius: 10px;
                background: linear-gradient(135deg, #667eea, #764ba2);
                color: white;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 1.1rem;
                flex-shrink: 0;
            }

            .user-avatar.bg-success {
                background: linear-gradient(135deg, #56ab2f, #a8e6cf);
            }

            .user-avatar.bg-info {
                background: linear-gradient(135deg, #667eea, #764ba2);
            }

            .user-info {
                display: flex;
                flex-direction: column;
            }

            .user-name {
                font-weight: 600;
                color: #2c3e50;
                font-size: 0.95rem;
                margin-bottom: 0.125rem;
            }

            .user-role {
                font-size: 0.8rem;
                color: #6c757d;
                font-weight: 500;
            }

            .transfer-time {
                font-size: 0.8rem;
                color: #6c757d;
                background: rgba(108, 117, 125, 0.1);
                padding: 0.25rem 0.5rem;
                border-radius: 6px;
                font-weight: 500;
            }

            .transfer-content {
                margin-bottom: 1rem;
            }

            .service-badge {
                display: inline-flex;
                align-items: center;
                background: linear-gradient(135deg, #667eea, #764ba2);
                color: white;
                padding: 0.4rem 0.75rem;
                border-radius: 8px;
                font-size: 0.8rem;
                font-weight: 600;
                margin-bottom: 0.75rem;
                text-transform: uppercase;
                letter-spacing: 0.5px;
            }

            .service-badge.bg-warning {
                background: linear-gradient(135deg, #f093fb, #f5576c);
            }

            .service-badge.bg-primary {
                background: linear-gradient(135deg, #667eea, #764ba2);
            }

            .transfer-info {
                color: #495057;
                font-size: 0.9rem;
                line-height: 1.5;
                padding-left: 0.5rem;
                border-left: 3px solid rgba(102, 126, 234, 0.2);
                background: rgba(255, 255, 255, 0.7);
                padding: 0.75rem;
                border-radius: 8px;
            }

            .transfer-actions {
                display: flex;
                gap: 0.5rem;
                justify-content: flex-end;
            }

            .btn-action {
                width: 40px;
                height: 40px;
                border-radius: 10px;
                border: none;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 1.1rem;
                transition: all 0.3s ease;
                cursor: pointer;
                position: relative;
                overflow: hidden;
            }

            .btn-action::before {
                content: '';
                position: absolute;
                top: 50%;
                left: 50%;
                width: 0;
                height: 0;
                border-radius: 50%;
                background: rgba(255, 255, 255, 0.3);
                transition: all 0.3s ease;
                transform: translate(-50%, -50%);
            }

            .btn-action:hover::before {
                width: 100%;
                height: 100%;
            }

            .btn-accept {
                background: linear-gradient(135deg, #56ab2f, #a8e6cf);
                color: white;
            }

            .btn-accept:hover {
                transform: translateY(-2px);
                box-shadow: 0 4px 15px rgba(86, 171, 47, 0.4);
            }

            .btn-reject {
                background: linear-gradient(135deg, #ff6b6b, #ee5a24);
                color: white;
            }

            .btn-reject:hover {
                transform: translateY(-2px);
                box-shadow: 0 4px 15px rgba(255, 107, 107, 0.4);
            }

            /* Responsive */
            @media (max-width: 768px) {
                .transfer-header {
                    flex-direction: column;
                    align-items: flex-start;
                    gap: 0.5rem;
                }

                .transfer-time {
                    align-self: flex-end;
                }

                .user-avatar {
                    width: 35px;
                    height: 35px;
                    font-size: 1rem;
                }

                .transfer-actions {
                    justify-content: center;
                    gap: 1rem;
                }

                .btn-action {
                    width: 45px;
                    height: 45px;
                }
            }
        </style>
    @endpush

    @push('script')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                window.addEventListener('transferNotesUpdated', function(event) {

                    const count = event.detail.count;
                    const badge = document.getElementById('{{ $idCount }}');

                    if (badge) {
                        badge.textContent = count > 0 ? (count > 1 ? count + ' Pendentes' : ' Pendente') : '';
                        badge.classList.toggle('d-none', count === 0);
                    }
                });
            });
        </script>
    @endpush
</div>
