<?php

namespace App\Models;

use Illuminate\Database\Eloquent\{Builder, Model};
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Informe (final ou parcial) que entrou sem saldo no PEP e aguarda tratamento da Engenharia.
 */
class PepBalanceAlert extends Model
{
    public const TYPE_WORK_REPORT = 'work_report';

    public const TYPE_PARTIAL = 'partial';

    public const REASON_NO_BALANCE = 'no_balance';

    public const REASON_ADS_EXCEEDS = 'ads_exceeds';

    public const STATUS_OPEN = 'open';

    public const STATUS_HANDLED = 'handled';

    public const VIA_ENGINEER = 'engineer';

    public const VIA_AUTO = 'auto';

    protected $fillable = [
        'informe_type',
        'informe_id',
        'note_id',
        'company_id',
        'scope',
        'region',
        'reason',
        'balances',
        'status',
        'covered_since',
        'handled_at',
        'handled_by',
        'handled_via',
        'handled_notes',
    ];

    protected $casts = [
        'balances'      => 'array',
        'covered_since' => 'datetime',
        'handled_at'    => 'datetime',
    ];

    public function Note(): BelongsTo
    {
        return $this->belongsTo(Note::class);
    }

    public function Company(): BelongsTo
    {
        return $this->belongsTo(Company::class)->withTrashed();
    }

    public function Handler(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handled_by');
    }

    public function informe(): ?Model
    {
        return $this->informe_type === self::TYPE_PARTIAL
            ? Partial::query()->find($this->informe_id)
            : WorkReport::query()->find($this->informe_id);
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_OPEN);
    }

    public function scopeHandled(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_HANDLED);
    }

    public function isPartial(): bool
    {
        return $this->informe_type === self::TYPE_PARTIAL;
    }

    public function reasonLabel(): string
    {
        return $this->reason === self::REASON_ADS_EXCEEDS ? 'ADS acima do saldo' : 'Sem saldo no PEP';
    }
}
