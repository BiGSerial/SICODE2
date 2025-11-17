<div>
    <section class="section profile">
        <div class="row">
            <div class="col-xl-4">
                <div class="card mb-4">
                    <div class="card-body profile-card pt-4 d-flex flex-column align-items-center">
                        <div class="position-relative mb-3">
                            <img src="{{ $user->avatar_url }}" alt="Avatar" class="rounded-circle avatar-circle"
                                style="--avatar-size: 140px;">
                            @if ($delegationsAsDelegate && $delegationsAsDelegate->isNotEmpty())
                                <span class="badge bg-warning text-dark position-absolute top-0 start-100 translate-middle">
                                    Delegado
                                </span>
                            @endif
                        </div>
                        <h2 class="text-center">{{ $user->name }}</h2>
                        <h3 class="text-center text-muted fs-6">{{ $user->company?->name }}</h3>
                        <p class="text-center text-muted small mb-0">
                            {{ $user->company?->Address?->first()?->city }},
                            {{ $user->company?->Address?->first()?->uf }}
                        </p>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span class="fw-semibold">Estou cobrindo</span>
                        <span class="badge bg-primary">{{ $delegationsAsDelegate->count() }}</span>
                    </div>
                    <div class="card-body">
                        @forelse ($delegationsAsDelegate as $delegation)
                            <div class="mb-3">
                                <div class="fw-semibold">{{ $delegation->principal->name }}</div>
                                <small class="text-muted d-block">
                                    De {{ $delegation->valid_from?->format('d/m/Y H:i') }} ate
                                    {{ $delegation->valid_to?->format('d/m/Y H:i') ?? 'sem data final' }}
                                </small>
                                @if ($delegation->reason)
                                    <div class="small text-muted">{{ $delegation->reason }}</div>
                                @endif
                            </div>
                        @empty
                            <p class="text-muted mb-0">Nenhuma delegacao ativa como delegado.</p>
                        @endforelse
                    </div>
                </div>
            </div>

            <div class="col-xl-8">
                @if (session()->has('profileMessage'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        {{ session('profileMessage') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                <div class="card mb-4">
                    <div class="card-header">
                        <span class="fw-semibold">Informacoes basicas</span>
                    </div>
                    <div class="card-body">
                        <form wire:submit.prevent="saveProfile" class="row g-3">
                            <div class="col-12">
                                <label class="form-label">Nome completo</label>
                                <input type="text" class="form-control" wire:model.defer="user.name">
                                @error('user.name')
                                    <span class="text-danger small">{{ $message }}</span>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Email</label>
                                <input type="email" class="form-control" wire:model.defer="user.email">
                                @error('user.email')
                                    <span class="text-danger small">{{ $message }}</span>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Matricula / Registro</label>
                                <input type="text" class="form-control" wire:model.defer="user.Registration">
                                @error('user.Registration')
                                    <span class="text-danger small">{{ $message }}</span>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Empresa</label>
                                <input type="text" class="form-control" value="{{ $user->company?->name }}" disabled>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Telefone (empresa)</label>
                                <input type="text" class="form-control" value="{{ $user->company?->telephone }}"
                                    disabled>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Endereco</label>
                                <input type="text" class="form-control"
                                    value="{{ $user->company?->Address?->first()?->street }}" disabled>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Municipio</label>
                                <input type="text" class="form-control"
                                    value="{{ $user->company?->Address?->first()?->city }}" disabled>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Estado</label>
                                <input type="text" class="form-control"
                                    value="{{ $user->company?->Address?->first()?->uf }}" disabled>
                            </div>
                            <div class="col-12 text-end">
                                <button type="submit" class="btn btn-primary" wire:loading.attr="disabled">
                                    Salvar informacoes
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span class="fw-semibold">Avatar</span>
                        <small class="text-muted">Use upload ou DiceBear</small>
                    </div>
                    <div class="card-body">
                        <form wire:submit.prevent="saveAvatar">
                            <div class="mb-3">
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" id="avatarModeUpload" value="upload"
                                        wire:model="avatarMode">
                                    <label class="form-check-label" for="avatarModeUpload">Enviar arquivo</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" id="avatarModeDicebear"
                                        value="dicebear" wire:model="avatarMode">
                                    <label class="form-check-label" for="avatarModeDicebear">DiceBear</label>
                                </div>
                            </div>

                            @if ($avatarMode === 'upload')
                                <div class="mb-3" wire:key="avatar-upload-mode">
                                    <label class="form-label">Arquivo (jpg, png, max 2MB)</label>
                                    <input type="file" class="form-control" wire:model="avatarUpload" accept="image/*">
                                    @error('avatarUpload')
                                        <span class="text-danger small">{{ $message }}</span>
                                    @enderror
                                </div>
                                @if ($avatarUpload)
                                    <div class="mb-3" wire:key="avatar-upload-preview">
                                        <span class="small text-muted d-block">Preview</span>
                                        <img src="{{ $avatarUpload->temporaryUrl() }}" class="rounded-circle avatar-circle"
                                            style="--avatar-size: 100px;">
                                    </div>
                                @endif
                            @else
                                <div class="mb-3" wire:key="avatar-dicebear-mode">
                                    <label class="form-label">Seed (ex.: ferias-julho-2024)</label>
                                    <input type="text" class="form-control" wire:model="avatarSeed">
                                    @error('avatarSeed')
                                        <span class="text-danger small">{{ $message }}</span>
                                    @enderror
                                </div>
                                @if ($avatarSeed)
                                    <div class="mb-3" wire:key="avatar-dicebear-preview">
                                        <span class="small text-muted d-block">Preview</span>
                                        <img src="https://api.dicebear.com/9.x/pixel-art/svg?seed={{ rawurlencode($avatarSeed) }}"
                                            alt="DiceBear Preview" class="rounded-circle avatar-circle"
                                            style="--avatar-size: 100px;">
                                    </div>
                                @endif
                            @endif

                            <button type="submit" class="btn btn-outline-primary" wire:loading.attr="disabled">
                                Atualizar avatar
                            </button>
                        </form>
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-header">
                        <span class="fw-semibold">Delegacoes</span>
                    </div>
                    <div class="card-body">
                        <form wire:submit.prevent="saveDelegation" class="row g-3">
                            <div class="col-12">
                                <label class="form-label">Delegar para</label>
                                <select class="form-select" wire:model.defer="delegationForm.delegate_id">
                                    <option value="">Selecione um usuario</option>
                                    @foreach ($availableDelegates as $delegate)
                                        <option value="{{ $delegate->id }}">
                                            {{ $delegate->name }} ({{ $delegate->email }})
                                        </option>
                                    @endforeach
                                </select>
                                @error('delegationForm.delegate_id')
                                    <span class="text-danger small d-block">{{ $message }}</span>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Inicio</label>
                                <input type="datetime-local" class="form-control"
                                    wire:model.defer="delegationForm.valid_from">
                                @error('delegationForm.valid_from')
                                    <span class="text-danger small d-block">{{ $message }}</span>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Fim (opcional)</label>
                                <input type="datetime-local" class="form-control"
                                    wire:model.defer="delegationForm.valid_to">
                                @error('delegationForm.valid_to')
                                    <span class="text-danger small d-block">{{ $message }}</span>
                                @enderror
                            </div>
                            <div class="col-12">
                                <label class="form-label">Motivo</label>
                                <textarea rows="2" class="form-control" wire:model.defer="delegationForm.reason"
                                    placeholder="Ex.: Ferias de 10 a 30/01"></textarea>
                                @error('delegationForm.reason')
                                    <span class="text-danger small d-block">{{ $message }}</span>
                                @enderror
                            </div>
                            <div class="col-12 text-end">
                                <button type="submit" class="btn btn-success" wire:loading.attr="disabled">
                                    Delegar acesso
                                </button>
                            </div>
                        </form>

                        <hr>

                        <div class="table-responsive">
                            <table class="table table-sm align-middle">
                                <thead>
                                    <tr>
                                        <th>Delegado</th>
                                        <th>Periodo</th>
                                        <th>Motivo</th>
                                        <th class="text-end">Acoes</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($activeDelegations as $delegation)
                                        <tr>
                                            <td>
                                                <div class="fw-semibold">{{ $delegation->delegate->name }}</div>
                                                <small class="text-muted">{{ $delegation->delegate->email }}</small>
                                            </td>
                                            <td>
                                                <div>{{ $delegation->valid_from?->format('d/m/Y H:i') }}</div>
                                                <div class="text-muted small">
                                                    Ate {{ $delegation->valid_to?->format('d/m/Y H:i') ?? 'indeterminado' }}
                                                </div>
                                            </td>
                                            <td>{{ $delegation->reason ?? '-' }}</td>
                                            <td class="text-end">
                                                <button type="button" class="btn btn-outline-danger btn-sm"
                                                    wire:click="revokeDelegation('{{ $delegation->id }}')"
                                                    wire:loading.attr="disabled">
                                                    Revogar
                                                </button>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" class="text-center text-muted">Nenhuma delegacao ativa.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <span class="fw-semibold">Alterar senha</span>
                    </div>
                    <div class="card-body">
                        <form wire:submit.prevent="updatePassword" class="row g-3">
                            <div class="col-12">
                                <label class="form-label">Senha atual</label>
                                <input type="password" class="form-control"
                                    wire:model.defer="passwordForm.current_password">
                                @error('passwordForm.current_password')
                                    <span class="text-danger small">{{ $message }}</span>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Nova senha</label>
                                <input type="password" class="form-control" wire:model.defer="passwordForm.password">
                                @error('passwordForm.password')
                                    <span class="text-danger small">{{ $message }}</span>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Confirmar nova senha</label>
                                <input type="password" class="form-control"
                                    wire:model.defer="passwordForm.password_confirmation">
                                @error('passwordForm.password_confirmation')
                                    <span class="text-danger small">{{ $message }}</span>
                                @enderror
                            </div>
                            <div class="col-12 text-end">
                                <button type="submit" class="btn btn-warning" wire:loading.attr="disabled">
                                    Atualizar senha
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>
