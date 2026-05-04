<?php

namespace App\Console\Commands\SqlLog;

use App\Console\Commands\Concerns\ShowsProgress;
use App\Models\ExternalComment;
use App\Models\Note;
use App\Models\SicodeSql\LogExternalEntities;
use Illuminate\Console\Command;

class ExternalEntitieLog extends Command
{
    use ShowsProgress;

    protected $signature   = 'sicode:log_externalEntities';
    protected $description = 'Adds a log of external entities to the SQL database';

    public function handle()
    {
        $this->info('Starting the log of external entities...');

        // Consulta base usada tanto para contar quanto para chunk
        $baseQuery = Note::where(function ($q) {
            $q->where(fn ($q2) => $q2->where('type_note', 2)->whereIn('nstats', [11,20]))
              ->orWhere(fn ($q2) => $q2->where('type_note', 1)->where('centerjob', 'ORGAOEXT'));
        });

        // Contagem total de notas a processar
        $totalNotes = $baseQuery->count();

        // Se não tiver nada, já sai
        if ($totalNotes === 0) {
            return $this->info('No notes to process.');
        }

        // Inicializa a barra de progresso
        $bar = $this->createProgressBar($totalNotes);
        $bar->setFormat('verbose'); // mostra "current/total" e tempo estimado
        $bar->start();

        // Total de entidades externas (mesmo para todos os notes)
        $totalEntities = $totalNotes; // ou outra lógica se necessário

        // Definição das colunas para upsert
        $columns = [
            'note',
            'type_note',
            'n_entities',
            'last_protocol',
            'dt_last_protocol',
            'sts_last_protocol',
            'last_entitie',
            'rubrica',
            'city',
            'pedido',
            'status',
            'last_update',
            'last_user',
            'dt_status',
            'dt_created',
            'situation',
            'completed',
            'created_at',
            'updated_at',
        ];

        // Colunas que serão atualizadas em caso de conflito
        $updateCols = array_filter($columns, fn ($col) => ! in_array($col, ['note', 'created_at']));

        // Calcula o tamanho de cada chunk para não ultrapassar ~2000 binds
        $maxBatch = floor(2000 / count($columns));

        // Processa em chunks
        $baseQuery
            ->with('externals.protocols', 'externals.comments.user')
            ->chunk($maxBatch, function ($notes) use (
                $totalEntities,
                $updateCols,
                $bar
            ) {
                $rows = [];
                $now  = now();

                foreach ($notes as $note) {
                    // Avança a barra mesmo que não haja externals, para refletir progresso
                    $bar->advance();

                    // if ($note->externals->isEmpty()) {
                    //     continue;
                    // }
                    $lastComments = ExternalComment::whereIn('external_id', $note->externals->pluck('id'))
                        ->orderBy('created_at', 'desc')
                        ->with('external')
                        ->first();
                    $lastProtocol  = $lastComments?->external->protocols?->last();

                    $firstExternal = $lastComments?->external;

                    $rows[] = [
                        'note'             => $note->note,
                        'type_note'        => $note->type_note,
                        'n_entities'       => $note->externals?->count() ?? 0,
                        'last_protocol'    => $lastProtocol->protocol ?? null,
                        'dt_last_protocol' => $lastProtocol->created_at ?? null,
                        'sts_last_protocol' => $lastProtocol->status ?? null,
                        'last_entitie'     => $firstExternal->entidade ?? null,
                        'rubrica'          => $note->rubrica,
                        'city'             => $note->lexp,
                        'pedido'           => $note->numPedido,
                        'status'           => $note->nstats,
                        'last_update'      => $lastComments?->created_at,
                        'last_user'        => $lastComments?->user->name,
                        'dt_status'        => $note->dt_status,
                        'dt_created'       => $note->dt_created,
                        'situation'        => $lastComments?->title,
                        'completed'        => false,
                        'created_at'       => $now,
                        'updated_at'       => $now,
                    ];
                }

                if (! empty($rows)) {
                    LogExternalEntities::upsert(
                        $rows,
                        ['note'],    // chave única
                        $updateCols  // colunas a atualizar
                    );
                }
            });

        // Finaliza a barra
        $bar->finish();
        $this->newLine(2);
        $this->info('Log of external entities completed successfully.');
    }
}
