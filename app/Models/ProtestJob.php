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
        'status',
        'priority',
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
    }

    protected $appends = [
        'status_label',
        'status_badge_class',
        'priority_label',
        'priority_badge_class',
    ];

    /* ===================== ACCESSORS ===================== */

    public function getStatusLabelAttribute(): string
    {
        return $this->status->label();
    }

    public function getStatusBadgeClassAttribute(): string
    {
        return $this->status->badgeClass();
    }

    public function getPriorityLabelAttribute(): string
    {
        return $this->priority->label();
    }

    public function getPriorityBadgeClassAttribute(): string
    {
        return $this->priority->badgeClass();
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
        ProtestJobStatus::CANCELED->value    => [],
        ProtestJobStatus::REOPENED->value    => [ProtestJobStatus::ASSIGNED->value, ProtestJobStatus::IN_PROGRESS->value],
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
                    'closed_at' => $this->closed_at ?? now(),
                    'closed_by' => $this->closed_by ?? optional(auth()->user())->id,
                ],

                ProtestJobStatus::REOPENED => [
                    'closed_at'   => null,
                    'closed_by'   => null,
                    'finished_at' => null,
                ],
            };

            // proteção contra corrida de estado concorrente
            $original = $this->getOriginal('status');
            if ($original !== $this->status->value) {
                throw new \RuntimeException('Status alterado em paralelo. Recarregue e tente novamente.');
            }

            // atualiza o job
            $this->fill(array_merge(
                ['status' => $to],
                $stamps,
                $extra
            ));
            $this->save();

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

    public function finish(array $outcome = []): void
    {
        $extra = [];
        if ($outcome) {
            $extra['outcome'] = $outcome;
        }

        $this->transitionTo(ProtestJobStatus::DONE, $extra);
    }

    public function cancel(?string $reason = null): void
    {
        $this->transitionTo(ProtestJobStatus::CANCELED, [
            'reason' => $reason,
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
