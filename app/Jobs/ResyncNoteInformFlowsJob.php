<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Artisan;
use Throwable;

/**
 * Recalcula note_inform_flows de uma nota. Usado quando um vinculo e removido
 * fisicamente (delete/detach), situacao que a carga incremental por updated_at
 * nao enxerga.
 */
class ResyncNoteInformFlowsJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;

    public function __construct(public int $noteId)
    {
    }

    public static function dispatchForNotes(iterable $noteIds): void
    {
        foreach (collect($noteIds)->filter()->map(fn ($id) => (int) $id)->unique() as $noteId) {
            static::dispatch($noteId)->afterCommit();
        }
    }

    public function handle(): void
    {
        try {
            Artisan::call('sicode:sync-note-inform-flows', ['--note_id' => $this->noteId]);
        } catch (Throwable $e) {
            report($e);
        }
    }
}
