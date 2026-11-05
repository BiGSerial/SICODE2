<?php

namespace App\Console\Commands\Tools;

use App\Models\Audit;
use App\Services\Audit\AuditPayload;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Preenche nota, atividade e registro (e, com --changes, os campos alterados) das auditorias antigas a partir do JSON.
 * Retomável: só toca em linhas ainda sem contexto. Rode depois da migration, fora do horário de pico.
 */
class BackfillAuditContext extends Command
{
    protected $signature = 'audits:backfill-context {--chunk=20000 : Linhas por lote} {--changes : Também calcula changes_count/changed_fields (PHP, mais lento)}';

    protected $description = 'Extrai nota, atividade e registro das auditorias antigas (JSON) para as colunas indexadas';

    public function handle(): int
    {
        $chunk = max(1000, (int) $this->option('chunk'));
        $max   = (int) DB::table('audits')->max('id');
        $from  = (int) DB::table('audits')->min('id');

        $this->info("Contexto (SQL): ids {$from} a {$max}");

        for ($start = $from; $start <= $max; $start += $chunk) {
            $end = $start + $chunk - 1;

            DB::update(<<<'SQL'
                UPDATE audits SET
                    note_id    = CAST(COALESCE(JSON_UNQUOTE(JSON_EXTRACT(`after`, '$.note_id')), JSON_UNQUOTE(JSON_EXTRACT(`before`, '$.note_id'))) AS UNSIGNED),
                    service_id = COALESCE(JSON_UNQUOTE(JSON_EXTRACT(`after`, '$.service_id')), JSON_UNQUOTE(JSON_EXTRACT(`before`, '$.service_id'))),
                    record_id  = COALESCE(JSON_UNQUOTE(JSON_EXTRACT(`after`, '$.id')), JSON_UNQUOTE(JSON_EXTRACT(`before`, '$.id')))
                WHERE id BETWEEN ? AND ? AND note_id IS NULL AND service_id IS NULL AND record_id IS NULL
                SQL, [$start, $end]);

            $this->line("  até {$end}");
        }

        if ($this->option('changes')) {
            $this->info('Campos alterados (PHP)');

            Audit::query()->where('action', 'updated')->whereNull('changes_count')
                ->select(['id', 'action', 'before', 'after'])
                ->chunkById(1000, function ($audits) {
                    foreach ($audits as $audit) {
                        $context = AuditPayload::context($audit->before, $audit->after, $audit->action);

                        DB::table('audits')->where('id', $audit->id)->update([
                            'changes_count'  => $context['changes_count'],
                            'changed_fields' => $context['changed_fields'],
                        ]);
                    }
                });
        }

        $this->info('Concluído.');

        return self::SUCCESS;
    }
}
