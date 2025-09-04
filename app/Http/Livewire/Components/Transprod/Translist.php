<?php

namespace App\Http\Livewire\Components\Transprod;

use App\Models\{Notetimeline, Notify, Prodtransfer, Production, Service, User};
use App\Notifications\SystemNotification;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class Translist extends Component
{
    public $service;

    public $transfer_prod;

    protected $listeners = [
        'refresh_translist'   => '$refresh',
        'confirm_prod_accept' => 'accept',
        'confirm_prod_reject' => 'reject',
    ];

    public function mount(Service $service)
    {
        $this->service = $service;

    }

    public function fixTransfer()
    {
        $transfers = Prodtransfer::where('status', 19)
            ->where(function ($q) {
                $q->whereRelation('Production', 'completed', true);
            })->orWhere(function ($q) {
                $q->doesntHave('Production');
            })
            ->get();

        if ($transfers) {
            $transfers->each(function ($transfer) {
                $transfer->update([
                    'status' => 22,
                    'read_to' => true,
                    'read_from' => true,
                ]);
            });
        }
    }

    public function getTransferProperty()
    {
        return Prodtransfer::Where('service_id', $this->service->uuid)->where(function ($q) {
            return $q->where('from', Auth()->User()->id)
                ->orWhere('to', Auth()->User()->id);
        })
            ->whereRelation('Production', 'completed', false)
            ->orderBy('updated_at', 'DESC')
            ->with('To', 'From', 'Production.Note')
            ->get();
    }

    public function to_accept(Prodtransfer $transfer)
    {
        $this->transfer_prod = $transfer->load('Production.Note', 'From');

        if ($this->transfer_prod) {
            $this->dispatchBrowserEvent('alertar', [
                'title'         => 'TRANSFERÊNCIA DE PRODUÇÃO',
                'msg'           => "Você deseja aceitar a produção iniciada por {$this->transfer_prod->From->name} na NOTA/OV {$this->transfer_prod->Production->Note->note}?",
                'icon'          => 'question',
                'btnOktxt'      => 'Sim, Aceito!',
                'btnCanceltxt'  => 'Não, Cancele',
                'action'        => 'confirm_prod_accept',
                'cancel_titulo' => 'Cancelado!',
                'cancel_msg'    => 'Ação Cancelada.',

            ]);
        }
    }

    public function accept()
    {
        $production = Production::find($this->transfer_prod->production_id);
        $url        = route('services.accompany', ['service' => $production->service_id]);

        if ($production) {
            try {
                $production->update([
                    'user_id'     => $this->transfer_prod->to,
                    'company_id'  => (User::with('Employee.Contract')->find($this->transfer_prod->to))->Employee->Contract->company_id,
                    'att_at'      => date('Y-m-d H:i:s'),
                    'status'      => 2,
                    'transferred' => true,
                    'block'       => false,
                ]);

                $this->transfer_prod->update([
                    'read_to'   => true,
                    'read_from' => true,
                    'status'    => 21,
                ]);

                Notetimeline::create([
                    'note_id'    => $production->note_id,
                    'service_id' => $production->service_id,
                    'user_id'    => $production->user_id,
                    'info'       => 'Usuário ' . Auth()->User()->name . ' aceitou a transferência de produção',
                    'status'     => 21,

                ]);

                // Notify::create([
                //     'user_id' => $this->transfer_prod->from,
                //     'title'   => 'TRANSFERÊNCIA PRODUÇÃO',
                //     'info'    => 'O usuário aceitou sua solicitação para ' . $production->Note->note,
                //     'status'  => 1,
                //     'link'    => $url,
                // ]);


                $user = User::find($this->transfer_prod->from);
                $userName = auth()->User()->name;
                $link = route('services.accompany', ['service' => $this->transfer_prod->service_id]);

                if ($user) {

                    $user->notify(new SystemNotification(
                        titulo: 'Transferência Aceita',
                        mensagem: "A transferência de produção <strong>{$production->Note->note}</strong> em <strong>{$production->Service->service}</strong> foi aceita por <strong>{$userName}</strong>.",
                        link: $link, // ou outra rota que você tiver
                        status: 1,
                        extras: []
                    ));

                }

                // $user = User::find($this->transfer_prod->from);
                // $userName = auth()->User()->name;
                // if ($user) {
                //     $user->notify(new SystemNotification(
                //         'Transferência Aceita',
                //         "A transferência de produção <strong>{$production->Note->note}</strong> em <strong>{$production->Service->service}</strong> foi aceita por <strong>{$userName}</strong>.",
                //         '',
                //         1,
                //         []
                //     ));
                // }

                $this->dispatchBrowserEvent('swal', [
                    'position' => 'center',
                    'icon'     => 'success',
                    'title'    => 'Transferência Concluida com sucesso',
                    'timer'    => 2500,
                ]);

            } catch (\Throwable $th) {
                $this->dispatchBrowserEvent('swal', [
                    'position' => 'center',
                    'icon'     => 'error',
                    'title'    => 'OOOPS, Não foi possível aceitar a transferência',
                    'timer'    => 2500,
                ]);
            }
        }
    }

    public function to_rejectt(Prodtransfer $transfer)
    {
        $this->transfer_prod = $transfer->load('Production.Note', 'From');

        if ($this->transfer_prod) {
            $this->dispatchBrowserEvent('alertar', [
                'title'         => 'TRANSFERÊNCIA DE PRODUÇÃO',
                'msg'           => "Você deseja rejeitar a produção iniciada por {$this->transfer_prod->From->name} na NOTA/OV {$this->transfer_prod->Production->Note->note}?",
                'icon'          => 'question',
                'btnOktxt'      => 'Sim, Rejeite!',
                'btnCanceltxt'  => 'Não, Cancele',
                'action'        => 'confirm_prod_reject',
                'cancel_titulo' => 'Cancelado!',
                'cancel_msg'    => 'Ação Cancelada.',

            ]);
        }
    }

    public function reject()
    {
        $production = Production::with('Note')->find($this->transfer_prod->production_id);
        $url        = route('services.accompany', ['service' => $production->service_id]);

        if ($production) {

            DB::beginTransaction();

            try {
                $production->update([

                    'status'      => 2,
                    'transferred' => false,
                    'block'       => false,
                    'block_wpa'   => false,
                ]);

                $this->transfer_prod->update([
                    'read_to'   => true,
                    'read_from' => false,
                    'status'    => 20,
                ]);

                Notetimeline::create([
                    'note_id'    => $production->note_id,
                    'service_id' => $production->service_id,
                    'user_id'    => $production->user_id,
                    'info'       => 'Usuário ' . Auth()->User()->name . ' rejeitou a transferência de produção',
                    'status'     => 20,

                ]);

                // Notify::create([
                //     'user_id' => $this->transfer_prod->from,
                //     'title'   => 'TRANSFERÊNCIA PRODUÇÃO',
                //     'info'    => 'O usuário rejeitou sua solicitação para ' . $production->Note->note,
                //     'status'  => 0,
                //     'link'    => $url,
                // ]);

                $user = User::find($this->transfer_prod->from);
                $userName = auth()->User()->name;


                $link = route('services.accompany', ['service' => $this->transfer_prod->service_id]);

                if ($user) {

                    $user->notify(new SystemNotification(
                        titulo: 'Transferência rejeitada',
                        mensagem: "A transferência de produção <strong>{$production->Note->note}</strong> em <strong>{$production->Service->service}</strong> foi rejeitada por <strong>{$userName}</strong>.",
                        link: $link, // ou outra rota que você tiver
                        status: 0,
                        extras: []
                    ));

                }

                DB::commit();

                $this->dispatchBrowserEvent('swal', [
                    'position' => 'center',
                    'icon'     => 'success',
                    'title'    => 'Transferência Rejeitada com sucesso',
                    'timer'    => 2500,
                ]);

            } catch (\Throwable $th) {
                DB::rollBack();
                $this->dispatchBrowserEvent('swal', [
                    'position' => 'center',
                    'icon'     => 'error',
                    'title'    => 'OOOPS, Não foi possível rejeitar a transferência',
                    'timer'    => 2500,
                ]);
            }
        }
    }

    public function to_ok(Prodtransfer $transfer)
    {
        try {
            $transfer->update(['read_from' => true]);

            $this->emit('refresh_translist');

        } catch (\Throwable $th) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'error',
                'title'    => 'OOOPS, Ocorreu alguma falha...',
                'timer'    => 2500,
            ]);
        }
    }

    public function render()
    {
        $this->fixTransfer();

        return view('livewire.components.transprod.translist', [
            'lists' => $this->transfer,
        ]);
    }
}
