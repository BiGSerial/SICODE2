<?php

namespace App\Http\Livewire\Components\Historic;

use App\Models\{Analise, Production};
use Livewire\Component;

class Analises extends Component
{
    public $production;

    public $conclusion;

    public $exibition;

    public function mount($production_id)
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
        }])->find($production_id);

        // $this->production = Production::with('Analise')->find($production_id);

        // dd($this->production);

        if ($this->production) {

            if ($this->production->Analise) {
                $this->exibition = collect($this->production->Analise->toArray())->map(function ($value, $key) {
                    return [
                        'chave' => $key,
                        'valor' => trim($value) ? $value : null,
                    ];
                });

                $this->conclusion = $this->production->Analise['Conclusão'];
            }

        }
    }

    public function render()
    {
        return view('livewire.components.historic.analises');
    }
}
