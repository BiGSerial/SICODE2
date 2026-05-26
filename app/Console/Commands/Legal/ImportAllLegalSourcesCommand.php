<?php

namespace App\Console\Commands\Legal;

use App\Services\Legal\LegalImportService;

class ImportAllLegalSourcesCommand extends BaseLegalImportCommand
{
    protected $signature = 'legal:import-all
        {--source= : Fonte especifica (injunction|sentence|subsidy)}
        {--dry : Simula sem gravar}
        {--limit= : Limite de linhas por fonte}
        {--since= : Filtra por data de mudança (YYYY-MM-DD ou datetime)}
        {--force-snapshot : Força snapshot mesmo sem alteração}
        {--no-missing-check : Não marca ausentes na origem}';

    protected $description = 'Extrai e importa todas as fontes juridicas externas no padrao v2.';

    public function handle(LegalImportService $service): int
    {
        $source = $this->option('source');
        $sources = $source ? [$source] : ['injunction', 'sentence', 'subsidy'];

        $exitCode = self::SUCCESS;
        foreach ($sources as $item) {
            if (!in_array($item, ['injunction', 'sentence', 'subsidy'], true)) {
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
