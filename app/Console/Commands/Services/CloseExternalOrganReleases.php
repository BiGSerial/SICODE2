<?php

namespace App\Console\Commands\Services;

use App\Models\ExternalOrganRelease;
use Illuminate\Console\Command;

class CloseExternalOrganReleases extends Command
{
    protected $signature = 'sicode:external-organ-releases:close {--limit=1000}';

    protected $description = 'Fecha pendências de Obras Liberadas OE quando a nota chega aos status 20 ou 11.';

    public function handle(): int
    {
        $limit = max(1, (int) $this->option('limit'));
        $closed = 0;

        ExternalOrganRelease::query()
            ->pending()
            ->whereHas('note', function ($q) {
                $q->whereIn('nstats', ExternalOrganRelease::RELEASE_STATUSES);
            })
            ->with('note:id,nstats,dt_status')
            ->orderBy('id')
            ->limit($limit)
            ->get()
            ->each(function (ExternalOrganRelease $release) use (&$closed) {
                $note = $release->note;
                if (!$note) {
                    return;
                }

                $release->update([
                    'released_at' => $note->dt_status,
                    'release_dt_status' => $note->dt_status,
                    'release_detected_at' => now(),
                    'release_nstats' => $note->nstats,
                    'released_by' => null,
                ]);

                $closed++;
            });

        $this->info("Pendências de Obras Liberadas OE fechadas: {$closed}");

        return self::SUCCESS;
    }
}
