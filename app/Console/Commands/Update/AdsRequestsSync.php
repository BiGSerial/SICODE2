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
        $since = $this->option('since') ?: now()->subDay()->toDateTimeString();
        $chunkSize = (int) $this->option('chunk') ?: 1000;
        $limit = $this->option('limit') ? (int) $this->option('limit') : null;
        $dryRun = (bool) $this->option('dry-run');

        $query = SqlAdsRequest::query();

        $query->where('updated_at', '>=', $since);

        if ($limit) {
            $query->limit($limit);
        }

        $total = $query->count();
        $this->info('Sync ADS requests from SQL Server...');
        $this->info('Total rows: ' . $total);

        $updatedLocal = 0;
        $updatedSql = 0;
        $skipped = 0;
        $missing = 0;
        $conflicts = 0;

        $query->orderBy('id')->chunkById($chunkSize, function ($rows) use (&$updatedLocal, &$updatedSql, &$skipped, &$missing, &$conflicts, $dryRun) {
            $sicodeIds = $rows->pluck('sicode_id')->filter()->values();
            $localsById = $sicodeIds->isEmpty()
                ? collect()
                : AdsRequest::query()
                    ->whereIn('id', $sicodeIds)
                    ->get()
                    ->keyBy('id');

            foreach ($rows as $row) {
                if (!$row->sicode_id) {
                    $skipped++;
                    continue;
                }

                $local = $localsById->get($row->sicode_id);
                if (!$local) {
                    $missing++;
                    continue;
                }

                $sqlUpdatedAt = $row->updated_at?->getTimestamp() ?? 0;
                $localUpdatedAt = $local->updated_at?->getTimestamp() ?? 0;

                if ($sqlUpdatedAt === $localUpdatedAt) {
                    $skipped++;
                    continue;
                }

                if ($localUpdatedAt > $sqlUpdatedAt) {
                    $payloadSql = [
                        'status' => $local->status instanceof AdsRequestStatus ? $local->status->value : $local->status,
                        'attempts' => $local->attempts,
                        'description' => $local->description,
                        'url' => $local->url,
                        'completed_at' => $local->completed_at,
                        'partner' => $local->partner ? 1 : 0,
                        'batch_id' => $local->batch_id,
                        'updated_at' => $local->updated_at,
                    ];

                    if (!$dryRun) {
                        $row->fill($payloadSql);
                        $row->timestamps = false;
                        $row->save();
                    }

                    $updatedSql++;
                    continue;
                }

                $payloadLocal = [
                    'status' => $row->status,
                    'attempts' => $row->attempts,
                    'description' => $row->description,
                    'url' => $row->url,
                    'completed_at' => $row->completed_at,
                    'partner' => $row->partner,
                    'batch_id' => $row->batch_id,
                    'completed' => $row->status === AdsRequestStatus::DONE->value,
                    'updated_at' => $row->updated_at,
                ];

                if (!$local->sqlserver_id) {
                    $payloadLocal['sqlserver_id'] = $row->id;
                } elseif ($local->sqlserver_id !== $row->id) {
                    $conflicts++;
                }

                $local->fill($payloadLocal);

                if (!$local->isDirty()) {
                    $skipped++;
                    continue;
                }

                if (!$dryRun) {
                    $local->timestamps = false;
                    $local->save();
                }

                $updatedLocal++;
            }
        });

        $this->info('Updated SICODE: ' . $updatedLocal);
        $this->info('Updated SQL Server: ' . $updatedSql);
        $this->info('Skipped: ' . $skipped);
        $this->info('Conflicts: ' . $conflicts);
        $this->info('Missing local: ' . $missing);

        return 0;
    }
}
