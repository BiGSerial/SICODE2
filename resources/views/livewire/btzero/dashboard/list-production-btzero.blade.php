@php
    use App\Custom\Notestatus;
@endphp
<div wire:poll.120000ms>
    <div class="card">
        <div class="card-header edp-bg-seoweedgreen-100 py-1">
            <h4 class="my-0 text-white">Ultimas Produções</h4>
        </div>
        @if ($productions)
            <div class="table-responsible">
                <table class="table table-hover table-striped">
                    <thead>
                        <tr>
                            <th>Note</th>
                            <th>Serviço</th>
                            <th>Usuário</th>
                            <th>Empresa</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($productions as $production)
                            <tr wire:key="{{ $production->id }}">
                                <td class="fw-bold">{{ $production->Note->note }}</td>
                                <td>{{ $production->Service->service }}</td>
                                <td>{{ isset($production->User->name) ? explode(' ', $production->User->name)[0] : '' }}
                                </td>
                                <td>{{ isset($production->Company->name) ? explode(' ', $production->Company->name)[0] : '' }}
                                </td>
                                <td><span class="badge text-bg-{{ Notestatus::status($production->status)->color }}">
                                        {{ Notestatus::status($production->status)->status }} </span></td>
                            </tr>
                            @push('styles')
                                <style>
                                    @keyframes slideDown {
                                        0% {
                                            transform: translateY(-100%);
                                            opacity: 0;
                                        }

                                        100% {
                                            transform: translateY(0);
                                            opacity: 1;
                                        }
                                    }

                                    @keyframes fadeOut {
                                        0% {
                                            opacity: 1;
                                        }

                                        100% {
                                            opacity: 0;
                                        }
                                    }

                                    .new-row {
                                        animation: slideDown 0.5s ease-out;
                                    }

                                    .old-row {
                                        animation: fadeOut 0.5s ease-out;
                                    }
                                </style>
                            @endpush

                            <script>
                                document.addEventListener('livewire:load', function() {
                                    let n = 0;
                                    Livewire.hook('message.processed', (message, component) => {
                                        n += 1;

                                        console.log(n);
                                        const rows = document.querySelectorAll('tbody tr');
                                        if (rows.length > 0) {
                                            rows[0].classList.add('new-row');
                                            if (rows.length > 1) {
                                                rows[rows.length - 1].classList.add('old-row');
                                                setTimeout(() => {
                                                    rows[rows.length - 1].remove();
                                                }, 500);
                                            }
                                        }
                                    });
                                });
                            </script>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="card-body text-center">
                <h4>SEM PRODUÇÃO PARA EXIBIR</h4>
            </div>
        @endif
    </div>
