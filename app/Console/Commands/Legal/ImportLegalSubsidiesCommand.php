<?php

namespace App\Console\Commands\Legal;

use App\Services\Legal\LegalImportService;

class ImportLegalSubsidiesCommand extends BaseLegalImportCommand
{
    protected $signature = 'legal:import-subsidies
        {--dry : Simula sem gravar}
        {--limit= : Limite de linhas}
        {--since= : Filtra por Data Alteração (YYYY-MM-DD ou datetime)}
        {--force-snapshot : Força snapshot mesmo sem alteração}
        {--no-missing-check : Não marca ausentes na origem}';

    protected $description = 'Extrai e importa subsidios no padrao juridico v2.';

    public function handle(LegalImportService $service): int
    {
        return $this->runImport($service, 'subsidy');
    }
}
