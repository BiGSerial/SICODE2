<?php

namespace App\Http\Livewire\Engineers;

use App\Services\Engineers\InformDossier;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

/**
 * Modal do dossiê completo de um informe (final ou parcial) para a Engenharia.
 * Traz saldo de PEP, operações, evidências do fiscal e motivos de rejeição, então é exclusivo de usuário interno:
 * o parceiro continua nos modais enxutos de `Partner\Show`.
 */
class InformDetail extends Component
{
    public const TABS = [
        'overview' => 'Visão geral',
        'evidence' => 'Evidências',
        'budget'   => 'PEP e operações',
        'history'  => 'Fluxo e rejeições',
    ];

    public ?string $type = null;

    public ?int $informId = null;

    public string $tab = 'overview';

    protected $listeners = ['open_inform'];

    public function open_inform(string $type, int $id): void
    {
        $this->authorizeInternal();

        $this->type     = $type === InformDossier::TYPE_PARTIAL ? InformDossier::TYPE_PARTIAL : InformDossier::TYPE_FINAL;
        $this->informId = $id;
        $this->tab      = 'overview';

        $this->dispatchBrowserEvent('showModal', ['id' => 'modal_inform_detail']);
    }

    public function setTab(string $tab): void
    {
        if (array_key_exists($tab, self::TABS)) {
            $this->tab = $tab;
        }
    }

    public function render()
    {
        $this->authorizeInternal();

        return view('livewire.engineers.inform-detail', [
            'd' => $this->informId ? app(InformDossier::class)->find($this->type, $this->informId) : null,
        ]);
    }

    private function authorizeInternal(): void
    {
        $user = auth()->user();

        abort_unless(
            $user && !$user->onlyparner && (Gate::allows('engineer') || Gate::allows('management') || Gate::allows('admin') || Gate::allows('superadm')),
            403
        );
    }
}
