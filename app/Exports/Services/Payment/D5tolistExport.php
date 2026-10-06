<?php

namespace App\Exports\Services\Payment;

use App\Models\City;
use App\Models\Production;
use App\Services\D5\ProductionFiveNoteResolver;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithProperties;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class D5tolistExport implements FromCollection, WithMapping, WithHeadings, WithProperties, WithEvents
{
    use Exportable;

    protected $service;
    protected $user_id;
    protected Collection $cities;
    protected $data;

    public function __construct($service, $user_id)
    {
        $this->service = $service;
        $this->user_id = $user_id;
        $this->cities = collect();
        $this->data = collect();
    }

    /**
     * Produções D5 do usuário (mesmo recorte da lista de Acompanhamento: abertas, do usuário selecionado).
     * A D5 de cada produção é a associada a ela (vínculo direto ou D5 do informe da produção) — não só a
     * D5 legada da nota, que deixava a planilha vazia para quem só tem D5 vinculada a informe.
     */
    public function collection()
    {
        $resolver = app(ProductionFiveNoteResolver::class);

        return Production::query()
            ->where('service_id', $this->service->uuid)
            ->where('user_id', $this->user_id)
            ->where('completed', false)
            ->where('status', '!=', 5)
            ->where('dfive', true)
            ->with([
                'note.WorkForm.Company', 'note.WorkForm.Orders',
                'note.WorkFormAny.Company', 'note.WorkFormAny.Orders',
                'fiveNotes', 'WorkReportFlowProductions',
            ])
            ->get()
            ->each(function (Production $production) use ($resolver) {
                $five = $resolver->resolve($production);
                $five?->loadMissing('WorkReport.Company', 'WorkReport.Orders');
                $production->setRelation('resolvedFive', $five);
            })
            ->filter(fn (Production $production) => $production->getRelation('resolvedFive') !== null)
            ->values();
    }

    public function headings(): array
    {
        return [
            'Empreiteira',              // A
            'Centro /Resp',             // B
            'Diagrama',                 // C
            'Descricao Texto',          // D
            'Loc.Instalacao',           // E
            'Nota Gerada',              // F
            'Conjunto',                 // G
            'Texto - Conjunto',         // H
            'Elemento PEP',             // I
            'Motivos Retorno',          // J
            'Tipo de nota',             // K
            'Codificacao',              // L
            'Texto - Codificacao',      // M
            'Texto Sintoma',            // N
            'GPM',                      // O
            'Centro',                   // P
            'OV/NOTA',                  // Q
            'Valor da Obra Estimado',   // R
            'Id Informe',               // S
            'Escopo do Informe',        // T
        ];
    }

    public function map($row): array
    {

        $five = $row->getRelation('resolvedFive');
        // O informe da D5 é o dono das ordens; D5 legada (sem informe) usa o informe da nota.
        $workForm = $five?->WorkReport ?: $row->note?->WorkForm ?: $row->note?->WorkFormAny;
        $order = $workForm?->Orders?->sortBy('ordem')->first();

        // CORREÇÃO DE PERFORMANCE (N+1): Cacheia a consulta de cidades
        $cityCode = $row->note?->nexp;
        $city = null;
        if ($cityCode) {
            if (!$this->cities->has($cityCode)) {
                $this->cities->put($cityCode, City::where('rdMunicipio', $cityCode)->first());
            }
            $city = $this->cities->get($cityCode);
        }

        $moa = $workForm?->Orders?->sum('moaberto');

        // CORREÇÃO DE SEGURANÇA: explode()
        $reasonParts = explode('_', $five?->reason ?? '');
        $codifyParts = explode('_', $five?->codify ?? '');

        return [
            $workForm?->Company?->name ? $workForm?->Company?->name.($workForm?->canceled ? ' (CANCELADO)' : '') : null,
            '', // Centro /Resp
            $order?->ordem, // CORREÇÃO: Usar uma propriedade, não o objeto inteiro
            $five?->description,
            $five?->loc_install,
            $five?->note_d5 ?: '', // Nota Gerada (D5 já existente)
            $five?->conjunto,
            $row->note?->material, // Texto - Conjunto
            $order?->pep, // CORREÇÃO: Usar operador null-safe
            $reasonParts[1] ?? null, // CORREÇÃO: Acesso seguro ao índice
            'D5',
            $codifyParts[0] ?? null, // CORREÇÃO: Acesso seguro ao índice
            $codifyParts[1] ?? null, // Texto - Codificacao
            '', // Texto Sintoma
            $city?->gpm,
            $city?->centro,
            $row->note?->note, // CORRIGIDO: Adicionado null-safe para consistência
            $moa,
            $five?->work_report_id ?: ($workForm?->id ?: ''),
            $this->scopeLabel($five, $workForm),
        ];
    }

    private function scopeLabel($five, $workForm): string
    {
        $report = $five?->WorkReport ?: ($five?->work_report_id ? null : $workForm);
        $labels = collect($report?->finalScopeBadges() ?? [])->pluck('label')->filter()->implode(' / ');

        if ($labels !== '') {
            return $labels;
        }

        return $five && !$five->work_report_id ? 'Legada (sem informe)' : 'Geral';
    }

    public function properties(): array
    {
        $userName = optional(auth()->user())->name ?? 'SICODE';
        return [
            'creator'        => $userName,
            'lastModifiedBy' => $userName,
            'title'          => 'Formulario de Script',
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
                /** @var \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet */
                $sheet = $event->sheet->getDelegate();

                // ------------------------------------------------------------------
                // 1. OBTENDO OS LIMITES DA PLANILHA (EFICIÊNCIA)
                // ------------------------------------------------------------------

                // Pega a maior linha e coluna com dados para aplicar estilos apenas onde for necessário.
                $highestRow = $sheet->getHighestRow();
                $highestColumn = $sheet->getHighestColumn();
                $headerRange = 'A1:' . $highestColumn . '1';


                // ------------------------------------------------------------------
                // 2. ESTILIZAÇÃO DO CABEÇALHO (CORES DA IMAGEM)
                // ------------------------------------------------------------------

                // Mapa de cores para cada coluna do cabeçalho
                $headerColors = [
                    'A' => 'FFA500', 'B' => 'FFA500', 'C' => 'FFA500', 'E' => 'FFA500',
                    'G' => 'FFA500', 'I' => 'FFA500', 'J' => 'FFA500', 'K' => 'FFA500',
                    'L' => 'FFA500', 'O' => 'FFA500', 'P' => 'FFA500', 'Q' => 'FFA500',
                    'R' => 'FFA500', 'S' => 'FFA500', 'T' => 'FFA500', // Laranja
                    'D' => 'FFFF00', 'F' => 'FFFF00', 'N' => 'FFFF00', // Amarelo
                    'H' => 'FFDAB9', 'M' => 'FFDAB9', // Pêssego
                ];

                // Estilo base para todos os cabeçalhos
                $baseHeaderStyle = [
                    'font' => ['bold' => true, 'color' => ['rgb' => '000000']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'FFFFFF']]],
                ];

                // Aplica o estilo base e a cor de fundo específica para cada coluna
                foreach (range('A', $highestColumn) as $column) {
                    $style = $baseHeaderStyle;
                    $style['fill'] = [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => $headerColors[$column] ?? 'FFFFFF'] // Cor branca como padrão
                    ];
                    $sheet->getStyle($column . '1')->applyFromArray($style);
                }
                // Aumenta a altura da linha do cabeçalho para melhor visualização
                $sheet->getRowDimension(1)->setRowHeight(22);


                // ------------------------------------------------------------------
                // 3. LARGURA DAS COLUNAS (AUTOSIZE EFICIENTE)
                // ------------------------------------------------------------------

                // Define larguras fixas para colunas com conteúdo previsível
                $sheet->getColumnDimension('A')->setWidth(18); // Empreiteira
                $sheet->getColumnDimension('B')->setWidth(15); // Centro /Resp
                $sheet->getColumnDimension('C')->setWidth(15); // Diagrama
                $sheet->getColumnDimension('E')->setWidth(18); // Loc.Instalacao
                $sheet->getColumnDimension('F')->setWidth(15); // Nota Gerada
                $sheet->getColumnDimension('G')->setWidth(12); // Conjunto
                $sheet->getColumnDimension('I')->setWidth(25); // Elemento PEP
                $sheet->getColumnDimension('J')->setWidth(25); // Motivos Retorno
                $sheet->getColumnDimension('K')->setWidth(12); // Tipo de nota
                $sheet->getColumnDimension('L')->setWidth(15); // Codificacao
                $sheet->getColumnDimension('O')->setWidth(10); // GPM
                $sheet->getColumnDimension('P')->setWidth(10); // Centro
                $sheet->getColumnDimension('Q')->setWidth(15); // OV/NOTA
                $sheet->getColumnDimension('R')->setWidth(20); // Valor da Obra
                $sheet->getColumnDimension('S')->setWidth(12); // Id Informe
                $sheet->getColumnDimension('T')->setWidth(22); // Escopo do Informe

                // Usa AutoSize apenas para colunas com texto longo e imprevisível
                $sheet->getColumnDimension('D')->setAutoSize(true); // Descricao Texto
                $sheet->getStyle('D1:D' . $highestRow)->getAlignment()->setWrapText(true);

                $sheet->getColumnDimension('H')->setAutoSize(true); // Texto - Conjunto
                $sheet->getStyle('H1:H' . $highestRow)->getAlignment()->setWrapText(true);

                $sheet->getColumnDimension('M')->setAutoSize(true); // Texto - Codificacao
                $sheet->getStyle('M1:M' . $highestRow)->getAlignment()->setWrapText(true);

                $sheet->getColumnDimension('N')->setAutoSize(true); // Texto Sintoma
                $sheet->getStyle('N1:N' . $highestRow)->getAlignment()->setWrapText(true);


                // ------------------------------------------------------------------
                // 4. FORMATAÇÃO DE DADOS (NÚMEROS E MOEDA)
                // ------------------------------------------------------------------

                // Colunas que devem ser formatadas como números inteiros (sem decimais)
                $integerColumns = ['C', 'G', 'P', 'Q', 'S']; // Diagrama, Conjunto, GPM, Centro, OV/NOTA
                foreach ($integerColumns as $column) {
                    $sheet->getStyle($column . '2:' . $column . $highestRow)
                        ->getNumberFormat()
                        ->setFormatCode(NumberFormat::FORMAT_NUMBER);
                }

                // Colunas que são códigos e devem ser tratadas como texto para evitar formatação automática do Excel
                $textColumns = ['F', 'L']; // Nota Gerada, Codificacao
                foreach ($textColumns as $column) {
                    $sheet->getStyle($column . '2:' . $column . $highestRow)
                        ->getNumberFormat()
                        ->setFormatCode(NumberFormat::FORMAT_TEXT);
                }

                // Coluna que deve ser formatada como moeda (Real Brasileiro)
                $sheet->getStyle('R2:R' . $highestRow)
                    ->getNumberFormat()
                    ->setFormatCode('R$ #,##0.00');


                // ------------------------------------------------------------------
                // 5. ALINHAMENTO DE DADOS
                // ------------------------------------------------------------------

                // Centraliza o conteúdo de colunas específicas para melhor legibilidade
                $centerAlignColumns = ['B', 'C', 'E', 'F', 'G', 'K', 'L', 'O', 'P', 'Q', 'R', 'S', 'T'];
                foreach ($centerAlignColumns as $column) {
                    $sheet->getStyle($column . '2:' . $column . $highestRow)
                        ->getAlignment()
                        ->setHorizontal(Alignment::HORIZONTAL_CENTER);
                }
            },
        ];
    }
}
