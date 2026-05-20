<?php

namespace App\Console\Commands\Legal;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ValidateLegalImportCommand extends Command
{
    protected $signature = 'legal:validate-import {--limit=20 : Limite de linhas por bloco de amostra}';

    protected $description = 'Executa validações pós-importação do módulo jurídico R3.';

    public function handle(): int
    {
        $limit = max(1, (int) $this->option('limit'));

        $this->info('Validação R3 - Duplicidade de casos');
        $duplicateCases = DB::table('legal_cases')
            ->select('case_number_normalized', 'process_number_core', DB::raw('COUNT(*) as total'))
            ->groupBy('case_number_normalized', 'process_number_core')
            ->havingRaw('COUNT(*) > 1')
            ->limit($limit)
            ->get();
        $this->printRows($duplicateCases->all(), ['case_number_normalized', 'process_number_core', 'total']);

        $this->newLine();
        $this->info('Validação R3 - Duplicidade de demandas');
        $duplicateDemands = DB::table('legal_demands')
            ->select('source_occurrence_key', DB::raw('COUNT(*) as total'))
            ->groupBy('source_occurrence_key')
            ->havingRaw('COUNT(*) > 1')
            ->limit($limit)
            ->get();
        $this->printRows($duplicateDemands->all(), ['source_occurrence_key', 'total']);

        $this->newLine();
        $this->info('Validação R3 - Casos com múltiplas demandas');
        $casesWithManyDemands = DB::table('legal_demands')
            ->select('legal_case_id', DB::raw('COUNT(*) as total_demands'))
            ->groupBy('legal_case_id')
            ->havingRaw('COUNT(*) > 1')
            ->orderByDesc('total_demands')
            ->limit($limit)
            ->get();
        $this->printRows($casesWithManyDemands->all(), ['legal_case_id', 'total_demands']);

        $this->newLine();
        $this->info('Validação R3 - Demandas por fonte');
        $demandsBySource = DB::table('legal_demands')
            ->select('source_type', DB::raw('COUNT(*) as total'))
            ->groupBy('source_type')
            ->orderBy('source_type')
            ->get();
        $this->printRows($demandsBySource->all(), ['source_type', 'total']);

        $this->newLine();
        $this->info('Validação R3 - Ausências por fonte');
        $missingBySource = DB::table('legal_demands')
            ->select('source_type', DB::raw('COUNT(*) as total_missing'))
            ->where('source_presence_status', 'missing')
            ->groupBy('source_type')
            ->orderBy('source_type')
            ->get();
        $this->printRows($missingBySource->all(), ['source_type', 'total_missing']);

        return self::SUCCESS;
    }

    private function printRows(array $rows, array $columns): void
    {
        if (empty($rows)) {
            $this->line('Sem registros.');
            return;
        }

        $this->table($columns, array_map(function ($row) use ($columns) {
            $data = [];
            foreach ($columns as $column) {
                $data[$column] = $row->{$column} ?? null;
            }

            return $data;
        }, $rows));
    }
}

