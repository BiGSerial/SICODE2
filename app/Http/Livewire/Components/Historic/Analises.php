<?php

namespace App\Http\Livewire\Components\Historic;

use App\Models\Production;
use Livewire\Component;

class Analises extends Component
{
    public $production;

    public $conclusion;

    public $exibition;

    public bool $isSingleton = false;

    protected $listeners = [
        'openHistoricAnalise' => 'loadAndOpen',
    ];

    public function mount($production_id = null, bool $isSingleton = false)
    {
        $this->isSingleton = $isSingleton;
        if ($production_id) {
            $this->doLoad($production_id);
        }
    }

    public function loadAndOpen(int $productionId): void
    {
        $this->doLoad($productionId);
        $this->dispatchBrowserEvent('show-analise-modal-singleton');
    }

    private function doLoad(int $productionId): void
    {
        $this->production = Production::with(['Analise' => function ($query) {
            return $query->select(
                'production_id',
                'comprador as Comprador',
                'matricula as Matricula',
                'area as Área',
                'documento as Documento',
                'endereco as Endereço',
                'alimentador as Alimentador',
                'ninst as Número de Instalacao',
                'nMedidor as Número do Medidor',
                'patrimonio as Patrimônio',
                'lat as Latitude',
                'lon as Longitde',
                'carga_ini as Carga Inicial',
                'carga_fim as Carga Final',
                'queda as Queda',
                'queda_max as Queda Max',
                'queda_cliente as Queda no Cliente',
                'vao as Vão',
                'restricao as Restrição',
                'motivo as Motivo',
                'postes as Postes',
                'doe as Depende Orgão Externo',
                'card as Carta',
                'preresult as Finalidade',
                'info as Informação',
                'conclusion as Conclusão',
                'protocol as Protocolo'
            );
        }])->find($productionId);

        $this->conclusion = null;
        $this->exibition = null;

        if ($this->production?->Analise) {
            $this->exibition = collect($this->production->Analise->toArray())->map(function ($value, $key) {
                return [
                    'chave' => $key,
                    'valor' => trim((string) $value) ? $value : null,
                ];
            });

            $this->conclusion = $this->production->Analise['Conclusão'];
        }
    }

    public function render()
    {
        return view('livewire.components.historic.analises', [
            'isSingleton' => $this->isSingleton,
            'production'  => $this->production,
            'conclusion'  => $this->conclusion,
            'exibition'   => $this->exibition,
        ]);
    }
}
