<?php

namespace App\Console\Commands\Update;

use App\Enum\AdsRequestStatus;
use App\Custom\RegistroJson;
use App\Models\AdsRequest;
use App\Models\SicodeSql\AdsRequest as SqlAdsRequest;
use App\Notifications\SystemNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Console\Command;
use Throwable;

class AdsRequestsSync extends Command
{
    protected $signature = 'sicode:sync_ads_requests
        {--since= : Data/hora mínima para puxar atualizações do SQL Server}
        {--chunk=1000 : Tamanho do lote}
        {--limit= : Limite de registros para pull do SQL Server}
        {--push-limit= : Limite de registros locais para reenviar ao SQL Server}
        {--no-push : Não reenviar pendências locais ao SQL Server}
        {--push-only : Apenas reenviar pendências locais ao SQL Server}
        {--dry-run : Simula sem gravar}';

    protected $description = 'Sincroniza requisições ADS entre SICODE e SQL Server.';

    public function handle(): int
    {
        $log = null;

        try {
            $since = $this->option('since') ?: now()->subDay()->toDateTimeString();
            $chunkSize = (int) $this->option('chunk') ?: 1000;
            $limit = $this->option('limit') ? (int) $this->option('limit') : null;
            $pushLimit = $this->option('push-limit') ? (int) $this->option('push-limit') : null;
            $noPush = (bool) $this->option('no-push');
            $pushOnly = (bool) $this->option('push-only');
            $dryRun = (bool) $this->option('dry-run');

            if (!$noPush) {
                $push = $this->pushLocalRequestsToSqlServer($chunkSize, $pushLimit, $dryRun);

                $this->info('Pushed local to SQL Server: ' . $push['pushed']);
                $this->info('Already in SQL Server: ' . $push['already']);
                $this->info('Push failures: ' . $push['failed']);

                foreach ($push['errors'] as $error) {
                    $this->warn($error);
                }

                if ($pushOnly) {
                    return $push['failed'] > 0 ? self::FAILURE : self::SUCCESS;
                }
            }

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
            $this->error($e->getMessage());

            if ($log instanceof RegistroJson) {
                $log->setErrorMessage($e->getMessage());
                $log->fail($e->getMessage());
            }

            return self::FAILURE;
        }
    }

    private function pushLocalRequestsToSqlServer(int $chunkSize, ?int $limit, bool $dryRun): array
    {
        $statuses = $this->activeStatuses();
        $base = AdsRequest::query()
            ->with(['note:id,note', 'company:id,name', 'requestedBy:id,name,email,Registration'])
            ->whereIn('status', $statuses)
            ->orderBy('id');

        if ($limit) {
            $base->limit($limit);
        }

        $pushed = 0;
        $already = 0;
        $failed = 0;
        $errors = [];

        $processed = 0;

        $base->chunkById($chunkSize, function ($requests) use (&$pushed, &$already, &$failed, &$errors, &$processed, $limit, $dryRun) {
            $sqlRows = $dryRun ? collect() : $this->loadSqlRowsBySicodeIds($requests->pluck('id'));

            foreach ($requests as $request) {
                if ($limit !== null && $processed >= $limit) {
                    return false;
                }

                $processed++;
                $existing = $sqlRows->get($request->id);

                if ($existing) {
                    $already++;
                    $this->applySqlRowToLocalRequest($request, $existing, $dryRun);
                    continue;
                }

                if ($dryRun) {
                    $pushed++;
                    continue;
                }

                $error = null;
                if ($this->mirrorToSqlServer($request, $error)) {
                    $pushed++;
                } else {
                    $failed++;
                    if (count($errors) < 10) {
                        $noteNumber = $request->note?->note ?? (string) $request->note_id;
                        $errors[] = "Falha #{$request->id} nota={$noteNumber}: {$error}";
                    }
                }
            }
        });

        return compact('pushed', 'already', 'failed', 'errors');
    }

    private function loadSqlRowsBySicodeIds($ids)
    {
        $ids = collect($ids)->filter()->unique()->values();
        if ($ids->isEmpty()) {
            return collect();
        }

        $rows = collect();
        foreach ($ids->chunk(1800) as $chunk) {
            $rows = $rows->merge(
                SqlAdsRequest::query()
                    ->whereIn('sicode_id', $chunk->all())
                    ->get(['id', 'sicode_id', 'status', 'attempts', 'description', 'url', 'completed_at', 'updated_at'])
            );
        }

        return $rows->keyBy('sicode_id');
    }

    private function mirrorToSqlServer(AdsRequest $request, ?string &$error = null): bool
    {
        try {
            $user = $request->requestedBy;
            $company = $request->company;
            $noteNumber = $request->note?->note ?? (string) $request->note_id;
            $status = $request->status instanceof AdsRequestStatus
                ? $request->status->value
                : ($this->normalizeStatus($request->status) ?? AdsRequestStatus::QUEUED->value);

            $payload = [
                'batch_id' => $request->batch_id,
                'note' => $noteNumber,
                'company' => $company?->name,
                'status' => $status,
                'attempts' => $request->attempts ?? 0,
                'partner' => $request->partner ? 1 : 0,
                'register' => $user?->Registration,
                'user' => $user?->name,
                'email' => $user?->email,
                'description' => $request->description,
                'completed_at' => $request->completed_at,
                'created_at' => $request->created_at,
                'updated_at' => $request->updated_at,
            ];

            $sqlTable = DB::connection('sqlsrv2')->table('dbo.ads_requests');

            if ($sqlTable->where('sicode_id', $request->id)->exists()) {
                $sqlTable->where('sicode_id', $request->id)->update($payload);
            } else {
                $sqlTable->insert(array_merge(['sicode_id' => $request->id], $payload));
            }

            $sqlRow = SqlAdsRequest::query()
                ->where('sicode_id', $request->id)
                ->latest('updated_at')
                ->first();

            if ($sqlRow) {
                $this->applySqlRowToLocalRequest($request, $sqlRow, false);
            } else {
                $request->forceFill(['last_error' => null])->save();
            }

            return true;
        } catch (Throwable $exception) {
            report($exception);
            $error = $exception->getMessage();

            $request->forceFill([
                'attempts' => (int) $request->attempts + 1,
                'last_error' => mb_substr($exception->getMessage(), 0, 1000),
                'next_retry_at' => now()->addMinutes(30),
            ])->save();

            return false;
        }
    }

    private function applySqlRowToLocalRequest(AdsRequest $request, $sqlRow, bool $dryRun): bool
    {
        $sqlStatus = $this->normalizeStatus($sqlRow->status)
            ?? ($request->status instanceof AdsRequestStatus ? $request->status->value : AdsRequestStatus::QUEUED->value);

        $request->fill([
            'status' => $sqlStatus,
            'attempts' => (int) ($sqlRow->attempts ?? 0),
            'description' => $sqlRow->description,
            'url' => $sqlRow->url,
            'completed_at' => $sqlRow->completed_at,
            'sqlserver_id' => $sqlRow->id,
            'completed' => $sqlStatus === AdsRequestStatus::DONE->value,
            'last_error' => null,
            'updated_at' => $sqlRow->updated_at,
        ]);

        if (!$request->isDirty()) {
            return false;
        }

        if (!$dryRun) {
            $request->timestamps = false;
            $request->save();
        }

        return true;
    }

    private function activeStatuses(): array
    {
        return collect(AdsRequestStatus::cases())
            ->map(fn (AdsRequestStatus $status) => $status->value)
            ->reject(fn (string $status) => in_array($status, [
                AdsRequestStatus::DONE->value,
                AdsRequestStatus::CANCELED->value,
                AdsRequestStatus::FAILED->value,
            ], true))
            ->values()
            ->all();
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
