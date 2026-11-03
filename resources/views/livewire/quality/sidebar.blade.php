<div>
    <x-nav.sidebar :nav="\App\Support\Menu\QualityNav::class" :route="$routeKey" label="Menu da Qualidade" :context="$context">
        {{-- Perfil de quem olha (e a troca de modo para quem acumula Gestão e equipe): painel recolhível do rodapé. --}}
        <details class="snav-occ" data-snav-persist="quality-role" open>
            <summary class="snav-occ-head">
                <span class="snav-occ-ico"><i class="ri-shield-user-line" aria-hidden="true"></i></span>
                <span class="snav-occ-title">Seu perfil</span>
                <i class="ri-arrow-down-s-line snav-occ-chev" aria-hidden="true"></i>
            </summary>
            <div class="snav-occ-body">
                <span class="badge text-bg-warning">{{ $roles->roleLabel($user) }}</span>
                @unless ($isManager)
                    <div class="snav-occ-meta mt-1">{{ $roles->companyNames($user) }}</div>
                @endunless
                @if ($roles->isDualMode($user))
                    <form method="post" action="{{ route('quality.view-as') }}" class="mt-2">
                        @csrf
                        <div class="snav-occ-meta mb-1">Atuar como</div>
                        <div class="btn-group btn-group-sm w-100" role="group">
                            <button name="mode" value="manager" class="btn {{ $roles->viewMode($user) === 'manager' ? 'btn-warning' : 'btn-outline-light' }}">Gestão</button>
                            <button name="mode" value="member" class="btn {{ $roles->viewMode($user) === 'member' ? 'btn-warning' : 'btn-outline-light' }}">Equipe N1/N2</button>
                        </div>
                    </form>
                @endif
            </div>
        </details>
    </x-nav.sidebar>
</div>
