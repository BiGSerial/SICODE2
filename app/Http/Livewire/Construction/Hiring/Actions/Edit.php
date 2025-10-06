<?php

namespace App\Http\Livewire\Construction\Hiring\Actions;

use App\Models\Company;
use App\Models\File;
use App\Models\User;
use App\Models\Viability;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use App\Services\Viability\LogRehiring;

class Edit extends Component
{
    /** Modo individual */
    public ?Viability $viability = null;

    /** Modo bulk */
    public array $ids = [];     // IDs selecionados
    public bool $isBulk = false;

    /** Selects */
    public $companies;
    public $users = [];

    /** Flags de edição */
    public bool $rehiring = false;
    public bool $newsend  = false;

    /** Selecionados no form */
    public $user_s;
    public $companyS; // camelCase para hook updatedCompanyS

    protected $listeners = [
        'edit_hiring'      => 'editHiring',      // individual
        'edit_hiring_bulk' => 'editHiringBulk',  // massa
        'alter_viability',
    ];

    public function mount()
    {
        $this->companies = Company::orderBy('name')->get();
        $this->users     = User::where('responsible', true)->select('id', 'name')->orderBy('name')->get();
    }

    public function updatedCompanyS($companyId)
    {
        $this->users = User::whereHas('Companies', fn ($q) => $q->where('companies.id', $companyId))
            ->where('users.responsible', true)
            ->select('id', 'name')
            ->orderBy('name')
            ->get();
    }

    public function recontratar()
    {
        $this->rehiring = !$this->rehiring;
    }

    public function editHiring(Viability $viability)
    {
        $this->resetForm();

        $this->isBulk    = false;
        $this->viability = $viability;

        if ($this->viability) {
            $this->user_s   = optional($this->viability->Engineer)->id ?: '';
            $this->companyS = optional($this->viability->Company)->id ?: '';
            $this->dispatchBrowserEvent('showModal', ['id' => 'modal_edit_hiring']);
        }
    }

    public function editHiringBulk(array $ids)
    {
        $this->resetForm();

        $this->isBulk = true;
        $this->ids    = array_values(array_unique(array_filter($ids)));

        $this->viability = null;

        $this->dispatchBrowserEvent('showModal', ['id' => 'modal_edit_hiring']);
    }

    public function toAlterViability()
    {
        if (empty($this->user_s) || empty($this->companyS)) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'warning',
                'title'    => 'É PRECISO DEFINIR EMPRESA E RESPONSÁVEL PARA ALTERAÇÃO',
                'timer'    => 5000,
            ]);
            return;
        }

        $oldUser    = $this->isBulk ? null : optional($this->viability?->Engineer)->name;
        $oldCompany = $this->isBulk ? null : optional($this->viability?->Company)->name;

        $newUser    = optional(User::find($this->user_s))->name ?? '---';
        $newCompany = optional(Company::find($this->companyS))->name ?? '---';

        $title = $this->isBulk
            ? "ALTERAR VIABILIDADE EM MASSA (" . count($this->ids) . " itens)"
            : "ALTERAR VIABILIDADE";

        $rowsCompany = $this->isBulk
            ? "<td class='text-center align-middle'>—</td><td class='text-center align-middle'> => </td><td class='text-center align-middle'>{$newCompany}</td>"
            : "<td class='text-center align-middle'>{$oldCompany}</td><td class='text-center align-middle'> => </td><td class='text-center align-middle'>{$newCompany}</td>";

        $rowsUser = $this->isBulk
            ? "<td class='text-center align-middle'>—</td><td class='text-center align-middle'> => </td><td class='text-center align-middle'>{$newUser}</td>"
            : "<td class='text-center align-middle'>{$oldUser}</td><td class='text-center align-middle'> => </td><td class='text-center align-middle'>{$newUser}</td>";

        $this->dispatchBrowserEvent('alertar', [
            'title' => $title,
            'msg'   => "
                <p>Deseja aplicar as alterações de destino da(s) viabilidade(s)?</p>
                <div class='card'>
                    <table class='table table-sm'>
                        <thead>
                            <th class='text-center align-middle'>Empresa Origem</th>
                            <th class='text-center align-middle'></th>
                            <th class='text-center align-middle'>Empresa Destino</th>
                        </thead>
                        <tbody><tr>{$rowsCompany}</tr></tbody>
                    </table>
                    <table class='table table-sm'>
                        <thead>
                            <th class='text-center align-middle'>Responsável Origem</th>
                            <th class='text-center align-middle'></th>
                            <th class='text-center align-middle'>Responsável Destino</th>
                        </thead>
                        <tbody><tr>{$rowsUser}</tr></tbody>
                    </table>
                </div>
            ",
            'icon'          => 'question',
            'btnOktxt'      => 'Sim, Envie!',
            'btnCanceltxt'  => 'Não, Cancele',
            'action'        => 'alter_viability',
            'cancel_titulo' => 'Cancelado!',
            'cancel_msg'    => 'Nenhuma obra teve a Viabilidade Alterada!',
        ]);
    }

    public function alter_viability()
    {
        if ($this->isBulk) {
            $this->alterBulk();
            return;
        }

        if (!$this->viability) {
            $this->toast('error', 'Viabilidade não carregada.');
            return;
        }

        // snapshot "antes"
        $before = $this->viability->fresh(); // garante sync

        if ($this->newsend) {
            DB::beginTransaction();
            try {
                $this->viability->update($this->payloadNewSend());
                $this->viability->days()->delete();

                // auditoria (dentro da transação)
                LogRehiring::handle($before, $this->viability->fresh(), [
                    'was_newsend'     => true,
                    'was_rehiring'    => (bool)$this->rehiring,
                    'new_engineer_id' => $this->user_s,
                    'new_company_id'  => $this->companyS,
                ]);

                DB::commit();
                $this->toast('success', 'Viabilidade reenviada como NOVA com sucesso!');
                $this->closeAll();
                $this->emitUp('refresh_list');
                $this->emitUp('clear_selection');
                return;
            } catch (\Throwable $th) {
                DB::rollBack();
                $this->toast('error', 'Falha ao reenviar a viabilidade.');
                return;
            }
        }

        if (!$this->viability->completed && !$this->rehiring) {
            DB::beginTransaction();
            try {
                $this->viability->update([
                    'engineer_id' => $this->user_s,
                    'company_id'  => $this->companyS,
                ]);

                // auditoria simples (sem new send)
                LogRehiring::handle($before, $this->viability->fresh(), [
                    'was_newsend'     => false,
                    'was_rehiring'    => (bool)$this->rehiring,
                    'new_engineer_id' => $this->user_s,
                    'new_company_id'  => $this->companyS,
                ]);

                DB::commit();
                $this->toast('success', 'Alterado com sucesso!');
                $this->closeAll();
                $this->emitUp('refresh_list');
                $this->emitUp('clear_selection');
            } catch (\Throwable $th) {
                DB::rollBack();
                $this->toast('error', 'OOOPS! Algo deu errado.');
            }
        }
    }

    protected function alterBulk(): void
    {
        if (empty($this->ids)) {
            $this->toast('warning', 'Nenhum item selecionado.');
            return;
        }

        DB::beginTransaction();
        try {
            $viabs = Viability::whereIn('id', $this->ids)->get();

            foreach ($viabs as $viab) {
                $before = $viab->fresh();

                if ($this->newsend) {
                    $viab->update($this->payloadNewSend());
                    $viab->days()->delete();

                    LogRehiring::handle($before, $viab->fresh(), [
                        'was_newsend'     => true,
                        'was_rehiring'    => (bool)$this->rehiring,
                        'new_engineer_id' => $this->user_s,
                        'new_company_id'  => $this->companyS,
                    ]);
                    continue;
                }

                if (!$viab->completed && !$this->rehiring) {
                    $viab->update([
                        'engineer_id' => $this->user_s,
                        'company_id'  => $this->companyS,
                    ]);

                    LogRehiring::handle($before, $viab->fresh(), [
                        'was_newsend'     => false,
                        'was_rehiring'    => (bool)$this->rehiring,
                        'new_engineer_id' => $this->user_s,
                        'new_company_id'  => $this->companyS,
                    ]);
                }
            }

            DB::commit();
            $msg = $this->newsend
                ? 'Viabilidades reenviadas como NOVAS com sucesso!'
                : 'Viabilidades alteradas com sucesso!';
            $this->toast('success', $msg);

            $this->closeAll();
            $this->emitUp('refresh_list');
            $this->emitUp('clear_selection');
        } catch (\Throwable $th) {
            DB::rollBack();
            $this->toast('error', 'Falha ao aplicar alterações em massa.'.$th->getMessage());
        }
    }

    protected function payloadNewSend(): array
    {
        return [
            'engineer_id'   => $this->user_s,
            'company_id'    => $this->companyS,
            'rehired'       => $this->rehiring,
            'sended_at'     => now(),
            'tacit'         => false,
            'approved'      => false,
            'rejected'      => false,
            'status'        => 1,
            'tacit_at'      => null,
            'completed_at'  => null,
            'replica'       => false,
            'treplica'      => false,
            'inActivity'    => false,
            'returned_at'   => null,
        ];
    }

    public function downloadFile($id)
    {
        if ($file = File::find($id)) {
            if (Storage::disk('local')->exists($file->path)) {
                return Storage::download($file->path, $file->file_name);
            }
        }
    }

    protected function toast(string $icon, string $title): void
    {
        $this->dispatchBrowserEvent('swal', [
            'position' => 'center',
            'icon'     => $icon,
            'title'    => $title,
            'timer'    => 5000,
        ]);
    }

    protected function closeAll(): void
    {
        $this->dispatchBrowserEvent('hideModal');
        $this->resetForm();
    }

    protected function resetForm(): void
    {
        $this->resetErrorBag();
        $this->resetValidation();

        $this->user_s    = null;
        $this->companyS  = null;
        $this->rehiring  = false;
        $this->newsend   = false;

        $this->ids       = [];
        $this->isBulk    = false;
    }

    public function render()
    {
        return view('livewire.construction.hiring.actions.edit');
    }
}
