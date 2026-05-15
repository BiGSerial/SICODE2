<?php

namespace App\Console\Commands\Legal;

use App\Services\Legal\LegalImportService;

class ImportLegalSentencesCommand extends BaseLegalImportCommand
{
    protected $signature = 'legal:import-sentences
        {--dry : Simula sem gravar}
        {--limit= : Limite de linhas}
        {--since= : Filtra por Data Alteração (YYYY-MM-DD ou datetime)}
        {--force-snapshot : Força snapshot mesmo sem alteração}
        {--no-missing-check : Não marca ausentes na origem}';

    protected $description = 'Importa sentencas/cumprimentos da base externa para o modulo juridico.';

    public function handle(LegalImportService $service): int
    {
        return $this->runImport($service, 'sentence');
    }
}
