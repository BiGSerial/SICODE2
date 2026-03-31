<div wire:poll.8s>
    <x-show-loading target="readed" />
    <li class="nav-item dropdown ms-2" id="notification-dropdown">
        @php
            use Carbon\Carbon;
            use App\Helpers\NotifyStatus;
            use App\Support\Notifications\UserNotificationData;
        @endphp

        <a class="nav-link nav-icon" href="#" data-bs-toggle="dropdown" id="notification-toggle">
            <i class="bi bi-bell align-middle text-edp-verde"></i>
            @if ($notifies->whereNull('read_at')->count())
                <span class="badge bg-danger badge-number">{{ $notifies->whereNull('read_at')->count() }}</span>
            @endif
        </a><!-- End Notification Icon -->

        <ul class="dropdown-menu dropdown-menu-end dropdown-menu-arrow notifications" wire:ignore.self
            style="max-height: 500px; overflow-y: auto; scrollbar-width: thin;" id="notification-menu">

            <li class="dropdown-header sticky-top bg-white">
                Você possuí <strong>{{ $notifies->whereNull('read_at')->count() }}</strong> notificações novas.
                @if ($notifies->whereNull('read_at')->count())
                    <a href="#" wire:click.prevent="recognize_all" class="badge rounded-pill bg-primary p-2 ms-2">
                        Reconhecer Tudo
                    </a>
                @endif
            </li>

            <li>
                <hr class="dropdown-divider">
            </li>

            @if ($notifies->isNotEmpty())
                @foreach ($notifies->take($total_notifies) as $notify)
                    @php
                        $payload = UserNotificationData::fromArray($notify->data);
                        $status = NotifyStatus::getStatus($payload->status());
                    @endphp
                    <li class="notification-item" style="{{ $notify->read_at ? 'background-color: #d3d3d3' : '' }}">
                        <a wire:key="{{ $notify->id }}" href="#"
                            wire:click.prevent="readed('{{ $notify->id }}')"
                            class="d-flex align-items-start text-decoration-none">
                            <i class="{{ $status->icon ?? '' }} {{ $status->color ?? '' }}"></i>
                            <div class="ms-2">
                                <h6 class="fw-bold text-secondary">{{ $payload->title() }}</h6>
                                <p class="mb-1">{!! $payload->message() !!}</p>
                                <small class="d-inline-flex align-items-center gap-1 text-muted">
                                    <i class="{{ $payload->actionIcon() }}"></i>
                                    {{ $payload->actionLabel() }}
                                </small>
                                <p class="mt-3 mb-0 text-muted small">
                                    {{ Carbon::parse($notify->created_at)->diffForHumans() }}</p>
                            </div>
                        </a>
                    </li>
                    <li>
                        <hr class="dropdown-divider">
                    </li>
                @endforeach
            @endif

            <li class="dropdown-footer">
                <a href="#" wire:click.prevent="$emitTo('components.notify.all-notifies', 'openNotifies')">Mostrar
                    todas as Notificações</a>
            </li>
        </ul><!-- End Notification Dropdown Items -->
    </li><!-- End Notification Nav -->

    {{-- ========= MODAL: TODAS AS NOTIFICAÇÕES (render no <body>) ========= --}}
    {{-- @push('modals') --}}

    {{-- @endpush --}}

    {{-- Fechar dropdown ao clicar fora (mantido do seu código) + eventos para o modal --}}

</div>
