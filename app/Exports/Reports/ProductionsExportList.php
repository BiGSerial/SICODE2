<?php

namespace App\Exports\Reports;

use App\Custom\Notestatus;
use App\Models\Edp_depc\City;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\RegistersEventListeners;
use App\Models\Production;
use Carbon\Carbon;
use Carbon\CarbonInterval;
use Maatwebsite\Excel\Concerns\WithProperties;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class ProductionsExportList implements FromQuery, WithEvents, WithProperties, WithHeadings, WithChunkReading, WithMapping
{
    use Exportable;
    use RegistersEventListeners;

    protected $data;
    private $cities;

    public function __construct($data)
    {
        $this->data = $data;
        $this->cities = City::all();
    }

    public function query()
    {
        return $this->data;
    }

    public function chunkSize(): int
    {
        return 1000;
    }

    public function map($row): array
    {


        $city = $this->cities->firstWhere('rdMunicipio', $row->Note->nexp);


        return [
            $row->Dispatcher ? $row->Dispatcher->name : '',
            isset($row->Dispatcher->Employee->Contract->company->name) ? $row->Dispatcher->Employee->Contract->company->name : '',
            $row->User ? $row->User->name : '',
            $row->Company ? $row->Company->name : '',
            $row->Service ? $row->Service->service : '',
            $row->Note->rubrica,
            $row->Note->type_note,
            $row->Note->note,
            $row->Note->doe ? 'Sim' : 'Não',
            $row->Note->group2,
            $row->Note->material,
            $row->Note->lexp,
            $city ? $city->centro : '',
            $city ? $city->baseConstrucao : '',
            Carbon::parse($row->dt_note)->format('d/m/Y H:i:s'),
            Carbon::parse($row->dispatch_at)->format('d/m/Y H:i:s'),
            Carbon::parse($row->att_at)->format('d/m/Y H:i:s'),
            Carbon::parse($row->completed_at)->format('d/m/Y H:i:s'),
            $row->odi,
            $row->odd,
            $row->ods,
            $row->eo ? 'Sim' : 'Não',
            $row->iproject ? 'Sim' : 'Não',
            $row->cad ? 'Sim' : 'Não',
            $row->cadastro ? 'Sim' : 'Não',
            $row->postes_c,
            $row->postes_l,
            $row->postes_u,
            CarbonInterval::seconds($row->stopped)->cascade()->forHumans(['short' => true]),
            $row->d5 ? 'Sim' : 'Não',
            $row->confirmed ? 'Sim' : 'Não',
            Notestatus::status($row->status)->status,
            $row->Analise ? $row->Analise->conclusion : '',
        ];
    }

    public function headings(): array
    {
        return [
            'Despachante',
            'Empresa',
            'Usuario',
            'Empresa',
            'Serviço',
            'Rubrica',
            'TipoNota',
            'Nota',
            'DOE',
            'Grp2',
            'Descricao',
            'Municipio',
            'Centro',
            'Base',
            'Data Status (OV)',
            'Despachado em',
            'Atribuído em',
            'Finalizado em',
            'ODI/DR',
            'ODD',
            'ODS',
            'EO',
            'iProject',
            'CAD',
            'Cadastro',
            'Postes Cadastro',
            'Postes Levantado',
            'Postes/Ativos',
            'Parado',
            'RetornoInterno',
            'Situação',
            'Produção',
            'Conclusão'

        ];
    }

    public function properties(): array
    {
        return [
            'creator'        => 'SICODE',
            'lastModifiedBy' => 'SICODE',
            'title'          => 'Relatorio Automatico Sicode',
            'description'    => 'Arquivo gerado automaticamente via SICODE',
            'subject'        => 'Relatorios',
            'manager'        => 'Joao Paulo Mantovani',
            'company'        => 'EDP Energias do Brasil',
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                // Define o estilo para a primeira linha
                $event->sheet->getStyle('A1:AG1')->applyFromArray([
                    'font' => [
                        'bold'  => true,
                        'color' => ['rgb' => 'FFFFFF'], // Cor do texto (branco)
                    ],
                    'fill' => [
                        'fillType'   => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '0000FF'], // Cor de fundo (azul)
                    ],
                ]);

                $event->sheet->autoSize();
            },
        ];
    }
}
