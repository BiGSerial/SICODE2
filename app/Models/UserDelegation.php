<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class UserDelegation extends Model
{
    use HasUuids;

    protected $table   = 'user_delegations';
    protected $guarded = []; // se preferir, troque por fillable

    protected $casts = [
        'valid_from' => 'datetime',
        'valid_to'   => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /** Titular (quem está de férias/licença) */
    public function principal(): BelongsTo
    {
        return $this->belongsTo(User::class, 'principal_id');
    }

    /** Delegado (quem cobre o titular) */
    public function delegate(): BelongsTo
    {
        return $this->belongsTo(User::class, 'delegate_id');
    }

    /*-------------------------
    | Scopes de consulta
    *------------------------*/

    /** Vigentes “agora” (valid_to nulo ou futuro) */
    public function scopeActive($q)
    {
        return $q->where('valid_from', '<=', now())
                 ->where(function ($q) {
                     $q->whereNull('valid_to')
                       ->orWhere('valid_to', '>=', now());
                 });
    }

    /** Por delegado específico */
    public function scopeForDelegate($q, string $delegateId)
    {
        return $q->where('delegate_id', $delegateId);
    }

    /** Por titular específico */
    public function scopeForPrincipal($q, string $principalId)
    {
        return $q->where('principal_id', $principalId);
    }

    /** Delegações em vigor para este delegado, neste instante */
    public function scopeCurrentForDelegate($q, string $delegateId)
    {
        return $q->forDelegate($delegateId)->active();
    }

    /**
     * Delegações que sobrepõem um intervalo (qualquer interseção).
     * Ex.: ->overlaps('2025-11-01', '2025-11-30')
     */
    public function scopeOverlaps($q, Carbon|string $from, Carbon|string $to)
    {
        $from = $from instanceof Carbon ? $from : Carbon::parse($from);
        $to   = $to   instanceof Carbon ? $to : Carbon::parse($to);

        return $q->where('valid_from', '<=', $to)
                 ->where(function ($q) use ($from) {
                     $q->whereNull('valid_to')
                       ->orWhere('valid_to', '>=', $from);
                 });
    }

    /*-------------------------
    | Helpers de negócio
    *------------------------*/

    /** Abre/atualiza uma delegação (upsert simples por trio principal/delegate/data-início) */
    public static function start(
        string $principalId,
        string $delegateId,
        Carbon|string|null $from = null,
        ?string $reason = null
    ): self {
        $from = $from ? ($from instanceof Carbon ? $from : Carbon::parse($from)) : now();

        return static::updateOrCreate(
            [
                'principal_id' => $principalId,
                'delegate_id'  => $delegateId,
                'valid_from'   => $from,
            ],
            [
                'reason'     => $reason,
                'valid_to'   => null,
            ]
        );
    }

    /** Encerra a delegação (define valid_to) */
    public function end(Carbon|string|null $to = null): self
    {
        $this->valid_to = $to ? ($to instanceof Carbon ? $to : Carbon::parse($to)) : now();
        $this->save();

        return $this;
    }

    /** Encerra *todas* as delegações ativas entre principal→delegate */
    public static function endAllBetween(string $principalId, string $delegateId, Carbon|string|null $to = null): int
    {
        $to = $to ? ($to instanceof Carbon ? $to : Carbon::parse($to)) : now();

        return static::where('principal_id', $principalId)
            ->where('delegate_id', $delegateId)
            ->active()
            ->update(['valid_to' => $to]);
    }
}
