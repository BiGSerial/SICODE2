@php
    use Carbon\Carbon;
@endphp
<div wire:poll.60s>
    <li class="nav-item dropdown mx-3">

        <a class="nav-link nav-icon" href="#" data-bs-toggle="dropdown">
            <i class="bi bi-bell align-middle text-edp-verde"></i>
            @if ($notifies->count())
                <span class="badge bg-danger badge-number">{{ $notifies->count() }}</span>
            @endif
        </a><!-- End Notification Icon -->

        <ul class="dropdown-menu dropdown-menu-end dropdown-menu-arrow notifications" wire:ignore.self>
            <li class="dropdown-header">
                Você possuí <strong>{{ $notifies->count() }}</strong> notificações.
                @if ($notifies->count() && $notifies->count() > $total_notifies)
                    <a href="#"><span class="badge rounded-pill bg-primary p-2 ms-2"
                            wire:click.prevent="recognize_all">Reconhecer Tudo</span></a>
                @endif
            </li>
            <li>
                <hr class="dropdown-divider">
            </li>
            @if ($notifies->count())
                @foreach ($notifies->take($total_notifies) as $notify)
                    <a href="" class="nav-link" wire:click.prevent="readed({{ $notify->id }})">
                        <li class="notification-item">

                            @if ($notify->status == 0)
                                <i class="bi bi-x-circle text-danger"></i>
                            @elseif($notify->status == 1)
                                <i class="bi bi-check-circle text-success"></i>
                            @elseif($notify->status == 2)
                                <i class="bi bi-exclamation-circle text-warning"></i>
                            @elseif($notify->status == 3)
                                <i class="bi bi-info-circle text-primary"></i>
                            @endif
                            <div>
                                <h6 class="fw-bold">{{ $notify->title }}</h6>
                                <p>{{ $notify->info }}</p>
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
