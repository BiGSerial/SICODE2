@php
    use App\Custom\Viabilitiesstatus;
    use App\Custom\Notestatus;
    use Carbon\Carbon;
@endphp
<table class="table table-striped table-hover table-sm my-0">
    <thead class="table-dark text-center">
        <tr>
            <th scope="col">ID</th>
            <th scope="col">Nome</th>
            <th scope="col">Email</th>
            <th scope="col">Empresa</th>
            <th scope="col">Permissões</th>
            <th scope="col">Serviços</th>
            <th scope="col">Status</th>

        </tr>
    </thead>
    <tbody>
        @foreach ($users_l as $theUser)
            <tr>
                <td>{{ $theUser->id }}</td>
                <td>{{ $theUser->name }}</td>
                <td>{{ $theUser->email }}</td>
                <td>{{ isset($theUser->Employee->Contract->Company->name) ? mb_strtoupper($theUser->Employee->Contract->Company->name) : '' }}
                </td>
                <td>

                    @if ($theUser->superadm)
                        <p>SuperAdm</p>
                    @endif
                    @if ($theUser->admin)
                        <p>Admin</p>
                    @endif
                    @if ($theUser->management)
                        <p>Admin</p>
                    @endif
                    @if ($theUser->engineer)
                        <p>Engenheiro</p>
                    @endif
                    @if ($theUser->responsible)
                        <p>Responsável</p>
                    @endif
                    @if ($theUser->operator)
                        <p>Operador</p>
                    @endif
                    @if ($theUser->user)
                        <p>Usuário</p>
                    @endif
                    @if ($theUser->onlyparner)
                        <p>Empreiteira</p>
                    @endif
                    @if ($theUser->analyst)
                        <p>Analista</p>
                    @endif
                </td>
                <td>
                    @if ($theUser->ToServices->count())
                        @foreach ($theUser->ToServices as $service)
                            <p>{{ $service->Service->service }}
                                ({{ $service->service ? 'O' : '--' }}|{{ $service->dispatch ? 'D' : '--' }})
                            </p>
                        @endforeach
                    @endif
                </td>
                <td>
                    @if ($theUser->trashed())
                        <span class="badge text-bg-danger">REMOVIDO</span>
                    @else
                        <p class="mt-1 mb-0"><span class="fw-bold">Visto em:</span>
                            {{ isset($theUser->Watchdog->updated_at) ? Carbon::parse($theUser->Watchdog->updated_at)->diffForHumans(Carbon::now()) : 'Nunca Entrou' }}
                        </p>
                    @endif
                </td>

            </tr>
        @endforeach
    </tbody>
</table>
