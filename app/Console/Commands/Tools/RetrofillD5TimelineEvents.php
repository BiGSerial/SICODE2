<?php

namespace App\Console\Commands\Tools;

use App\Models\FiveNote;
use App\Models\Production;
use App\Models\Service;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class RetrofillD5TimelineEvents extends Command
{
    protected $signature = 'sicode:retrofill-d5-timeline
                            {--apply : Persiste os eventos (padrao = dry-run)}
                            {--append : Nao remove eventos existentes da D5 antes do retrofill}
                            {--limit=0 : Limite de D5 processadas}
                            {--chunk=200 : Tamanho do lote de leitura}
                            {--five= : Processa apenas uma FiveNote especifica por ID}';

    protected $description = 'Retrofill da timeline de D5 em timeline_events com inferencia controlada para casos legados';

    private ?string $fiscalServiceId = null;
    private ?string $paymentServiceId = null;

    public function handle(): int
    {
        $apply = (bool) $this->option('apply');
        $append = (bool) $this->option('append');
        $limit = (int) $this->option('limit');
        $chunk = max(1, (int) $this->option('chunk'));
        $fiveId = $this->option('five');

        $this->resolveServiceIds();

        $query = FiveNote::query()->orderBy('id');

        if ($fiveId) {
            $query->whereKey($fiveId);
        }

        if ($limit > 0) {
            $query->limit($limit);
        }

        $processed = 0;
        $generated = 0;
        $inferred = 0;
        $withoutEvents = 0;

        $query->chunkById($chunk, function (Collection $fives) use (
            $apply,
            $append,
            &$processed,
            &$generated,
            &$inferred,
            &$withoutEvents
        ) {
            foreach ($fives as $five) {
                $events = $this->buildEventsForFive($five);
                $processed++;

                if (count($events) === 0) {
                    $withoutEvents++;
                    continue;
                }

                $generated += count($events);
                $inferred += collect($events)->where('inferred', true)->count();

                if (!$apply) {
                    continue;
                }

                DB::transaction(function () use ($five, $events, $append) {
                    if (!$append) {
                        DB::table('timeline_events')
                            ->where('five_note_id', $five->id)
                            ->delete();
                    }

                    DB::table('timeline_events')->insert($events);
                }, 1);
            }
        });

        $mode = $apply ? 'APPLY' : 'DRY-RUN';

        $this->line("Modo: {$mode}");
        $this->line('D5 processadas: '.$processed);
        $this->line('Eventos gerados: '.$generated);
        $this->line('Eventos inferidos: '.$inferred);
        $this->line('D5 sem eventos: '.$withoutEvents);
        $this->line('Servico Fiscalizacao: '.($this->fiscalServiceId ?? 'N/A'));
        $this->line('Servico Pagamento: '.($this->paymentServiceId ?? 'N/A'));

        return self::SUCCESS;
    }

    private function resolveServiceIds(): void
    {
        $this->fiscalServiceId = Service::query()
            ->whereIn('service', ['Fiscalizacao', 'Fiscalização'])
            ->value('uuid');

        $this->paymentServiceId = Service::query()
            ->where('service', 'Pagamento')
            ->value('uuid');
    }

    private function buildEventsForFive(FiveNote $five): array
    {
        $now = now();
        $noteId = (int) $five->note_id;

        $latestFiscalAssigned = $this->latestProductionForService($noteId, $this->fiscalServiceId, false);
        $latestFiscalCompleted = $this->latestProductionForService($noteId, $this->fiscalServiceId, true);
        $latestPaymentAssigned = $this->latestProductionForService($noteId, $this->paymentServiceId, false);

        $paymentAssignedAt = $this->dateFromProduction($latestPaymentAssigned, false);
        $fiscalAssignedAt = $this->dateFromProduction($latestFiscalAssigned, false);
        $fiscalCompletedAt = $this->dateFromProduction($latestFiscalCompleted, true);

        $partnerCompletedAt = $five->completed_at;
        $partnerCompletedInferred = false;
        $partnerCompletedMeta = [];

        if (!$partnerCompletedAt && (bool) $five->returned) {
            if ((bool) $five->isPassive) {
                if ($fiscalCompletedAt) {
                    $partnerCompletedAt = $fiscalCompletedAt;
                    $partnerCompletedInferred = true;
                    $partnerCompletedMeta = [
                        'rule' => 'passive_use_latest_completed_fiscal_assignment',
                        'production_id' => $latestFiscalCompleted?->id,
                    ];
                }
            } else {
                if ($fiscalAssignedAt && (!$paymentAssignedAt || $fiscalAssignedAt->gt($paymentAssignedAt))) {
                    $partnerCompletedAt = $fiscalAssignedAt;
                    $partnerCompletedInferred = true;
                    $partnerCompletedMeta = [
                        'rule' => 'returned_use_latest_fiscal_assignment_if_posterior_than_payment',
                        'payment_assigned_at' => $paymentAssignedAt?->toDateTimeString(),
                        'fiscal_assigned_at' => $fiscalAssignedAt->toDateTimeString(),
                        'production_id' => $latestFiscalAssigned?->id,
                    ];
                }
            }
        }

        $createdAt = $this->earliestDate([
            $five->created_at,
            $five->dispatch_at,
            $five->payed_at,
        ]) ?? $now;

        $events = [];
        $sequence = 0;
        $currentStage = null;

        $push = function (
            string $eventType,
            ?string $toStage,
            ?CarbonInterface $occurredAt,
            ?string $actorUserId = null,
            ?string $actorRole = null,
            ?string $ownerUserId = null,
            ?string $ownerRole = null,
            ?string $serviceId = null,
            ?int $productionId = null,
            bool $inferred = false,
            ?string $reason = null,
            ?string $comment = null,
            array $metadata = []
        ) use (&$events, &$sequence, &$currentStage, $five, $now): void {
            if (!$occurredAt) {
                return;
            }

            $sequence++;

            $events[] = [
                'five_note_id' => $five->id,
                'note_id' => $five->note_id,
                'event_type' => $eventType,
                'from_stage' => $currentStage,
                'to_stage' => $toStage,
                'actor_user_id' => $actorUserId,
                'actor_role' => $actorRole,
                'owner_user_id' => $ownerUserId,
                'owner_role' => $ownerRole,
                'service_id' => $serviceId,
                'production_id' => $productionId,
                'occurred_at' => $occurredAt->toDateTimeString(),
                'inferred' => $inferred,
                'reason' => $reason,
                'comment' => $comment,
                'metadata' => json_encode(array_merge($metadata, [
                    'sequence' => $sequence,
                ]), JSON_UNESCAPED_UNICODE),
                'created_at' => $now,
                'updated_at' => $now,
            ];

            $currentStage = $toStage ?? $currentStage;
        };

        $push(
            eventType: 'd5_created',
            toStage: 'created',
            occurredAt: Carbon::parse($createdAt),
            ownerUserId: $latestPaymentAssigned?->user_id,
            ownerRole: $latestPaymentAssigned?->user_id ? 'PAGAMENTO' : null,
            serviceId: $this->paymentServiceId,
            productionId: $latestPaymentAssigned?->id
        );

        if ($five->payed_at) {
            $push(
                eventType: 'd5_payment_updated',
                toStage: 'payment_review',
                occurredAt: Carbon::parse($five->payed_at),
                actorUserId: $latestPaymentAssigned?->user_id,
                actorRole: $latestPaymentAssigned?->user_id ? 'PAGAMENTO' : null,
                ownerUserId: $latestPaymentAssigned?->user_id,
                ownerRole: $latestPaymentAssigned?->user_id ? 'PAGAMENTO' : null,
                serviceId: $this->paymentServiceId,
                productionId: $latestPaymentAssigned?->id
            );
        }

        if ($five->dispatch_at || $five->visible_partner) {
            $push(
                eventType: 'd5_released_to_partner',
                toStage: 'released_to_partner',
                occurredAt: Carbon::parse($five->dispatch_at ?? $five->payed_at ?? $five->created_at),
                actorUserId: $latestPaymentAssigned?->user_id,
                actorRole: $latestPaymentAssigned?->user_id ? 'PAGAMENTO' : null,
                ownerRole: 'EMPREITEIRA',
                serviceId: $this->paymentServiceId,
                productionId: $latestPaymentAssigned?->id
            );
        }

        if ($partnerCompletedAt) {
            $push(
                eventType: $partnerCompletedInferred ? 'd5_partner_recompleted' : 'd5_partner_completed',
                toStage: 'partner_done',
                occurredAt: Carbon::parse($partnerCompletedAt),
                actorRole: 'EMPREITEIRA',
                ownerRole: 'FISCALIZACAO',
                inferred: $partnerCompletedInferred,
                metadata: $partnerCompletedMeta
            );

            $push(
                eventType: 'd5_sent_to_supervision_queue',
                toStage: 'supervision_queue',
                occurredAt: Carbon::parse($partnerCompletedAt),
                actorRole: 'SYSTEM',
                ownerRole: 'FISCALIZACAO',
                inferred: $partnerCompletedInferred,
                metadata: $partnerCompletedMeta
            );
        }

        if ($fiscalAssignedAt) {
            $push(
                eventType: 'd5_supervision_assigned',
                toStage: 'supervision_assigned',
                occurredAt: $fiscalAssignedAt,
                actorUserId: $latestFiscalAssigned?->user_id,
                actorRole: $latestFiscalAssigned?->user_id ? 'FISCALIZACAO' : null,
                ownerUserId: $latestFiscalAssigned?->user_id,
                ownerRole: $latestFiscalAssigned?->user_id ? 'FISCALIZACAO' : null,
                serviceId: $this->fiscalServiceId,
                productionId: $latestFiscalAssigned?->id
            );
        }

        if ((bool) $five->returned && !(bool) $five->is_supervisioned) {
            $push(
                eventType: 'd5_returned_with_pending',
                toStage: 'returned_to_partner',
                occurredAt: $fiscalCompletedAt ?? $fiscalAssignedAt ?? Carbon::parse($five->updated_at ?? $five->created_at),
                actorUserId: $latestFiscalCompleted?->user_id ?? $latestFiscalAssigned?->user_id,
                actorRole: 'FISCALIZACAO',
                ownerRole: 'EMPREITEIRA',
                serviceId: $this->fiscalServiceId,
                productionId: $latestFiscalCompleted?->id ?? $latestFiscalAssigned?->id,
                reason: 'pendencia_fiscalizacao'
            );
        }

        if ((bool) $five->is_supervisioned) {
            $push(
                eventType: 'd5_supervision_approved',
                toStage: 'supervision_approved',
                occurredAt: Carbon::parse($five->supervisioned_at ?? $fiscalCompletedAt ?? $fiscalAssignedAt ?? $five->updated_at),
                actorUserId: $latestFiscalCompleted?->user_id ?? $latestFiscalAssigned?->user_id,
                actorRole: 'FISCALIZACAO',
                ownerUserId: $latestPaymentAssigned?->user_id,
                ownerRole: $latestPaymentAssigned?->user_id ? 'PAGAMENTO' : null,
                serviceId: $this->fiscalServiceId,
                productionId: $latestFiscalCompleted?->id ?? $latestFiscalAssigned?->id
            );

            $push(
                eventType: 'd5_sent_to_payment_archive',
                toStage: 'payment_archive_queue',
                occurredAt: Carbon::parse($five->supervisioned_at ?? $fiscalCompletedAt ?? $five->updated_at),
                actorRole: 'SYSTEM',
                ownerUserId: $latestPaymentAssigned?->user_id,
                ownerRole: $latestPaymentAssigned?->user_id ? 'PAGAMENTO' : null,
                serviceId: $this->paymentServiceId,
                productionId: $latestPaymentAssigned?->id
            );
        }

        if ((bool) $five->is_archived) {
            $push(
                eventType: 'd5_archived',
                toStage: 'archived',
                occurredAt: Carbon::parse($five->updated_at ?? $five->supervisioned_at ?? $five->payed_at ?? $five->created_at),
                actorUserId: $latestPaymentAssigned?->user_id,
                actorRole: $latestPaymentAssigned?->user_id ? 'PAGAMENTO' : null,
                ownerRole: 'CLOSED',
                serviceId: $this->paymentServiceId,
                productionId: $latestPaymentAssigned?->id
            );
        }

        usort($events, function (array $a, array $b): int {
            return strcmp($a['occurred_at'], $b['occurred_at']);
        });

        return $events;
    }

    private function latestProductionForService(int $noteId, ?string $serviceId, bool $completedOnly): ?Production
    {
        if (!$serviceId) {
            return null;
        }

        $query = Production::query()
            ->where('note_id', $noteId)
            ->where('service_id', $serviceId);

        if ($completedOnly) {
            $query->where('completed', true);
        }

        return $query
            ->orderByDesc('completed_at')
            ->orderByDesc('att_at')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->first();
    }

    private function dateFromProduction(?Production $production, bool $preferCompleted): ?CarbonInterface
    {
        if (!$production) {
            return null;
        }

        if ($preferCompleted) {
            $value = $production->completed_at ?? $production->att_at ?? $production->created_at;
        } else {
            $value = $production->att_at ?? $production->created_at ?? $production->completed_at;
        }

        return $value ? Carbon::parse($value) : null;
    }

    private function earliestDate(array $values): ?CarbonInterface
    {
        $dates = collect($values)
            ->filter()
            ->map(fn ($value) => Carbon::parse($value))
            ->sort();

        return $dates->first();
    }
}

