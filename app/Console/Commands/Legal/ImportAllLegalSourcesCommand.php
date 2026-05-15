<?php

namespace App\Console\Commands\Legal;

use App\Services\Legal\LegalImportService;

class ImportAllLegalSourcesCommand extends BaseLegalImportCommand
{
    protected $signature = 'legal:import-all
        {--source= : Fonte especifica (liminar|sentence|subsidy)}
        {--dry : Simula sem gravar}
        {--limit= : Limite de linhas por fonte}
        {--since= : Filtra por Data Alteração (YYYY-MM-DD ou datetime)}
        {--force-snapshot : Força snapshot mesmo sem alteração}
        {--no-missing-check : Não marca ausentes na origem}';

    protected $description = 'Importa todas as fontes juridicas externas.';

    public function handle(LegalImportService $service): int
    {
        $source = $this->option('source');
        $sources = $source ? [$source] : ['liminar', 'sentence', 'subsidy'];

        $exitCode = self::SUCCESS;
        foreach ($sources as $item) {
            if (!in_array($item, ['liminar', 'sentence', 'subsidy'], true)) {
                $this->error("Fonte invalida: {$item}");
                return self::FAILURE;
            }

            $this->newLine();
            $this->info("Importando fonte {$item}...");
            $current = $this->runImport($service, $item);
            if ($current !== self::SUCCESS) {
                $exitCode = self::FAILURE;
            }
        }

        return $exitCode;
    }
}
