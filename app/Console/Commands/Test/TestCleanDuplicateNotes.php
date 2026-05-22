<?php

namespace App\Console\Commands\Test;

use App\Console\Commands\Concerns\ShowsProgress;
use App\Models\Note;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Console\Style\SymfonyStyle;

class TestCleanDuplicateNotes extends Command
{
    use ShowsProgress;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tools:teste_clean_duplicate_notes';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Executa uma simulação otimizada para limpar notas duplicadas, priorizando performance e baixo uso de memória.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $io = new SymfonyStyle($this->input, $this->output);
        $start = microtime(true);
        $io->title('Simulação de Limpeza de Notas Duplicadas (Modo de Baixo Consumo de Memória)');

        // Todos os relacionamentos a serem verificados.
        $relations = [
            'Productions', 'Historic', 'Wpas', 'Priorities', 'Orders',
            'Files', 'Viabilities', 'Waitings', 'Externals',
            'WorkForm', 'd5Return', 'RamalForm', 'Partials',
            'Approval', 'Adsform', 'OldAds', 'TempAdsInfos',
        ];

        // --- ETAPA 1: OBTENÇÃO DOS NOMES DUPLICADOS ---
        $io->section('Etapa 1/4: Identificando grupos de notas duplicadas...');
        $duplicateNoteNames = Note::query()
            ->select('note')
            ->groupBy('note')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('note'); // pluck() é eficiente e retorna uma coleção simples.

        if ($duplicateNoteNames->isEmpty()) {
            $io->success('Nenhuma nota duplicada encontrada. Nenhuma ação necessária.');
            return;
        }
        $io->writeln(" <fg=green>OK</> -> " . $duplicateNoteNames->count() . " grupos de notas duplicadas encontrados.");
        $io->newLine();

        // --- ETAPA 2: PROCESSAMENTO EM LOTES (CHUNKS) ---
        $io->section('Etapa 2/4: Processando registros em lotes para economizar memória...');

        $report = [
            'groups_count'            => 0,
            'removals_count'          => 0,
            'transfers_count'         => 0,
            'relations_moved_count'   => 0,
            'notes_with_relations'    => [],
            'recent_notes_count'      => 0,
            'difficulty' => [
                'Média'   => 0,
                'Difícil' => 0,
                'Crítica' => 0,
            ],
        ];

        $yesterdayTenAm = Carbon::yesterday()->setTime(10, 0);
        $chunkSize = 50; // Define o tamanho do lote.
        $noteChunks = $duplicateNoteNames->chunk($chunkSize);

        $processingBar = $this->createProgressBar($noteChunks->count());
        $processingBar->setFormat(' Processando Lote %current%/%max% [%bar%] %percent:3s%%');
        $processingBar->start();

        foreach ($noteChunks as $noteNameChunk) {
            $notesInChunk = Note::with($relations)
                ->whereIn('note', $noteNameChunk)
                ->orderBy('note')->orderBy('id')
                ->get();

            $groupedNotes = $notesInChunk->groupBy('note');

            foreach ($groupedNotes as $noteGroup) {
                $report['groups_count']++;
                $noteGroup->shift(); // Remove a nota principal (menor ID) do grupo.

                foreach ($noteGroup as $note) {
                    $relationsFound = [];
                    $relationCount = 0;
                    foreach ($relations as $relationName) {
                        if ($note->$relationName && $note->$relationName->count() > 0) {
                            $count = $note->$relationName->count();
                            $relationCount += $count;
                            $relationsFound[] = "{$relationName} ({$count})";
                        }
                    }

                    if ($relationCount > 0) {
                        $report['transfers_count']++;
                        $report['relations_moved_count'] += $relationCount;
                        $report['notes_with_relations'][] = [
                            'note' => $note->note,
                            'id'   => $note->id,
                            'relations' => implode(', ', $relationsFound)
                        ];

                        if ($relationCount <= 5) {
                            $report['difficulty']['Média']++;
                        } elseif ($relationCount <= 15) {
                            $report['difficulty']['Difícil']++;
                        } else {
                            $report['difficulty']['Crítica']++;
                        }
                    } else {
                        $report['removals_count']++;
                    }

                    if ($note->created_at && $note->created_at->isAfter($yesterdayTenAm)) {
                        $report['recent_notes_count']++;
                    }
                }
            }
            $processingBar->advance();
        }

        $processingBar->finish();
        $io->newLine(2);

        // --- ETAPA 3: ANÁLISE DE DIFICULDADE ---
        $io->section('Etapa 3/4: Análise de Dificuldade e Risco de Perda');
        $io->listing([
            "Transferências de Média Dificuldade (1-5 vínculos): " . number_format($report['difficulty']['Média']),
            "Transferências de Alta Dificuldade (6-15 vínculos): " . number_format($report['difficulty']['Difícil']),
            "Transferências Críticas (+15 vínculos): " . number_format($report['difficulty']['Crítica']),
        ]);
        if ($report['recent_notes_count'] > 0) {
            $io->warning("Encontradas " . $report['recent_notes_count'] . " notas duplicadas criadas desde ontem às 10h. Recomenda-se investigar a origem.");
        }
        $io->newLine();

        // --- ETAPA 4: SUMÁRIO ---
        $io->section('Etapa 4/4: Sumário da Simulação');

        $duration = microtime(true) - $start;
        $formattedDuration = gmdate('H:i:s', (int) $duration);

        $io->success('Simulação concluída com sucesso!');
        $io->writeln("Tempo de execução: <fg=yellow>{$formattedDuration}</>");
        $io->listing([
            "Grupos de notas duplicadas processados: " . number_format($report['groups_count']),
            "Registros a serem removidos (sem vínculos): " . number_format($report['removals_count']),
            "Registros com vínculos a serem transferidos: " . number_format($report['transfers_count']),
            "Total de vínculos que seriam movidos: " . number_format($report['relations_moved_count']),
        ]);

        if (!empty($report['notes_with_relations'])) {
            $io->section('Detalhes das Notas com Vínculos a Serem Transferidos');
            $io->table(
                ['Nota Duplicada (ID)', 'Relações Encontradas (Qtd)'],
                collect($report['notes_with_relations'])->map(function ($item) {
                    return ["{$item['note']} (ID: {$item['id']})", $item['relations']];
                })->toArray()
            );
        }
    }
}
