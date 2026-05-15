<?php

namespace App\Console\Commands\Legal;

use App\Services\Legal\LegalImportService;

class ImportLegalInjunctionsCommand extends BaseLegalImportCommand
{
    protected $signature = 'legal:import-liminares
        {--dry : Simula sem gravar}
        {--limit= : Limite de linhas}
        {--since= : Filtra por Data Alteração (YYYY-MM-DD ou datetime)}
        {--force-snapshot : Força snapshot mesmo sem alteração}
        {--no-missing-check : Não marca ausentes na origem}';

    protected $description = 'Importa liminares da base externa para o modulo juridico.';

    public function handle(LegalImportService $service): int
    {
        return $this->runImport($service, 'liminar');
    }
}
