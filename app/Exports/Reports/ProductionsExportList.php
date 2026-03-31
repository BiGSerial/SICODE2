<?php

namespace App\Exports\Reports;

use App\Custom\Notestatus;
use App\Helpers\DaysLeft;
use App\Models\Edp_depc\City;
use App\Models\Operation;
use App\Models\Production;
use Carbon\CarbonInterval;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\RegistersEventListeners;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithProperties;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class ProductionsExportList implements FromQuery, WithEvents, WithProperties, WithHeadings, WithChunkReading, WithMapping
{
    use Exportable;
    use RegistersEventListeners;

    protected $data;
    private $cities;
    private $rowEstimate;

    /** Quantas linhas usar como amostra para estimar larguras (quanto maior, mais preciso / mais lento) */
    private int $autosizeSampleRows = 200;

    /** Limiares/limites para heurística de largura */
    private int $shortColMaxLen = 18;   // até 18 chars = pode usar autosize
    private int $minWidth       = 12;   // largura mínima para colunas longas
    private int $maxWidth       = 60;   // largura máxima para colunas longas
    private int $padding        = 2;    // “folga” para colunas longas (maxLen + padding)

    public function __construct($data, ?int $rowEstimate = null)
    {
        $this->data   = $data;
        $this->rowEstimate = $rowEstimate ?? 0;
        $this->cities = City::all(); // cache em memória p/ map()
    }

    public function query()
    {
        // IMPORTANTE: garanta que o query que chega aqui já está com os eager loads necessários
        // (User, Company, Service, Note, Dispatcher, Analise, Reclaim, Note.RamalForm, Note.WorkForm etc.)
        // para evitar N+1 no map().
        return $this->data;
    }

    public function chunkSize(): int
    {
        // Pode aumentar para 2000/3000 se seu banco/worker aguentar
        return 1000;
    }

    public function map($row): array
    {
        // Evita Carbon::parse quando já são instâncias de Carbon (tipicamente são)
        // $city = $this->cities->firstWhere('rdMunicipio', $row->Note?->nexp);



        if ($row->partial == true) {
            $tipoProducao = 'PARCIAL';
        } elseif ($row->d5 == true) {
            $tipoProducao = 'RETORNO INTERNO';
        } elseif ($row->dfive == true) {
            $tipoProducao = 'D5';
        } else {
            $tipoProducao = 'NORMAL';
        }

        $dateFinal = (new DaysLeft($row->note))->getLastDate();



        $wf = $row->note->workForm ?: $row->note->workFormAny;

        $supervisioned = '';
        $ads = null;
        $adsDeliveredAt = null;
        $adsDueAt = null;
        $adsType = null;
        $orderWhere = '';

        if ($wf) {
            $ads = $wf->Adsform;
            if ($ads) {
                if ($ads->tacit) {
                    $adsType = 'TACITA';
                    $adsDueAt = $ads->tacit_due_at;
                    $adsDeliveredAt = $ads->tacit_delivered_at;
                } else {
                    $adsType = 'NORMAL';
                    $adsDeliveredAt = $ads->created_at;
                }
            }

            // 1) Opção preferida: pega os IDs relacionados sem ambiguidade
            $ops = collect($wf->Orders ?? [])->pluck('id'); // Collection<int>

            // (Alternativa equivalente, caso prefira manter pluck: ->pluck('orders.id'))
            // $ops = $wf->Orders()->pluck('orders.id');

            if ($ops->isNotEmpty()) {
                // 2) Evita first()?->value() (2 queries). Use value() direto após ordenar.
                $supervisioned = Operation::whereIn('order_id', $ops)
                    ->where('operacao', '0040')
                    ->whereNotNull('fimReal')
                    ->orderByDesc('fimReal')
                    ->value('fimReal'); // retorna string|null
            }
        }






        $d5 = $row->Note->FiveNote;

        return [
            $row->Dispatcher?->name ?? '',
            $row->Dispatcher?->Employee?->Contract?->company?->name ?? '',
            $row->User?->name ?? '',
            $row->Company?->name ?? '',
            $row->Service?->service ?? '',
            $row->Note->rubrica,
            $row->Note->type_note,
            $row->Note->note,
            $row->Note->doe ? 'Sim' : 'Não',
            $row->Note->group2,
            $row->Note->group5,
            $row->Note->material,
            $row->Note->lexp,
            $row->Note->city?->centro ?? '',
            $row->Note->city?->baseConstrucao ?? '',
            $row->dt_note?->format('d/m/Y H:i:s'),
            $row->dispatch_at?->format('d/m/Y H:i:s'),
            $row->att_at?->format('d/m/Y H:i:s'),
            $row->completed_at?->format('d/m/Y H:i:s'),
            $row->completed ? 'SIM' : 'NÃO',
            $row->odi,
            $row->odd,
            $row->ods,
            $row->eo ? 'Sim' : 'Não',
            $row->iproject ? 'Sim' : 'Não',
            $row->cad ? 'Sim' : 'Não',
            $row->cadastro ? 'Sim' : 'Não',
            $row->postes_c,
            $row->Note->postes,
            $row->postes_u,
            $row->stopped ? CarbonInterval::seconds($row->stopped)->cascade()->forHumans(['short' => true]) : '',
            $row->d5 ? 'Sim' : 'Não',
            $row->d5 ? ($row->Reclaim?->category ?? '') : '',
            $row->confirmed ? 'CONFIRMADO' : 'NAO CONFIRMADO',
            Notestatus::status($row->status)->status,
            $row->Analise->preresult ?? '',
            $row->Analise->conclusion ?? '',
            $tipoProducao,
            $row->Note->RamalForm ? 'SIM' : 'NÃO',
            $row->Note->RamalForm?->created_at?->format('d/m/Y H:i:s'),
            $row->partial_at?->format('d/m/Y H:i:s'),
            $wf ? 'SIM'.($wf->canceled ? ' (CANCELADO)' : '') : 'NÃO',
            $wf?->informed_at?->format('d/m/Y H:i:s'),
            !$wf ? 'NORMAL' : ($wf->canceled ? 'CANCELADO' : ($wf->rejected ? 'REJEITADO' : 'NORMAL')),
            $ads ? 'SIM' : 'NÃO',
            $adsDeliveredAt?->format('d/m/Y H:i:s') ?? '',
            $adsType ?? '',
            $adsDueAt?->format('d/m/Y H:i:s') ?? '',
            $supervisioned ?? '',
            is_null($row->supervision_by_partner_photos) ? '' : ($row->supervision_by_partner_photos ? 'SIM' : 'NÃO'),
            $dateFinal == '---' ? '---' : $dateFinal,
            $d5 ? 'SIM' : 'NÃO',
            $d5?->note_d5 ?? '',
            $d5?->created_at?->format('d/m/Y H:i:s'),
            $d5?->payed_at?->format('d/m/Y H:i:s'),
            $d5?->completed_at?->format('d/m/Y H:i:s'),
            $d5?->supervisioned_at?->format('d/m/Y H:i:s'),
            $d5?->updated_at?->format('d/m/Y H:i:s'),
            $d5 ? ($d5->is_archived ? 'ENCERRADO' : 'ATIVO') : '',


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
            'Grp5',
            'Material',
            'Municipio',
            'Centro',
            'Base',
            'Data Status (OV)',
            'Despachado em',
            'Atribuído em',
            'Finalizado em',
            'Concluída',
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
            'Retorno Interno (RI)',
            'RI Categoria',
            'Situação',
            'Produção',
            'Finalidade',
            'Conclusão',
            'Tipo de Produção',
            'SMC',
            'Data SMC',
            'SMC Publicado em',
            'Informe Final',
            'Data Informe Final',
            'Status Informe Final',
            'Entrega ADS',
            'Data Entrega ADS',
            'Tipo ADS',
            'Prazo ADS',
            'Ultima Fiscalização',
            'Fiscalização por Fotos da Parceira',
            'Dt Final Obra',
            'Existe D5',
            'Numero D5',
            'Data Solicitação D5',
            'Data Pagamento D5',
            'Data Conclusao D5',
            'Data Fiscalização D5',
            'Ultima Atualização D5',
            'Status D5',

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
                $sheet = $event->sheet->getDelegate();

                // Estilo do header
                $highestColumn = $sheet->getHighestColumn();
                $headerRange   = 'A1:' . $highestColumn . '1';
                $sheet->getStyle($headerRange)->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
                    'fill' => [
                        'fillType'   => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '0F4C81'],
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical'   => Alignment::VERTICAL_CENTER,
                    ],
                ]);
                $sheet->getRowDimension(1)->setRowHeight(24);

                $sheet->freezePane('A2');
                $sheet->setAutoFilter($headerRange);

                $highestRow = (int) $sheet->getHighestRow();
                if ($highestRow >= 2) {
                    $bodyRange = "A2:{$highestColumn}{$highestRow}";
                    $sheet->getStyle($bodyRange)->applyFromArray([
                        'alignment' => [
                            'vertical' => Alignment::VERTICAL_CENTER,
                        ],
                        'borders' => [
                            'allBorders' => [
                                'borderStyle' => Border::BORDER_THIN,
                                'color' => ['rgb' => 'E5E7EB'],
                            ],
                        ],
                    ]);
                }

                // ===== Heurística de largura por coluna =====
                $highestColumnIndex  = Coordinate::columnIndexFromString($highestColumn);
                $sampleLastRow       = min($highestRow, 1 + $this->autosizeSampleRows); // inclui o header (linha 1)

                if ($this->rowEstimate <= 10000) {
                    for ($colIndex = 1; $colIndex <= $highestColumnIndex; $colIndex++) {
                        $colLetter = Coordinate::stringFromColumnIndex($colIndex);

                        // 1) Comece pelo tamanho do cabeçalho
                        $headerValue = (string) ($sheet->getCell($colLetter . '1')->getValue() ?? '');
                        $maxLen = mb_strlen($headerValue);

                        // 2) Amostra das primeiras N linhas
                        for ($row = 2; $row <= $sampleLastRow; $row++) {
                            $val = $sheet->getCell($colLetter . $row)->getValue();
                            if ($val instanceof \PhpOffice\PhpSpreadsheet\RichText\RichText) {
                                $val = $val->getPlainText();
                            }
                            $len = mb_strlen((string) $val);
                            if ($len > $maxLen) {
                                $maxLen = $len;
                            }
                        }

                        // 3) Decisão: autosize só para colunas curtas; demais com largura fixa
                        if ($maxLen <= $this->shortColMaxLen) {
                            // autosize é custoso, mas aceitável para colunas "curtas"
                            $sheet->getColumnDimension($colLetter)->setAutoSize(true);
                        } else {
                            $width = min(max($maxLen + $this->padding, $this->minWidth), $this->maxWidth);
                            $sheet->getColumnDimension($colLetter)->setWidth($width);
                        }
                    }
                }

                $headings = $this->headings();
                $riColIndex = array_search('Retorno Interno (RI)', $headings, true);
                $doneColIndex = array_search('Concluída', $headings, true);
                $statusColIndex = array_search('Situação', $headings, true);
                $riCol = $riColIndex !== false ? Coordinate::stringFromColumnIndex($riColIndex + 1) : null;
                $doneCol = $doneColIndex !== false ? Coordinate::stringFromColumnIndex($doneColIndex + 1) : null;
                $statusCol = $statusColIndex !== false ? Coordinate::stringFromColumnIndex($statusColIndex + 1) : null;

                for ($row = 2; $row <= $highestRow; $row++) {
                    $rowRange = "A{$row}:{$highestColumn}{$row}";
                    if ($row % 2 === 0) {
                        $sheet->getStyle($rowRange)->getFill()->setFillType(Fill::FILL_SOLID)
                            ->getStartColor()->setRGB('F8FAFC');
                    }

                    $isRi = $riCol ? mb_strtoupper((string) $sheet->getCell("{$riCol}{$row}")->getValue()) === 'SIM' : false;
                    $isOpen = $doneCol ? mb_strtoupper((string) $sheet->getCell("{$doneCol}{$row}")->getValue()) === 'NÃO' : false;

                    if ($isOpen) {
                        $sheet->getStyle($rowRange)->getFill()->setFillType(Fill::FILL_SOLID)
                            ->getStartColor()->setRGB('FFF8E1');
                    } elseif ($isRi) {
                        $sheet->getStyle($rowRange)->getFill()->setFillType(Fill::FILL_SOLID)
                            ->getStartColor()->setRGB('FDEBD0');
                    }

                    if ($statusCol) {
                        $statusCell = "{$statusCol}{$row}";
                        $statusText = mb_strtoupper(trim((string) $sheet->getCell($statusCell)->getValue()));
                        if (str_contains($statusText, 'CONFIRMADO')) {
                            $sheet->getStyle($statusCell)->getFont()
                                ->setBold(true)
                                ->getColor()->setRGB('0F766E');
                        } elseif ($statusText !== '') {
                            $sheet->getStyle($statusCell)->getFont()
                                ->setBold(true)
                                ->getColor()->setRGB('B91C1C');
                        }
                    }
                }
            },
        ];
    }
}
