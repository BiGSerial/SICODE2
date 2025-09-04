<?php

namespace App\Http\Livewire\Home\Tools;

use App\Models\Prodtransfer;
use App\Models\User;
use App\Notifications\SystemNotification;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class TransferNotes extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';
    private $perPage = 5;

    public $idCount;


    public function mount(string $idCount)
    {
        $this->idCount = $idCount;


    }

    public function toUpdateIdCount()
    {
        $this->dispatchBrowserEvent('transferNotesUpdated', [
            'count' => $this->transferNotes->count(),
        ]);
    }

    public function getTransferNotesProperty()
    {
        return Prodtransfer::where('to', auth()->id())
            ->where('read_to', false)
            ->where('status', 19)
            ->with(['Service', 'Production', 'From.Company:id,name', 'To'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function acceptTransfer(Prodtransfer $transfer)
    {

        if ($transfer) {

            DB::beginTransaction();

            try {
                $transfer->update([
                'read_to' => true,
                'status' => 21,
                ]);

                $transfer->Production->update([
                    'user_id' => auth()->id(),
                    'company_id' => auth()->user()->company_id,
                    'nstats' => 2,
                    'block' => false,
                    'wpa_block' => false,
                    'att_by' => $transfer->from,
                    'att_at' => now(),
                    'transferred' => true,
                ]);



                $user = User::find($transfer->from);
                $userName = auth()->User()->name;
                $link = route('services.accompany', ['service' => $transfer->service_id]);

                if ($user) {

                    $user->notify(new SystemNotification(
                        titulo: 'Transferência Aceita',
                        mensagem: "A transferência de produção <strong>{$transfer->Production->Note->note}</strong> em <strong>{$transfer->Service->service}</strong> foi aceita por <strong>{$userName}</strong>.",
                        link: $link, // ou outra rota que você tiver
                        status: 1,
                        extras: []
                    ));

                }

                DB::commit();

                $this->toUpdateIdCount();

            } catch (\Throwable $th) {
                DB::rollBack();
            }
        }
    }

    public function rejectTransfer(Prodtransfer $transfer)
    {
        if ($transfer) {

            DB::beginTransaction();

            try {
                $transfer->update([
                    'read_to' => true,
                    'status' => 20,
                ]);

                $transfer->Production->update([
                    'nstats' => 2,
                    'block' => false,
                ]);

                DB::commit();

                $user = User::find($transfer->from);
                $userName = auth()->User()->name;
                $link = route('services.accompany', ['service' => $transfer->service_id]);

                if ($user) {

                    $user->notify(new SystemNotification(
                        titulo: 'Transferência Rejeitada',
                        mensagem: "A transferência de produção <strong>{$transfer->Production->Note->note}</strong> em <strong>{$transfer->Service->service}</strong> foi rejeitada por <strong>{$userName}</strong>.",
                        link: $link, // ou outra rota que você tiver
                        status: 0,
                        extras: []
                    ));

                }


                $this->toUpdateIdCount();

            } catch (\Throwable $th) {
                DB::rollBack();
            }
        }
    }

    public function render()
    {
        return view('livewire.home.tools.transfer-notes', [
            'transferNotes' => $this->transferNotes,
        ]);
    }
}
