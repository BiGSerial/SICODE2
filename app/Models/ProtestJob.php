<?php

namespace App\Models;

use App\Enum\ProtestJobPriority;
use App\Enum\ProtestJobStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class ProtestJob extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'protest_id',
        'med_protest_id',
        'created_by',
        'owner_id',
        'closed_by',
        'priority',
        'status',
        'sent_at',
        'accepted_at',
        'started_at',
        'finished_at',
        'closed_at',
        'sla_due_at',
        'sla_breached_at',
        'escalated_at',
        'escalation_level',
        'outcome',
        'close_reason',
        'notes',
        'need_evidence',
        'is_advance',
        'confirmed',
        'confirmed_at',
        'auto',
    ];

    protected $casts = [
        'status'            => ProtestJobStatus::class,
        'priority'          => ProtestJobPriority::class,

        'outcome'           => 'array',

        'sent_at'           => 'datetime',
        'accepted_at'       => 'datetime',
        'started_at'        => 'datetime',
        'finished_at'       => 'datetime',
        'closed_at'         => 'datetime',

        'sla_due_at'        => 'datetime',
        'sla_breached_at'   => 'datetime',

        'escalated_at'      => 'datetime',
        'escalation_level'  => 'integer',

        'need_evidence'     => 'boolean',
        'is_advance'        => 'boolean',
        'confirmed'         => 'boolean',
        'auto'              => 'boolean',
        'confirmed_at'      => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            if (!$model->status) {
                $model->status = ProtestJobStatus::OPENED;
                $model->sent_at ??= now();
            }

            if (!$model->priority) {
                $model->priority = ProtestJobPriority::NORMAL;
            }
        });

        static::created(function (self $model) {


            $user = User::find($model->owner_id);
            $model->events()->create([
                'type'        => 'job_created',
                'actor_id'    => $model->created_by ?? optional(auth()->user())->id,
                'meta'        => [
                    'message'  => 'Job criado para o usuário ' . ($user?->name ?? 'N/A'),
                    'status'   => $model->status->value,
                    'priority' => $model->priority->value,
                ],
                'occurred_at' => now(),
            ]);
        });
    }

    // ...

    protected $appends = [
        'status_label',
        'status_badge_class',
        'priority_label',
        'priority_badge_class',
        'status_age_days',
    ];

    /* ===================== ACCESSORS ===================== */

    protected function resolveStatusEnum(): \App\Enum\ProtestJobStatus
    {
        if ($this->status instanceof \App\Enum\ProtestJobStatus) {
            return $this->status;
        }

        if (!empty($this->attributes['status'] ?? null)) {
            return \App\Enum\ProtestJobStatus::from($this->attributes['status']);
        }

        // fallback pra não quebrar
        return \App\Enum\ProtestJobStatus::OPENED;
    }

    protected function resolvePriorityEnum(): \App\Enum\ProtestJobPriority
    {
        if ($this->priority instanceof \App\Enum\ProtestJobPriority) {
            return $this->priority;
        }

        if (!empty($this->attributes['priority'] ?? null)) {
            return \App\Enum\ProtestJobPriority::from($this->attributes['priority']);
        }

        // fallback pra registros antigos sem prioridade
        return \App\Enum\ProtestJobPriority::NORMAL;
    }

    public function getStatusLabelAttribute(): string
    {
        return $this->resolveStatusEnum()->label();
    }

    public function getStatusBadgeClassAttribute(): string
    {
        return $this->resolveStatusEnum()->badgeClass();
    }

    public function getPriorityLabelAttribute(): string
    {
        return $this->resolvePriorityEnum()->label();
    }

    public function getPriorityBadgeClassAttribute(): string
    {
        return $this->resolvePriorityEnum()->badgeClass();
    }

    public function getStatusAgeDaysAttribute(): int
    {
        $since = match ($this->resolveStatusEnum()) {
            ProtestJobStatus::OPENED => $this->sent_at,
            ProtestJobStatus::ASSIGNED => $this->accepted_at ?? $this->sent_at,
            ProtestJobStatus::IN_PROGRESS => $this->started_at ?? $this->accepted_at ?? $this->sent_at,
            ProtestJobStatus::WAITING => $this->updated_at ?? $this->started_at ?? $this->accepted_at ?? $this->sent_at,
            ProtestJobStatus::DONE => $this->finished_at ?? $this->closed_at ?? $this->updated_at,
            ProtestJobStatus::CANCELED => $this->closed_at ?? $this->updated_at,
            ProtestJobStatus::REOPENED => $this->updated_at ?? $this->sent_at,
        };

        $since = $since ?? $this->updated_at ?? $this->created_at;

        return $since ? $since->diffInDays(now()) : 0;
    }


    /* ===================== RELAÇÕES ===================== */

    public function protest()
    {
        return $this->belongsTo(Protest::class);
    }

    public function medProtest()
    {
        return $this->belongsTo(MedProtest::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function closer()
    {
        return $this->belongsTo(User::class, 'closed_by');
    }



    public function events()
    {
        return $this->hasMany(ProtestJobEvent::class, 'protest_job_id');
    }

    public function Comments()
    {
        return $this->morphMany(Comment::class, 'commentable');
    }

    /* ===================== SCOPES ===================== */

    public function scopeOpen($q)
    {
        return $q->whereIn('status', [
            ProtestJobStatus::OPENED->value,
            ProtestJobStatus::ASSIGNED->value,
            ProtestJobStatus::IN_PROGRESS->value,
            ProtestJobStatus::WAITING->value,
            ProtestJobStatus::REOPENED->value,
        ]);
    }

    public function scopeByStatus($q, ProtestJobStatus $s)
    {
        return $q->where('status', $s->value);
    }

    public function scopeWithSla($q)
    {
        return $q->whereNotNull('sla_due_at');
    }

    public function scopeOpenLike($q)
    {

        return $this->scopeOpen($q);
    }

    /* ===================== TRANSIÇÕES ===================== */

    protected static array $allowed = [
        ProtestJobStatus::OPENED->value      => [ProtestJobStatus::ASSIGNED->value, ProtestJobStatus::CANCELED->value],
        ProtestJobStatus::ASSIGNED->value    => [ProtestJobStatus::IN_PROGRESS->value, ProtestJobStatus::WAITING->value, ProtestJobStatus::CANCELED->value],
        ProtestJobStatus::IN_PROGRESS->value => [ProtestJobStatus::WAITING->value, ProtestJobStatus::DONE->value, ProtestJobStatus::CANCELED->value],
        ProtestJobStatus::WAITING->value     => [ProtestJobStatus::IN_PROGRESS->value, ProtestJobStatus::CANCELED->value],
        ProtestJobStatus::DONE->value        => [ProtestJobStatus::REOPENED->value],
        ProtestJobStatus::CANCELED->value    => [ProtestJobStatus::REOPENED->value],
        ProtestJobStatus::REOPENED->value    => [ProtestJobStatus::ASSIGNED->value, ProtestJobStatus::IN_PROGRESS->value, ProtestJobStatus::CANCELED->value, ProtestJobStatus::DONE->value],
    ];

    protected function canGo(ProtestJobStatus $to): bool
    {
        $from = $this->status->value;
        return in_array($to->value, self::$allowed[$from] ?? [], true);
    }

    protected function transitionTo(ProtestJobStatus $to, array $extra = [], ?string $changedByUserId = null): void
    {
        $from = $this->status;

        // já está no mesmo status? não faz nada
        if ($from === $to) {
            return;
        }

        // valida se pode
        if (!$this->canGo($to)) {
            throw new \DomainException("Transição inválida: {$from->value} → {$to->value}");
        }

        DB::transaction(function () use ($from, $to, $extra, $changedByUserId) {

            $stamps = match ($to) {
                ProtestJobStatus::OPENED => [
                    'sent_at' => now(),
                ],

                ProtestJobStatus::ASSIGNED => [
                    'accepted_at' => $this->accepted_at ?? now(),
                ],

                ProtestJobStatus::IN_PROGRESS => [
                    'started_at' => $this->started_at ?? now(),
                ],

                ProtestJobStatus::WAITING => [
                    // nada obrigatório
                ],

                ProtestJobStatus::DONE => [
                    'finished_at' => $this->finished_at ?? now(),
                    'closed_at'   => $this->closed_at   ?? now(),
                    'closed_by'   => $this->closed_by   ?? optional(auth()->user())->id,

                ],

                ProtestJobStatus::CANCELED => [
                    'confirmed' => true,
                    'confirmed_at' => now(),
                    'closed_at' => $this->closed_at ?? now(),
                    'closed_by' => $this->closed_by ?? optional(auth()->user())->id,
                ],

                ProtestJobStatus::REOPENED => [
                    'closed_at'   => null,
                    'closed_by'   => null,
                    'finished_at' => null,
                    'confirmed'     => false,
                    'confirmed_at'  => null,
                    'started_at'    => null,
                ],
            };

            // proteção contra corrida de estado concorrente
            $currentInDb = static::whereKey($this->getKey())->value('status');


            if ($currentInDb->value !== $this->status->value) {
                throw new \RuntimeException('Status alterado em paralelo. Recarregue e tente novamente.');
            }

            // atualiza o job
            $this->fill(array_merge(
                ['status' => $to],
                $stamps,
                $extra
            ));
            $this->save();

            if ($this->auto && !$this->confirmed && ($this->status === ProtestJobStatus::DONE)) {
                $this->confirmJob();
            }

            // loga evento
            $this->events()->create([
                'type'        => 'status_changed',
                'actor_id'    => $changedByUserId ?? optional(auth()->user())->id,
                'meta'        => [
                    'from' => $from->value,
                    'to'   => $to->value,
                ] + $extra,
                'occurred_at' => now(),
            ]);
        });
    }


    public function accept(): void
    {
        if ($this->status === ProtestJobStatus::OPENED) {
            $this->transitionTo(ProtestJobStatus::ASSIGNED);
            return;
        }

        $this->transitionTo(ProtestJobStatus::ASSIGNED);
    }

    public function start(): void
    {
        if ($this->status === ProtestJobStatus::OPENED) {
            $this->transitionTo(ProtestJobStatus::ASSIGNED);
        }

        $this->transitionTo(ProtestJobStatus::IN_PROGRESS);
    }

    public function wait(?string $reason = null): void
    {
        $this->transitionTo(ProtestJobStatus::WAITING, [
            'reason' => $reason,
        ]);
    }

    public function finish(array $outcome = [], ?string $reason = null): void
    {
        $extra = [];
        $extra['close_reason'] = $reason;

        if ($outcome) {
            $extra['outcome'] = $outcome;
        }

        $this->transitionTo(ProtestJobStatus::DONE, $extra);
    }

    public function cancel(?string $reason = null): void
    {
        $this->transitionTo(ProtestJobStatus::CANCELED, [
            'reason' => $reason,
            'close_reason' => $this->close_reason ?? $reason,
        ]);
    }

    public function reopen(?string $reason = null): void
    {

        $this->transitionTo(ProtestJobStatus::REOPENED, [
            'reason' => $reason,
        ]);
    }

    /**
     * Trocar o responsável (owner_id), resetando aceite.
     */
    public function reassignTo(string $newOwnerUuid, ?string $actorUuid = null): void
    {
        DB::transaction(function () use ($newOwnerUuid, $actorUuid) {
            $old = $this->owner_id;
            $oldOwner = $old ? User::find($old) : null;
            $newOwner = User::find($newOwnerUuid);

            $this->update([
            'owner_id'    => $newOwnerUuid,
            'accepted_at' => null,
            ]);

            $this->events()->create([
            'type'        => 'reassigned',
            'actor_id'    => $actorUuid ?? optional(auth()->user())->id,
            'meta'        => [
                'from_owner' => $old,
                'to_owner'   => $newOwnerUuid,
                'from_owner_name' => $oldOwner?->name ?? 'N/A',
                'to_owner_name'   => $newOwner?->name ?? 'N/A',
            ],
            'occurred_at' => now(),
            ]);
        });
    }
    public function confirmJob(?string $actorUuid = null, ?string $result = null): void
    {
        DB::transaction(function () use ($actorUuid, $result) {
            $normalizedResult = MedProtest::normalizeResult($result)
                ?? MedProtest::normalizeResult($this->medProtest?->result);

            if ($this->status === ProtestJobStatus::DONE && !$normalizedResult) {
                throw new \DomainException('É obrigatório informar o resultado da medida (procedente ou improcedente) para confirmar a resolução.');
            }

            $this->update([
                'confirmed' => true,
                'confirmed_at' => now(),
            ]);

            if ($normalizedResult && $this->medProtest) {
                $this->medProtest->update([
                    'result' => $normalizedResult,
                ]);
            }

            $this->events()->create([
                    'type'        => 'confirm_job',
                    'actor_id'    => $actorUuid ?? optional(auth()->user())->id,
                    'meta'        => [
                        'confirmed' => true,
                        'message' => 'Job confirmado e aceito pelo respons?vel',
                        'result' => $normalizedResult,
                    ],
                    'occurred_at' => now(),
                ]);
        });
    }



    public function alreadyWarned(string $code): bool
    {
        return $this->events()
            ->where('type', 'sla_warning')
            ->where('meta->code', $code)
            ->exists();
    }

    /**
 * Registra evento de aviso de SLA.
 */
    public function logSlaWarning(string $code, array $extra = [], ?string $actorUuid = null): void
    {
        $this->events()->create([
            'type'        => 'sla_warning',
            'actor_id'    => $actorUuid ?? optional(auth()->user())->id,
            'meta'        => array_merge(['code' => $code], $extra),
            'occurred_at' => now(),
        ]);
    }

    /**
     * Registra evento de estouro de SLA + carimba "sla_breached_at" (idempotente).
     */
    public function breachSla(?string $reason = null, ?string $actorUuid = null): void
    {
        if ($this->sla_breached_at) {
            return; // já marcado
        }

        DB::transaction(function () use ($reason, $actorUuid) {
            $this->update([
                'sla_breached_at' => now(),
            ]);

            $this->events()->create([
                'type'        => 'sla_breached',
                'actor_id'    => $actorUuid ?? optional(auth()->user())->id,
                'meta'        => array_filter(['reason' => $reason]),
                'occurred_at' => now(),
            ]);
        });
    }
}
