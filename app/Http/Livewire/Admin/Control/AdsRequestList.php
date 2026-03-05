<?php

namespace App\Http\Livewire\Admin\Control;

use App\Enum\AdsRequestStatus;
use App\Models\AdsRequest;
use Livewire\Component;
use Livewire\WithPagination;

class AdsRequestList extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public int $perPage = 100;
    public ?string $search = null;
    public string $statusFilter = 'all';

    protected $queryString = [
        'search' => ['except' => '', 'as' => 'buscar'],
        'page' => ['except' => 1, 'as' => 'p'],
        'perPage' => ['except' => 100, 'as' => 'pp'],
        'statusFilter' => ['except' => 'all', 'as' => 'status'],
    ];

    protected $listeners = [
        'refresh_list' => '$refresh',
    ];

    public function updating($name): void
    {
        if ($name !== 'page') {
            $this->resetPage();
        }
    }

    public function getListsProperty()
    {
        $search = trim((string) $this->search);

        return AdsRequest::query()
            ->with(['note:id,note', 'company:id,name', 'requestedBy:id,name,email'])
            ->when($this->statusFilter !== 'all', fn ($q) => $q->where('status', $this->statusFilter))
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('id', 'like', "%{$search}%")
                        ->orWhere('status', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%")
                        ->orWhereHas('note', fn ($qq) => $qq->where('note', 'like', "%{$search}%"))
                        ->orWhereHas('company', fn ($qq) => $qq->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('requestedBy', fn ($qq) => $qq->where('name', 'like', "%{$search}%"));
                });
            })
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate($this->perPage);
    }

    public function getStatusOptionsProperty(): array
    {
        return array_map(fn ($case) => $case->value, AdsRequestStatus::cases());
    }

    public function render()
    {
        return view('livewire.admin.control.ads-request-list', [
            'lists' => $this->lists,
            'statusOptions' => $this->statusOptions,
        ]);
    }
}

