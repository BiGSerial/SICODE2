<?php

namespace App\Http\Livewire\Components\Transprod;

use App\Models\{Company, Notify, Prodtransfer, Production, User};
use App\Notifications\SystemNotification;
use App\Support\SicodeRules;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class Transprod extends Component
{
    public $production;

    public $search;

    public $company_transfer_id;

    public $transfer_view = false;

    public $user_transfer_id;

    public $user_transfer_info;

    protected $listeners = [
        'transfer_production' => 'transfer_production',
    ];

    public function transfer_production(Production $production)
    {
        $this->production = $production->load('User', 'Note', 'Service');

        if (in_array((int) $this->production->status, [
            Production::STATUS_IN_PROJECT_REVIEW,
            Production::STATUS_REJECTED_PROJECT_REVIEW,
            Production::STATUS_RELEASED_TO_FINISH,
        ], true)) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'warning',
                'title'    => 'TRANSFERÊNCIA BLOQUEADA',
                'html'     => 'Produção em tratativa de análise de projeto. Conclua o fluxo antes de transferir.',
                'timer'    => 4000,
            ]);

            return;
        }

        if ($this->production) {
            $this->transfer_view = true;
        }

        $this->dispatchBrowserEvent('showModal', [
            'id' => 'transfer_modal',
        ]);
    }

    public function getUserlistProperty()
    {
        return $this->eligibleUsersQuery()
            ->when($this->company_transfer_id, fn ($q, $companyId) => $this->applyCompanyFilter($q, $companyId))
            ->when($this->search, function ($q, $s) {
                return $q->where('name', 'like', '%' . $s . '%');
            })
            ->orderBy('name', 'ASC')->get();
    }

    public function getCompanylistProperty()
    {
        $companyIds = $this->eligibleUsersQuery()
            ->get()
            ->flatMap(fn ($user) => $this->companyIdsForUser($user))
            ->filter()
            ->unique()
            ->values();

        return Company::whereIn('id', $companyIds)
            ->orderBy('name', 'ASC')
            ->get();
    }

    public function updatedCompanyTransferId()
    {
        $this->user_transfer_id = null;
    }

    private function eligibleUsersQuery()
    {
        return User::with('Company', 'Employee.Contract.company', 'Companies')
            ->whereRelation('ToServices', function ($q) {
                $q->when($this->production, function ($q) {
                    return $q->where('service_id', $this->production->service_id)
                        ->where('service', true);
                }, fn ($q) => $q->whereRaw('0 = 1'));
            })
            ->where('id', '!=', auth()->id())
            ->when(auth()->user()?->contract, function ($q) {
                $companyIds = SicodeRules::visibleCompanyIdsFor(auth()->user());

                return count($companyIds)
                    ? $q->where(function ($users) use ($companyIds) {
                        $users->whereIn('company_id', $companyIds)
                            ->orWhereHas('Employee.Contract', fn ($contract) => $contract->whereIn('company_id', $companyIds));
                    })
                    : $q->whereRaw('0 = 1');
            });
    }

    private function applyCompanyFilter($query, string $companyId)
    {
        return $query->where(function ($users) use ($companyId) {
            $users->where('company_id', $companyId)
                ->orWhereHas('Employee.Contract', fn ($contract) => $contract->where('company_id', $companyId));
        });
    }

    private function companyIdsForUser(User $user)
    {
        return collect([$user->Employee?->Contract?->company_id ?: $user->company_id])
            ->filter()
            ->unique()
            ->values();
    }

    public function transfer_prod()
    {
        if ($this->production && in_array((int) $this->production->status, [
            Production::STATUS_IN_PROJECT_REVIEW,
            Production::STATUS_REJECTED_PROJECT_REVIEW,
            Production::STATUS_RELEASED_TO_FINISH,
        ], true)) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'warning',
                'title'    => 'TRANSFERÊNCIA BLOQUEADA',
                'html'     => 'Produção em tratativa de análise de projeto. Conclua o fluxo antes de transferir.',
                'timer'    => 4000,
            ]);

            return;
        }

        $url = route('services.accompany', ['service' => $this->production->service_id]);

        // Check existence user to transfer
        if (!$this->user_transfer_id) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'warning',
                'title'    => 'SEM USUÁRIO SELECIONADO PARA TRANSFERIR!.',
                'timer'    => 2500,
            ]);

            return;
        }

        if (!strlen(trim($this->user_transfer_info)) || strlen(trim($this->user_transfer_info)) <= 2) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'warning',
                'title'    => 'INFORMAÇÃO OBRIGATÓRIA.',
                'html'     => '<strong> (MOTIVO) </strong> A informação do motivo é obrigatório. Seja Claro e Objetivo.',
                'timer'    => 10000,
            ]);

            return;
        }

        $user = $this->eligibleUsersQuery()
            ->when($this->company_transfer_id, fn ($q, $companyId) => $this->applyCompanyFilter($q, $companyId))
            ->where('id', $this->user_transfer_id)
            ->first();

        if (!$user) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'warning',
                'title'    => 'Usuário não habilitado para esta atividade.',
                'timer'    => 3000,
            ]);

            return;
        }

        DB::beginTransaction();

        try {
            $transfer = Prodtransfer::create([
                'production_id' => $this->production->id,
                'service_id'    => $this->production->service_id,
                'from'          => Auth()->User()->id,
                'to'            => $this->user_transfer_id,
                'info'          => $this->user_transfer_info,
                'read_from'     => true,
                'read_to'       => false,
                'status'        => 19,
            ]);

            $this->production->update([
                'block'  => true,
                'status' => 19,
            ]);

            // Notify::create([
            //     'user_id' => $transfer->to,
            //     'title'   => 'TRANSFERÊNCIA PRODUÇÃO',
            //     'info'    => 'O usuário ' . Auth()->User()->name . ' deseja transferir para você a nota/ov ' . $this->production->Note->note . ' em ' . $this->production->Service->service,
            //     'status'  => 3,
            //     'link'    => $url,
            // ]);

            // UUID do destinatário

            $note       = $this->production->Note->note;
            $dispatcher = $this->production->Dispatcher;
            $link       = route('services.main', ['service' => $this->production->service_id]);

            if ($user) {
                $user->notify(new SystemNotification(
                    titulo: 'NOVA INTENÇÃO DE TRANSFERÊNCIA DE PRODUÇÃO',
                    mensagem: "Você recebeu uma nova transferência de produção de <strong>" . Auth()->User()->name . " em " . $this->production->Service->service . "</strong> para a obra <strong>" . $note . "</strong>.",
                    link: $link,
                    status: 3,
                    extras: []
                ));
            } else {
                throw new \Exception('Usuário não encontrado para transferência');
            }

            $link = null;

            if ($dispatcher) {
                $dispatcher->notify(new SystemNotification(
                    titulo: 'NOVA INTENÇÃO DE TRANSFERÊNCIA DE PRODUÇÃO',
                    mensagem: "Ha uma nova transferência de produção entre <strong>" . Auth()->User()->name . "</strong> e <strong>" . $user?->name . "</strong> para a obra <strong>" . $note . "</strong>.",
                    link: $link,
                    status: 3,
                    extras: []
                ));
            }

            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'success',
                'title'    => 'Solicitação de Transferência Enviada com Sucesso.',
                'timer'    => 2500,
            ]);

            DB::commit();

            $this->close();

        } catch (\Throwable $th) {
            DB::rollBack();

            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'error',
                'title'    => 'OOOPS, Algo de errado aconteceu....' . $th->getMessage(),
                'timer'    => 8000,
            ]);
        }
    }

    public function close()
    {

        $this->production          = null;
        $this->search              = '';
        $this->company_transfer_id = null;
        $this->transfer_view       = false;
        $this->user_transfer_id    = null;
        $this->user_transfer_info  = null;

        $this->dispatchBrowserEvent('hideModal');
        $this->emit('refresh_accomany');
    }

    public function render()
    {
        return view('livewire.components.transprod.transprod', [
            'user_list'    => $this->Userlist,
            'company_list' => $this->Companylist,
        ]);
    }
}
