<?php

namespace App\Models\Closure;

use App\Models\{Note, User};
use Illuminate\Database\Eloquent\{Builder, Model};

/**
 * RI (Retorno Interno) enviado ao Desenho: liga a Nota do Encerramento ao Reclaim do fluxo existente. Vale para todas as Ordens da Nota.
 * `reclaim_id` não tem FK de propósito (reclaims é compartilhada com Viabilidade/Desenho). `closed_at` libera o índice de "um ativo por Nota".
 */
class ClosureInternalReturn extends Model
{
    public const STATUS_SENT      = 'SENT';
    public const STATUS_RETURNED  = 'RETURNED';
    public const STATUS_CANCELLED = 'CANCELLED';

    protected $fillable = [
        'note_id',
        'reclaim_id',
        'design_service_id',
        'status',
        'cycle',
        'message',
        'sent_by',
        'sent_at',
        'returned_at',
        'cancelled_by',
        'cancelled_at',
        'cancel_reason',
        'closed_at',
    ];

    protected $casts = [
        'cycle'        => 'integer',
        'sent_at'      => 'datetime',
        'returned_at'  => 'datetime',
        'cancelled_at' => 'datetime',
        'closed_at'    => 'datetime',
    ];

    /** RI que trava as Ordens da Nota (enquanto não retornou nem foi cancelado). */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_SENT)->whereNull('closed_at');
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_SENT && $this->closed_at === null;
    }

    public function Note()
    {
        return $this->belongsTo(Note::class);
    }

    public function SentBy()
    {
        return $this->belongsTo(User::class, 'sent_by')->withTrashed();
    }

    public function CancelledBy()
    {
        return $this->belongsTo(User::class, 'cancelled_by')->withTrashed();
    }
}
