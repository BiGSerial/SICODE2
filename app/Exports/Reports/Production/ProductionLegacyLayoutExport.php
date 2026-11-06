<?php

namespace App\Exports\Reports\Production;

use App\Exports\Reports\{PostWorkProcessStrictSheet, ProductionsExportList};
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\{WithMultipleSheets, WithProperties};

/**
 * Lista de Produção no layout anterior (59 colunas, mesma ordem e mesmos nomes), para as planilhas de controle que dependem dela
 * (reuniões quinzenais e mensais, produtividade). A primeira aba é a lista, como sempre foi; "Controle Exportacao" vai por último.
 * O filtro é o mesmo do Relatório de Produção da tela.
 */
class ProductionLegacyLayoutExport implements WithMultipleSheets, WithProperties
{
    /**
     * @param array<string, string> $control rótulo => valor da aba Controle Exportacao
     */
    public function __construct(private readonly Builder $records, private readonly int $rowEstimate, private readonly string $requester, private readonly array $control)
    {
    }

    public function sheets(): array
    {
        return [
            new ProductionsExportList($this->records, $this->rowEstimate),
            new PostWorkProcessStrictSheet('Controle Exportacao', ['Campo', 'Valor'], array_map(fn ($k, $v) => [$k, $v], array_keys($this->control), $this->control)),
        ];
    }

    public function properties(): array
    {
        return [
            'creator'        => config('app.name', 'SICODE'),
            'lastModifiedBy' => $this->requester,
            'title'          => 'Relatório de Produção (layout anterior)',
            'description'    => 'Lista de produções com as 59 colunas do layout anterior',
            'subject'        => 'Relatórios',
        ];
    }
}
