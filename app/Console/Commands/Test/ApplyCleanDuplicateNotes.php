<?php

namespace App\Console\Commands\Test;

use App\Console\Commands\Concerns\ShowsProgress;
use App\Models\Note;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection; // Importação corrigida/adicionada
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\Console\Style\SymfonyStyle;

class ApplyCleanDuplicateNotes extends Command
{
    use ShowsProgress;

    /**
     * Adicionada a opção --force para controle de execução.
     * @var string
     */
    protected $signature = 'tools:apply_clean_duplicate_notes {--force : Aplica as mudanças no banco de dados. Sem esta flag, executa em modo de simulação.}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Encontra e corrige notas duplicadas, transferindo relações e aplicando um unique index.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $io = new SymfonyStyle($this->input, $this->output);
        $isDryRun = !$this->option('force');
        $start = microtime(true);

        if ($isDryRun) {
            $io->title('Executando em MODO DE SIMULAÇÃO (nenhuma alteração será feita)');
        } else {
            $io->title('Executando em MODO DE PRODUÇÃO (alterações serão aplicadas)');
            $io->warning('!!!! ATENÇÃO: ESTE COMANDO IRÁ MODIFICAR O BANCO DE DADOS !!!!');
            $io->note('Recomenda-se fortemente que um backup tenha sido feito antes de continuar.');
            if (!$this->confirm('Você tem certeza que deseja continuar?')) {
                $io->info('Operação cancelada pelo usuário.');
                return;
            }
        }

        // --- ETAPA 1: IDENTIFICAÇÃO ---
        $io->section('Etapa 1/5: Identificando grupos de notas duplicadas...');
        $duplicateNoteNames = Note::query()->select('note')->groupBy('note')->havingRaw('COUNT(*) > 1')->pluck('note');

        if ($duplicateNoteNames->isEmpty()) {
            $io->success('Nenhuma nota duplicada encontrada. Nenhuma ação necessária.');
            return;
        }
        $io->writeln(" <fg=green>OK</> -> " . $duplicateNoteNames->count() . " grupos de notas duplicadas encontrados.");
        $io->newLine();

        // --- ETAPA 2: PROCESSAMENTO E EXECUÇÃO ---
        $io->section('Etapa 2/5: Processando e ' . ($isDryRun ? 'simulando' : 'aplicando') . ' correções...');
        $chunkSize = 50;
        $noteChunks = $duplicateNoteNames->chunk($chunkSize);
        $processingBar = $this->createProgressBar($duplicateNoteNames->count());
        $processingBar->setFormat(' Processando Grupo %current%/%max% [%bar%] %percent:3s%%');
        $processingBar->start();

        DB::beginTransaction();
        try {
            foreach ($noteChunks as $noteNameChunk) {
                $notesInChunk = Note::whereIn('note', $noteNameChunk)->orderBy('note')->orderBy('id')->get();
                $groupedNotes = $notesInChunk->groupBy('note');

                foreach ($groupedNotes as $noteName => $noteGroup) {
                    $noteToKeep = $noteGroup->shift();
                    $notesToDeleteIds = $noteGroup->pluck('id');

                    // Lógica para transferir relações
                    $this->transferRelations($noteToKeep, $notesToDeleteIds, $isDryRun);

                    // Lógica para apagar as notas duplicadas
                    if (!$isDryRun) {
                        Note::whereIn('id', $notesToDeleteIds)->delete();
                    }
                    $processingBar->advance();
                }
            }
            if (!$isDryRun) {
                DB::commit();
            } else {
                DB::rollBack(); // Desfaz a transação no modo simulação
            }
            $processingBar->finish();
            $io->newLine(2);
            $io->success('Processamento de lotes concluído com sucesso.');
        } catch (\Exception $e) {
            DB::rollBack();
            $io->error("Um erro ocorreu durante o processamento: " . $e->getMessage());
            return;
        }

        // --- ETAPA 3: VERIFICAÇÃO PÓS-EXECUÇÃO ---
        $io->section('Etapa 3/5: Verificando se ainda existem duplicatas...');
        $remainingDuplicates = Note::query()->select('note', DB::raw('COUNT(*) as cnt'))->groupBy('note')->having('cnt', '>', 1)->count();

        if ($remainingDuplicates > 0) {
            $io->error("VERIFICAÇÃO FALHOU: Ainda existem {$remainingDuplicates} grupos de notas duplicadas. A aplicação do índice UNIQUE foi abortada.");
            return;
        }
        $io->success('Verificação concluída. Nenhuma duplicata encontrada.');
        $io->newLine();

        // --- ETAPA 4: APLICAÇÃO DO ÍNDICE UNIQUE ---
        $io->section('Etapa 4/5: Aplicando o índice UNIQUE na coluna "note"...');
        if (!$isDryRun) {
            try {
                Schema::table('notes', function ($table) {
                    $table->unique('note', 'notes_note_unique');
                });
                $io->success('Índice UNIQUE aplicado com sucesso.');
            } catch (QueryException $e) {
                // Código de erro para "duplicate key" ou "índice já existe" pode variar entre SGBDs
                if (str_contains($e->getMessage(), 'Duplicate entry') || str_contains($e->getMessage(), 'already exists')) {
                    $io->warning('Não foi possível aplicar o índice UNIQUE. Pode ser que ele já exista ou um erro de duplicidade ocorreu. Verifique manualmente.');
                } else {
                    $io->error("Não foi possível aplicar o índice UNIQUE: " . $e->getMessage());
                }
            }
        } else {
            $io->info('Simulando aplicação de índice UNIQUE (nenhuma alteração feita).');
        }
        $io->newLine();

        // --- ETAPA 5: SUMÁRIO FINAL ---
        $io->section('Etapa 5/5: Conclusão');
        $duration = microtime(true) - $start;
        $formattedDuration = gmdate('H:i:s', (int) $duration);
        $io->writeln("Tempo total de execução: <fg=yellow>{$formattedDuration}</>");
        $io->success('Operação finalizada!');
    }

    /**
     * Transfere as relações de notas antigas para a nota principal.
     */
    private function transferRelations(Note $noteToKeep, Collection $notesToDeleteIds, bool $isDryRun)
    {
        if ($notesToDeleteIds->isEmpty()) {
            return;
        }

        // Mapeamento [tabela_pivot => chave_estrangeira]
        $pivotTables = [
            'note_production'   => 'note_id', 'historic_note' => 'note_id', 'note_wpa' => 'note_id',
            'note_priority'     => 'note_id', 'note_order'    => 'note_id', 'file_note' => 'note_id',
            'note_viability'    => 'note_id', 'note_waiting'  => 'note_id',
        ];

        // Mapeamento para tabelas com MorphTo (commentable, assignable, etc.)
        // $morphTables = [
        //     'comments'          => ['type' => 'commentable_type', 'id' => 'commentable_id'],
        //     'user_assignments'  => ['type' => 'assignable_type',  'id' => 'assignable_id'],
        // ];

        // Transferência em tabelas pivot simples
        foreach ($pivotTables as $table => $foreignKey) {
            // *** CORREÇÃO APLICADA AQUI ***
            // Verifica se a tabela existe ANTES de tentar fazer o update.
            if (Schema::hasTable($table) && !$isDryRun) {
                DB::table($table)->whereIn($foreignKey, $notesToDeleteIds)->update([$foreignKey => $noteToKeep->id]);
            }
        }

        // Transferência em tabelas polimórficas
        // foreach ($morphTables as $table => $columns) {
        //     // *** CORREÇÃO APLICADA AQUI ***
        //     if (Schema::hasTable($table) && !$isDryRun) {
        //         DB::table($table)
        //             ->where($columns['type'], Note::class)
        //             ->whereIn($columns['id'], $notesToDeleteIds)
        //             ->update([$columns['id'] => $noteToKeep->id]);
        //     }
        // }

        // Para relações hasMany (one-to-many), o update é na própria tabela relacionada.
        $hasManyRelations = [
            'externals'      => 'note_id', 'work_forms'     => 'note_id', 'd5_returns'     => 'note_id',
            'ramal_forms'    => 'note_id', 'partials'       => 'note_id', 'approvals'      => 'note_id',
            'adsforms'       => 'note_id', 'old_ads'        => 'note_id',
            'temp_ads_infos' => 'note_id',
        ];

        foreach ($hasManyRelations as $table => $foreignKey) {
            // *** CORREÇÃO APLICADA AQUI ***
            if (Schema::hasTable($table) && !$isDryRun) {
                DB::table($table)->whereIn($foreignKey, $notesToDeleteIds)->update([$foreignKey => $noteToKeep->id]);
            }
        }
    }
}
