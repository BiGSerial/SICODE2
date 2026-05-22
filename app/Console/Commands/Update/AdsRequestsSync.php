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
        $skipped = 0;
        $missing = 0;
        $conflicts = 0;
        $notifiedDone = 0;

        $query->orderBy('id')->chunkById($chunkSize, function ($rows) use (&$updatedLocal, &$skipped, &$missing, &$conflicts, &$notifiedDone, $dryRun) {
            $sicodeIds = $rows->pluck('sicode_id')->filter()->values();
            $sqlIds = $rows->pluck('id')->filter()->values();
            $localsById = $sicodeIds->isEmpty()
                ? collect()
                : AdsRequest::query()
                    ->whereIn('id', $sicodeIds)
                    ->get()
                    ->keyBy('id');
            $localsBySqlId = $sqlIds->isEmpty()
                ? collect()
                : AdsRequest::query()
                    ->whereIn('sqlserver_id', $sqlIds)
                    ->get()
                    ->keyBy('sqlserver_id');

            foreach ($rows as $row) {
                $local = null;
                if ($row->sicode_id) {
                    $local = $localsById->get($row->sicode_id);
                }
                if (!$local) {
                    $local = $localsBySqlId->get($row->id);
                }
                if (!$local) {
                    $missing++;
                    continue;
                }

                $sqlStatus = $this->normalizeStatus($row->status);
                $localStatus = $local->status instanceof AdsRequestStatus
                    ? $local->status->value
                    : $this->normalizeStatus($local->status);
                $payloadLocal = [
                    'status' => $sqlStatus ?? $localStatus ?? AdsRequestStatus::QUEUED->value,
                    'attempts' => $row->attempts,
                    'description' => $row->description,
                    'url' => $row->url,
                    'completed_at' => $row->completed_at,
                    'partner' => $row->partner,
                    'batch_id' => $row->batch_id,
                    'completed' => $sqlStatus === AdsRequestStatus::DONE->value,
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
        $this->info('Skipped: ' . $skipped);
        $this->info('Conflicts: ' . $conflicts);
        $this->info('Missing local: ' . $missing);
        $this->info('Notified DONE requester: ' . $notifiedDone);
        $log->setUpdated($updatedLocal);
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
        ]);
        $request->save();

        return true;
    }

    private function normalizeStatus(mixed $status): ?string
    {
        $value = mb_strtoupper(trim((string) $status));
        if ($value === '') {
            return null;
        }

        return AdsRequestStatus::tryFrom($value)?->value;
    }
}
