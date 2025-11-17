<?php

namespace App\Http\Livewire\Home;

use App\Models\User;
use App\Models\UserDelegation;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithFileUploads;

class Profile extends Component
{
    use WithFileUploads;

    public User $user;

    public string $avatarMode = 'upload';
    public $avatarUpload;
    public string $avatarSeed = '';

    public array $delegationForm = [
        'delegate_id' => '',
        'valid_from'  => '',
        'valid_to'    => '',
        'reason'      => '',
    ];

    public array $passwordForm = [
        'current_password'      => '',
        'password'              => '',
        'password_confirmation' => '',
    ];

    public $availableDelegates = [];
    public $activeDelegations = [];
    public $delegationsAsDelegate = [];

    protected function rules(): array
    {
        return [
            'user.name'         => ['required', 'string', 'max:255'],
            'user.email'        => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->user->id)],
            'user.Registration' => ['nullable', 'string', 'max:20'],
        ];
    }

    public function mount(User $user): void
    {
        $this->user = $user ?? abort(403);

        $this->avatarMode = $this->avatarIsDiceBear($this->user->avatar) ? 'dicebear' : 'upload';
        $this->avatarSeed = $this->avatarMode === 'dicebear' ? $this->avatarSeedFromValue($this->user->avatar) : '';
        $this->delegationForm['valid_from'] = now()->format('Y-m-d\TH:i');

        $this->availableDelegates = User::query()
            ->orderBy('name')
            ->whereKeyNot($this->user->getKey())
            ->get(['id', 'name', 'email']);

        $this->refreshDelegations();
    }

    protected function avatarIsDiceBear(?string $value): bool
    {
        return is_string($value) && str_starts_with($value, 'dicebear:');
    }

    protected function avatarSeedFromValue(?string $value): string
    {
        return $this->avatarIsDiceBear($value)
            ? trim(substr($value, strlen('dicebear:')))
            : '';
    }

    public function refreshDelegations(): void
    {
        $this->activeDelegations = $this->user->delegationsGiven()
            ->active()
            ->with('delegate')
            ->orderByDesc('valid_from')
            ->get();

        $this->delegationsAsDelegate = $this->user->delegationsReceived()
            ->active()
            ->with('principal')
            ->orderByDesc('valid_from')
            ->get();
    }

    public function saveProfile(): void
    {
        $this->validate();

        $this->user->save();

        session()->flash('profileMessage', 'Dados atualizados com sucesso.');
    }

    public function saveAvatar(): void
    {
        if ($this->avatarMode === 'dicebear') {
            $this->avatarSeed = trim($this->avatarSeed);

            $this->validate(
                [
                    'avatarSeed' => ['required', 'string', 'max:100'],
                ],
                [
                    'avatarSeed.required' => 'Informe um seed para gerar o avatar.',
                ],
                [
                    'avatarSeed' => 'seed do avatar',
                ]
            );

            $this->user->avatar = 'dicebear:' . trim($this->avatarSeed);
            $this->user->save();
        } else {
            $this->validate([
                'avatarUpload' => ['required', 'image', 'max:2048'],
            ]);

            $path = $this->avatarUpload->store('avatars', 'public');

            if ($this->user->avatar && !$this->avatarIsDiceBear($this->user->avatar)) {
                Storage::disk('public')->delete($this->user->avatar);
            }

            $this->user->avatar = $path;
            $this->user->save();

            $this->avatarUpload = null;
        }

        $this->avatarMode = $this->avatarIsDiceBear($this->user->avatar) ? 'dicebear' : 'upload';
        $this->avatarSeed  = $this->avatarMode === 'dicebear' ? $this->avatarSeedFromValue($this->user->avatar) : '';

        session()->flash('profileMessage', 'Avatar atualizado com sucesso.');
    }

    public function updatedAvatarMode(): void
    {
        if ($this->avatarMode === 'dicebear') {
            $this->avatarUpload = null;
        } else {
            $this->avatarSeed = '';
        }
    }

    public function saveDelegation(): void
    {
        $data = $this->validate([
            'delegationForm.delegate_id' => ['required', 'exists:users,id', Rule::notIn([$this->user->id])],
            'delegationForm.valid_from'  => ['required', 'date'],
            'delegationForm.valid_to'    => ['nullable', 'date', 'after:delegationForm.valid_from'],
            'delegationForm.reason'      => ['nullable', 'string', 'max:500'],
        ])['delegationForm'];

        $from = Carbon::parse($data['valid_from']);
        $to   = $data['valid_to'] ? Carbon::parse($data['valid_to']) : null;

        $alreadyActive = UserDelegation::forPrincipal($this->user->id)
            ->forDelegate($data['delegate_id'])
            ->active()
            ->exists();

        if ($alreadyActive) {
            $this->addError('delegationForm.delegate_id', 'Ja existe uma delegacao ativa para este usuario.');
            return;
        }

        UserDelegation::create([
            'principal_id' => $this->user->id,
            'delegate_id'  => $data['delegate_id'],
            'valid_from'   => $from,
            'valid_to'     => $to,
            'reason'       => $data['reason'],
        ]);

        $this->delegationForm = [
            'delegate_id' => '',
            'valid_from'  => now()->format('Y-m-d\TH:i'),
            'valid_to'    => '',
            'reason'      => '',
        ];

        $this->refreshDelegations();

        session()->flash('profileMessage', 'Delegacao criada com sucesso.');
    }

    public function revokeDelegation(string $delegationId): void
    {
        $delegation = $this->user->delegationsGiven()
            ->whereKey($delegationId)
            ->first();

        if (!$delegation) {
            $this->addError('delegationForm.delegate_id', 'Delegacao nao encontrada.');
            return;
        }

        $delegation->end();

        $this->refreshDelegations();

        session()->flash('profileMessage', 'Delegacao revogada.');
    }

    public function updatePassword(): void
    {
        $data = $this->validate([
            'passwordForm.current_password'      => ['required'],
            'passwordForm.password'              => ['required', 'string', 'min:8', 'confirmed'],
            'passwordForm.password_confirmation' => ['required'],
        ])['passwordForm'];

        if (!Hash::check($data['current_password'], $this->user->getAuthPassword())) {
            $this->addError('passwordForm.current_password', 'Senha atual incorreta.');
            return;
        }

        $this->user->password = $data['password'];
        $this->user->save();

        $this->passwordForm = [
            'current_password'      => '',
            'password'              => '',
            'password_confirmation' => '',
        ];

        session()->flash('profileMessage', 'Senha atualizada com sucesso.');
    }

    public function render()
    {
        return view('livewire.home.profile');
    }
}
