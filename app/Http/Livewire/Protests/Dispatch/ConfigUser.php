<?php

namespace App\Http\Livewire\Protests\Dispatch;

use App\Models\ProtestUser;
use App\Models\ProtestUserTrigger;
use App\Models\User;
use Livewire\Component;

class ConfigUser extends Component
{
    // Mantive $protest se você quiser reusar depois; aqui é global.
    public $search = '';
    public $bulkInput = ''; // texto de busca/inclusão em massa
    public $selectedProtestUserId = null;

    protected $listeners = [
        'refreshTriggers' => '$refresh',
    ];

    /* --------- Listas --------- */
    public function getUsersProperty()
    {
        $search = trim((string) $this->search);

        // Suporta vários termos: espaço, vírgula, ponto-e-vírgula e quebra de linha
        $terms = collect(preg_split('/[\s,;\n\r]+/', $search, -1, PREG_SPLIT_NO_EMPTY))
            ->filter()
            ->unique()
            ->take(20);

        return User::query()
            ->when($terms->isNotEmpty(), function ($q) use ($terms) {
                $q->where(function ($s) use ($terms) {
                    foreach ($terms as $t) {
                        $s->orWhere('name', 'like', "%{$t}%")
                          ->orWhere('email', 'like', "%{$t}%");
                    }
                });
            }, function ($q) {
                // sem termos: lista padrão
                $q->orderBy('name');
            })
            ->orderBy('name')
            ->limit(200)
            ->get(['id','name','email']); // id = UUID
    }

    public function getProtestUsersProperty()
    {
        // Global (sem protest_id)
        return ProtestUser::with('user')->orderBy('id', 'asc')->get();
    }

    public function getSelectedChainProperty()
    {
        if (!$this->selectedProtestUserId) {
            return collect();
        }

        return ProtestUserTrigger::with('user')
            ->where('protest_user_id', $this->selectedProtestUserId)
            ->orderBy('id', 'asc')
            ->get();
    }

    /* --------- UI actions --------- */
    public function selectProtestUser($protestUserId)
    {
        $this->selectedProtestUserId = (int) $protestUserId;
    }

    public function setDefault($protestUserId)
    {
        $pu = ProtestUser::find($protestUserId);
        if ($pu) {
            $pu->default = ! $pu->default;
            $pu->save();
            $this->emitSelf('$refresh');
        }
    }

    /* --------- Adição unitária (back-compat) --------- */
    public function addTriggerUser($userId)
    {
        $this->addTriggerUsers([$userId]);
    }

    public function addChainUser($userId, $selectedProtestUserId = null)
    {
        $this->addChainUsers([$userId], $selectedProtestUserId);
    }

    /* --------- Adição em massa --------- */
    public function addTriggerUsers(array $userIds)
    {
        $userIds = collect($userIds)->filter()->unique();

        foreach ($userIds as $uid) {
            ProtestUser::firstOrCreate(['user_id' => $uid]);
        }

        $this->emitSelf('$refresh');
    }

    public function addChainUsers(array $userIds, $selectedProtestUserId = null)
    {
        if ($selectedProtestUserId) {
            $this->selectedProtestUserId = (int) $selectedProtestUserId;
        }
        if (!$this->selectedProtestUserId) {
            return;
        }

        $userIds = collect($userIds)->filter()->unique();
        foreach ($userIds as $uid) {
            ProtestUserTrigger::firstOrCreate([
                'protest_user_id' => $this->selectedProtestUserId,
                'user_id'         => $uid,
            ]);
        }

        $this->emitSelf('$refresh');
    }

    /* --------- Bulk (colar emails/uuids) --------- */
    public function bulkAddToTrigger()
    {
        $ids = $this->parseBulkUserIds($this->bulkInput);
        if ($ids) {
            $this->addTriggerUsers($ids);
        }
        $this->bulkInput = '';
    }

    public function bulkAddToChain()
    {
        if (!$this->selectedProtestUserId) {
            return;
        }

        $ids = $this->parseBulkUserIds($this->bulkInput);
        if ($ids) {
            $this->addChainUsers($ids, $this->selectedProtestUserId);
        }
        $this->bulkInput = '';
    }

    protected function parseBulkUserIds(?string $text): array
    {
        $text = trim((string) $text);
        if ($text === '') {
            return [];
        }

        $tokens = collect(preg_split('/[\s,;\n\r]+/', $text, -1, PREG_SPLIT_NO_EMPTY))
            ->map(fn ($t) => trim($t))
            ->filter()
            ->unique();

        // Separa emails e UUIDs
        $emails = $tokens->filter(fn ($t) => str_contains($t, '@'));
        $uuids  = $tokens->filter(function ($t) {
            // UUID v4 (relaxa um pouco a validação)
            return preg_match('/^[0-9a-fA-F-]{32,36}$/', $t);
        });

        if ($emails->isEmpty() && $uuids->isEmpty()) {
            return [];
        }

        return User::query()
            ->when($emails->isNotEmpty(), fn ($q) => $q->orWhereIn('email', $emails))
            ->when($uuids->isNotEmpty(), fn ($q) => $q->orWhereIn('id', $uuids))
            ->pluck('id')
            ->unique()
            ->values()
            ->all();
    }

    public function removeTriggerUser($protestUserId)
    {
        ProtestUser::whereKey($protestUserId)->delete(); // triggers somem por cascade
        if ((int)$this->selectedProtestUserId === (int)$protestUserId) {
            $this->selectedProtestUserId = null;
        }
        $this->emitSelf('$refresh');
    }

    public function removeChainUser($triggerId)
    {
        ProtestUserTrigger::whereKey($triggerId)->delete();
        $this->emitSelf('$refresh');
    }

    public function render()
    {
        return view('livewire.protests.dispatch.config-user', [
            'users'         => $this->users,
            'protestUsers'  => $this->protestUsers,
            'selectedChain' => $this->selectedChain,
        ]);
    }
}
