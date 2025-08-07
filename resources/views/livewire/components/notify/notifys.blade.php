@php
    use Carbon\Carbon;
    use App\Helpers\NotifyStatus;
@endphp
<div wire:poll.20s>
    <li class="nav-item dropdown mx-3">

        <a class="nav-link nav-icon" href="#" data-bs-toggle="dropdown">
            <i class="bi bi-bell align-middle text-edp-verde"></i>
            @if ($notifies->whereNull('read_at')->count())
                <span class="badge bg-danger badge-number">{{ $notifies->whereNull('read_at')->count() }}</span>
            @endif
        </a><!-- End Notification Icon -->

        <ul class="dropdown-menu dropdown-menu-end dropdown-menu-arrow notifications" wire:ignore.self
            style="max-height: 500px; overflow-y: auto; scrollbar-width: thin;">
            <li class="dropdown-header sticky-top bg-white">
                Você possuí <strong>{{ $notifies->whereNull('read_at')->count() }}</strong> notificações novas.
                @if ($notifies->whereNull('read_at')->count())
                    <a href="#"><span class="badge rounded-pill bg-primary p-2 ms-2"
                            wire:click.prevent="recognize_all">Reconhecer Tudo</span></a>
                @endif
            </li>
            <li>
                <hr class="dropdown-divider">
            </li>
            @if ($notifies->isNotEmpty())
                @foreach ($notifies->take($total_notifies) as $notify)
                    @php
                        // Se seu NotifyStatus trabalha com array, envie o status do data
                        $status = NotifyStatus::getStatus($notify->data['status'] ?? null);
                    @endphp
                    <a wire:key='{{ $notify->id }}' href="" class="nav-link"
                        wire:click.prevent="readed('{{ $notify->id }}')"
                        style="{{ $notify->read_at ? 'background-color: #d3d3d3' : '' }}">
                        <li class="notification-item">
                            <i class="{{ $status->icon ?? '' }} {{ $status->color ?? '' }}"></i>
                            <div>
                                <h6 class="fw-bold">{{ $notify->data['titulo'] ?? '-' }}</h6>
                                <p>{!! $notify->data['mensagem'] ?? '-' !!}</p>
                                <p class="mt-3">{{ Carbon::parse($notify->created_at)->diffForHumans() }}</p>
                            </div>
                        </li>
                    </a>
                    <li>
                        <hr class="dropdown-divider">
                    </li>
                @endforeach
            @endif
            <li class="dropdown-footer">
                <a href="#">Mostrar todas as Notificações</a>
            </li>

        </ul><!-- End Notification Dropdown Items -->

    </li><!-- End Notification Nav -->
</div>
