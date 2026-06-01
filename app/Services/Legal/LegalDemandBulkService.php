<?php

namespace App\Services\Legal;

use App\Models\Legal\LegalDemand;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class BulkResult
{
    public int $applied = 0;

    public int $skipped = 0;

    public array $errors = [];
}

class LegalDemandBulkService
{
    public function __construct(private LegalDemandWorkflowService $workflow)
    {
    }

    public function transferAllFromUser(User $fromUser, User $toUser, User $actor): BulkResult
    {
        $result = new BulkResult();

        $demands = LegalDemand::query()
            
            ->where('controller_user_id', $fromUser->id)
            ->whereNotIn('internal_status', ['closed_internal', 'closed_external', 'cancelled', 'ignored'])
            ->get();

        DB::transaction(function () use ($demands, $toUser, $actor, $result) {
            foreach ($demands as $demand) {
                try {
                    $demand->update(['controller_user_id' => $toUser->id]);
                    $demand->events()->create([
                        'event_type'  => 'controller_reassigned',
                        'description' => "Controlador transferido de {$demand->controller?->name} para {$toUser->name}.",
                        'actor_id'    => $actor->id,
                        'occurred_at' => now(),
                    ]);
                    $result->applied++;
                } catch (\Throwable $e) {
                    $result->skipped++;
                    $result->errors[$demand->id] = $e->getMessage();
                }
            }
        });

        return $result;
    }

    public function sendBatchToField(array $demandIds, User $actor, ?int $toUserId, ?string $toTeamId, ?string $message, ?string $dueAt): BulkResult
    {
        $result = new BulkResult();

        $demands = LegalDemand::whereIn('id', array_slice($demandIds, 0, 100))->get();

        DB::transaction(function () use ($demands, $actor, $toUserId, $toTeamId, $message, $dueAt, $result) {
            foreach ($demands as $demand) {
                try {
                    $this->workflow->sendToField(
                        $demand,
                        $actor,
                        $toUserId ? (string) $toUserId : null,
                        $toTeamId ? (string) $toTeamId : null,
                        $message,
                        $dueAt ? new \DateTime($dueAt) : null,
                    );
                    $result->applied++;
                } catch (\InvalidArgumentException $e) {
                    $result->skipped++;
                    $result->errors[$demand->id] = $e->getMessage();
                }
            }
        });

        return $result;
    }

    public function ignoreBatch(array $demandIds, User $actor, string $reason): BulkResult
    {
        $result  = new BulkResult();
        $demands = LegalDemand::whereIn('id', array_slice($demandIds, 0, 100))->get();

        $closedStatuses = ['closed_internal', 'closed_external', 'cancelled', 'ignored'];

        DB::transaction(function () use ($demands, $actor, $reason, $closedStatuses, $result) {
            foreach ($demands as $demand) {
                if (in_array($demand->internal_status->value ?? $demand->internal_status, $closedStatuses)) {
                    $result->skipped++;

                    continue;
                }

                try {
                    $fromStatus = $demand->internal_status instanceof \BackedEnum
                        ? $demand->internal_status->value
                        : $demand->internal_status;

                    $demand->update(['internal_status' => 'ignored']);
                    $demand->events()->create([
                        'event_type'  => 'ignored',
                        'description' => "Demanda ignorada. Motivo: {$reason}",
                        'actor_id'    => $actor->id,
                        'from_status' => $fromStatus,
                        'to_status'   => 'ignored',
                        'occurred_at' => now(),
                    ]);
                    $result->applied++;
                } catch (\Throwable $e) {
                    $result->skipped++;
                    $result->errors[$demand->id] = $e->getMessage();
                }
            }
        });

        return $result;
    }

    public function reassignController(array $demandIds, User $newController, User $actor): BulkResult
    {
        $result  = new BulkResult();
        $demands = LegalDemand::whereIn('id', array_slice($demandIds, 0, 100))->get();

        DB::transaction(function () use ($demands, $newController, $actor, $result) {
            foreach ($demands as $demand) {
                try {
                    $demand->update(['controller_user_id' => $newController->id]);
                    $demand->events()->create([
                        'event_type'  => 'controller_reassigned',
                        'description' => "Controlador reatribuído para {$newController->name}.",
                        'actor_id'    => $actor->id,
                        'occurred_at' => now(),
                    ]);
                    $result->applied++;
                } catch (\Throwable $e) {
                    $result->skipped++;
                    $result->errors[$demand->id] = $e->getMessage();
                }
            }
        });

        return $result;
    }

    public function closeInternalBatch(array $demandIds, User $actor, string $reason): BulkResult
    {
        $result = new BulkResult();
        $demands = LegalDemand::whereIn('id', array_slice($demandIds, 0, 200))->get();

        DB::transaction(function () use ($demands, $actor, $reason, $result) {
            foreach ($demands as $demand) {
                try {
                    $this->workflow->closeInternal($demand, $actor, $reason);
                    $result->applied++;
                } catch (\Throwable $e) {
                    $result->skipped++;
                    $result->errors[$demand->id] = $e->getMessage();
                }
            }
        });

        return $result;
    }
}
