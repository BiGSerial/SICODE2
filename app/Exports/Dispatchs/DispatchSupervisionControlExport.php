<?php

namespace App\Exports\Dispatchs;

use App\Exports\Closure\ClosureStrictSheet;
use Maatwebsite\Excel\Concerns\{WithMultipleSheets, WithProperties};

/**
 * Excel do Controle de Fiscalização (padrão: docs/Padrao_Estilizacao_Exportacoes.md): `Controle`, `Resumo` por status e
 * `Controle Exportacao`. Os cabeçalhos com "Data" e "Valor" ganham formato de data/hora e de valor (2 casas) pela folha padrão.
 * As linhas vêm de `ExportDispatchSupervisionJob` (mesma consulta da tela); este arquivo só monta as abas.
 */
class DispatchSupervisionControlExport implements WithMultipleSheets, WithProperties
{
    public const HEADINGS = [
        'Nota', 'Ordens', 'Tipo', 'Rubrica', 'Município', 'Grupo 2', 'Postes',
        'Despachante', 'Empresa do despachante', 'Responsável', 'Empresa do responsável',
        'DD', 'Situação da DD', 'Data ADS', 'Valor ADS', 'ADS',
        'Data do informe', 'Dias desde o informe', 'Data do despacho', 'Dias desde o despacho', 'Data da atribuição', 'Dias desde a atribuição',
        'Status', 'Prioridade', 'Bloqueada',
    ];

    /**
     * @param array<int, array<int, mixed>> $rows
     * @param array<int, array<int, mixed>> $summaryRows
     * @param array<int, array{0: string, 1: string}> $auditRows
     */
    public function __construct(
        private readonly array $rows,
        private readonly array $summaryRows,
        private readonly array $auditRows,
        private readonly string $serviceName,
    ) {
    }

    public function sheets(): array
    {
        return [
            new ClosureStrictSheet('Controle', self::HEADINGS, $this->rows),
            new ClosureStrictSheet('Resumo', ['Status', 'Quantidade', 'Percentual (%)'], $this->summaryRows),
            // "Conteúdo" e não "Valor": a folha padrão formata como dinheiro toda coluna cujo título tem "valor" (o total sairia 1,00).
            new ClosureStrictSheet('Controle Exportacao', ['Campo', 'Conteúdo'], $this->auditRows),
        ];
    }

    public function properties(): array
    {
        return [
            'creator'        => config('app.name', 'SICODE'),
            'lastModifiedBy' => config('app.name', 'SICODE'),
            'title'          => 'Controle de ' . mb_strtolower($this->serviceName),
            'description'    => 'Controle de despacho exportado com os filtros da tela',
            'subject'        => $this->serviceName,
        ];
    }
}
