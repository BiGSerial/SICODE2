<?php

namespace App\Http\Livewire\Protests\Dispatch;

use App\Models\ProtestJob;
use App\Models\User;
use Livewire\Component;
use Livewire\WithPagination;

class Monitoring extends Component
{
    use WithPagination;

    public int $perPage = 50;
    public $deep = '';
    public $userViewer = null;

    /** Sempre arrays para o Livewire (públicas) */
    public array $deepList = [];
    public array $userViewerList = [];

    protected $queryString = [
        'perPage'    => ['except' => 50],
        'deep'       => ['except' => ''],
        'userViewer' => ['except' => null],
    ];

    public function mount()
    {
        // depthsGlobal retorna Collection; converta p/ array simples de ints
        $this->deepList = User::depthsGlobal(true)->toArray();
        // opcional: ordenar, se quiser
        sort($this->deepList);
    }

    public function updatedDeep($value)
    {
        // reset quando limpar nível
        if ($value === '' || $value === null) {
            $this->userViewerList = [];
            $this->userViewer = null;
            $this->resetPage();
            return;
        }

        // Converta a query p/ array id=>name
        $this->userViewerList = User::usersAtDepthGlobal((int)$this->deep)
            ->select('users.id', 'users.name')
            ->orderBy('users.name')
            ->pluck('users.name', 'users.id')   // [id => name]
            ->toArray();

        // ao mudar filtro, volte para a página 1
        $this->resetPage();
    }

    public function baseQuery()
    {
        return ProtestJob::query()
            ->when($this->userViewer, function ($q) {
                $user = User::find($this->userViewer);
                if (!$user) {
                    return;
                }

                // pegue os ids de descendentes (incluindo o próprio) como array
                $ownerIds = $user->descendantsQuery(true)->pluck('users.id')->toArray();

                // agrupe where/or para não “soltar” o orWhereNull
                $q->where(function ($qq) use ($ownerIds) {
                    $qq->whereIn('owner_id', $ownerIds)
                       ->orWhereNull('owner_id');
                });
            })
            ->orderBy('priority', 'desc')
            ->with(['MedProtest', 'Protest', 'Owner:id,name', 'Creator:id,name', 'Closer:id,name']);
    }

    public function getListsProperty()
    {
        return $this->baseQuery()->paginate($this->perPage);
    }

    /** Botões (se quiser usar) */
    public function applyFilters()
    {
        $this->resetPage();
    }

    public function cleanFilters()
    {
        $this->deep = '';
        $this->userViewer = null;
        $this->userViewerList = [];
        $this->resetPage();
    }

    public function render()
    {
        return view('livewire.protests.dispatch.monitoring', [
            'lists' => $this->lists,
            // opcional: pode nem precisar passar, pois a prop é pública
            'userViewerLists' => $this->userViewerList,
        ]);
    }
}
