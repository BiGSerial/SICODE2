<?php

namespace App\Console\Commands\Update;

use App\Enum\AdsRequestStatus;
use App\Custom\RegistroJson;
use App\Models\AdsRequest;
use App\Models\SicodeSql\AdsRequest as SqlAdsRequest;
use App\Notifications\SystemNotification;
use Illuminate\Console\Command;
use Throwable;

class AdsRequestsSync extends Command
{
    protected $signature = 'sicode:sync_ads_requests {--since=} {--chunk=1000} {--limit=} {--dry-run}';

    protected $description = 'Sync ADS requests status from SQL Server to SICODE.';

    public function handle(): int
    {
        $log = null;

        try {
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
        $log = new RegistroJson('sync_ads_requests', $this->options(), $total);
        $this->info('Sync ADS requests from SQL Server...');
        $this->info('Total rows: ' . $total);

        $updatedLocal = 0;
        $updatedSql = 0;
        $skipped = 0;
        $missing = 0;
        $conflicts = 0;
        $forcedDone = 0;
        $notifiedDone = 0;
        $forceThreshold = now()->subHours(2);
        $forceTag = '#ADS Liberada pelo Sistema (FORCE)';

        $query->orderBy('id')->chunkById($chunkSize, function ($rows) use (&$updatedLocal, &$updatedSql, &$skipped, &$missing, &$conflicts, &$forcedDone, &$notifiedDone, $dryRun, $forceThreshold, $forceTag) {
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

                if ($this->shouldForceDoneFromUrl($row, $local, $forceThreshold)) {
                    if (!$dryRun) {
                        $this->forceDoneFromUrl($row, $local, $forceTag);
                        if ($this->notifyDoneRequesterIfNeeded($local, false)) {
                            $notifiedDone++;
                        }
                    }

                    $forcedDone++;
                    $updatedLocal++;
                    $updatedSql++;
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
                        if ($this->notifyDoneRequesterIfNeeded($local, false)) {
                            $notifiedDone++;
                        }
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
                    if ($this->notifyDoneRequesterIfNeeded($local, false)) {
                        $notifiedDone++;
                    }
                }

                $updatedLocal++;
            }
        });

        $this->info('Updated SICODE: ' . $updatedLocal);
        $this->info('Updated SQL Server: ' . $updatedSql);
        $this->info('Skipped: ' . $skipped);
        $this->info('Conflicts: ' . $conflicts);
        $this->info('Missing local: ' . $missing);
        $this->info('Forced DONE (URL + >2h): ' . $forcedDone);
        $this->info('Notified DONE requester: ' . $notifiedDone);
        $log->setUpdated($updatedLocal + $updatedSql);
        $log->setNoteUpdated($skipped);
        if ($conflicts > 0 || $missing > 0) {
            $log->setErrorMessage("Conflitos={$conflicts}; MissingLocal={$missing}");
        }
        $log->save();

        return 0;
        } catch (Throwable $e) {
            if ($log instanceof RegistroJson) {
                $log->setErrorMessage($e->getMessage());
                $log->fail($e->getMessage());
            }

            return self::FAILURE;
        }
    }

    private function shouldForceDoneFromUrl(SqlAdsRequest $row, AdsRequest $local, \Carbon\Carbon $threshold): bool
    {
        $sqlStatus = (string) ($row->status ?? '');
        $localStatus = $local->status instanceof AdsRequestStatus ? $local->status->value : (string) $local->status;

        return $sqlStatus !== AdsRequestStatus::DONE->value
            && $localStatus !== AdsRequestStatus::DONE->value
            && !empty($row->url)
            && $local->created_at
            && $local->created_at->lte($threshold);
    }

    private function forceDoneFromUrl(SqlAdsRequest $row, AdsRequest $local, string $forceTag): void
    {
        $doneAt = $row->completed_at ?? now();
        $description = trim((string) ($row->description ?: ($local->description ?? '')));
        if (!str_contains($description, $forceTag)) {
            $description = trim($description . PHP_EOL . $forceTag);
        }

        $syncNow = now();

        $row->fill([
            'status' => AdsRequestStatus::DONE->value,
            'description' => $description,
            'completed_at' => $doneAt,
            'updated_at' => $syncNow,
        ]);
        $row->timestamps = false;
        $row->save();

        $local->fill([
            'status' => AdsRequestStatus::DONE->value,
            'description' => $description,
            'url' => $row->url,
            'completed_at' => $doneAt,
            'completed' => true,
            'updated_at' => $syncNow,
        ]);
        $local->timestamps = false;
        $local->save();
    }

    private function notifyDoneRequesterIfNeeded(AdsRequest $request, bool $dryRun): bool
    {
        $status = $request->status instanceof AdsRequestStatus ? $request->status->value : (string) $request->status;
        if ($status !== AdsRequestStatus::DONE->value || $request->delivered_at) {
            return false;
        }

        $user = $request->requestedBy()->first();
        if (!$user) {
            return false;
        }

        if ($dryRun) {
            return true;
        }

        $noteNumber = $request->note()->value('note') ?? $request->note_id;
        $message = "A ADS da nota <strong>{$noteNumber}</strong> está disponível.";

        $user->notify(new SystemNotification(
            'ADS disponível',
            $message,
            $request->url ?: null,
            4,
            [
                'ads_request_id' => $request->id,
                'note_id' => $request->note_id,
            ]
        ));

        $request->timestamps = false;
        $request->forceFill([
            'delivered_at' => now(),
            'updated_at' => now(),
        ]);
        $request->save();

        return true;
    }
}
