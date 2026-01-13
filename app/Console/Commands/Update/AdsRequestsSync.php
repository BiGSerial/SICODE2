<?php

namespace App\Console\Commands\Update;

use App\Enum\AdsRequestStatus;
use App\Models\AdsRequest;
use App\Models\SicodeSql\AdsRequest as SqlAdsRequest;
use Illuminate\Console\Command;

class AdsRequestsSync extends Command
{
    protected $signature = 'sicode:sync_ads_requests {--since=} {--chunk=1000} {--limit=} {--dry-run}';

    protected $description = 'Sync ADS requests status from SQL Server to SICODE.';

    public function handle(): int
    {
        $since = $this->option('since');
        $chunkSize = (int) $this->option('chunk') ?: 1000;
        $limit = $this->option('limit') ? (int) $this->option('limit') : null;
        $dryRun = (bool) $this->option('dry-run');

        $query = SqlAdsRequest::query();

        if ($since) {
            $query->where('updated_at', '>=', $since);
        }

        if ($limit) {
            $query->limit($limit);
        }

        $total = $query->count();
        $this->info('Sync ADS requests from SQL Server...');
        $this->info('Total rows: ' . $total);

        $updated = 0;
        $skipped = 0;
        $missing = 0;

        $query->orderBy('id')->chunkById($chunkSize, function ($rows) use (&$updated, &$skipped, &$missing, $dryRun) {
            foreach ($rows as $row) {
                if (!$row->sicode_id) {
                    $skipped++;
                    continue;
                }

                $local = AdsRequest::find($row->sicode_id);
                if (!$local) {
                    $missing++;
                    continue;
                }

                $payload = [
                    'status' => $row->status,
                    'attempts' => $row->attempts,
                    'description' => $row->description,
                    'url' => $row->url,
                    'completed_at' => $row->completed_at,
                    'sqlserver_id' => $row->id,
                    'completed' => $row->status === AdsRequestStatus::DONE->value,
                    'updated_at' => $row->updated_at,
                ];

                $local->fill($payload);

                if (!$local->isDirty()) {
                    $skipped++;
                    continue;
                }

                if (!$dryRun) {
                    $local->timestamps = false;
                    $local->save();
                }

                $updated++;
            }
        });

        $this->info('Updated: ' . $updated);
        $this->info('Skipped: ' . $skipped);
        $this->info('Missing local: ' . $missing);

        return 0;
    }
}
