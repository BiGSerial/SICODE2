<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProtestJobEvent extends Model
{
    use HasFactory;

    // Tabela (caso não siga convenção)
    protected $table = 'protest_job_events';

    /**
     * Campos liberados para atribuição em massa.
     */
    protected $fillable = [
        'protest_job_id',
        'type',
        'actor_id',
        'meta',
        'occurred_at',
    ];

    /**
     * Casts de atributos.
     */
    protected $casts = [
        'meta'        => 'array',     // JSON <-> array
        'occurred_at' => 'datetime',  // carimbo de quando ocorreu (negócio)
    ];

    /**
     * Evento pertence a um ProtestJob.
     */
    public function job(): BelongsTo
    {
        return $this->belongsTo(ProtestJob::class, 'protest_job_id');
    }

    /**
     * Usuário (UUID) que realizou a ação (pode ser null para eventos automáticos).
     */
    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
