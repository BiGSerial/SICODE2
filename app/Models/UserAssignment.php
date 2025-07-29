<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserAssignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'assignable_id',
        'assignable_type',
        'started_at',
        'ended_at',
        'due_at',
        'completed',
        'responsible',
        'monitoring',
        'user',
        'transfered',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'due_at' => 'datetime',
        'completed' => 'boolean',
        'responsible' => 'boolean',
        'monitoring' => 'boolean',
        'transfered' => 'boolean',
        'user' => 'boolean',
    ];

    /**
     * Polimorphic relation to protests, med_protests etc.
     */
    public function assignable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Usuário responsável pela tarefa.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Verifica se a tarefa está atrasada.
     */
    public function isLate(): bool
    {
        return ! $this->completed && $this->due_at && now()->greaterThan($this->due_at);
    }

    public function getIsUserAttribute(): bool
    {
        return $this->user;
    }
}
