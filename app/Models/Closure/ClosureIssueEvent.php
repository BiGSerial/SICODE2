<?php

namespace App\Models\Closure;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use LogicException;

class ClosureIssueEvent extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'closure_issue_id',
        'actor_id',
        'type',
        'origin',
        'before_status',
        'after_status',
        'meta',
    ];

    protected $casts = ['meta' => 'array'];

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('Eventos de pendência são imutáveis.'));
        static::deleting(fn () => throw new LogicException('Eventos de pendência são imutáveis.'));
    }

    public function Issue()
    {
        return $this->belongsTo(ClosureIssue::class, 'closure_issue_id');
    }

    public function Actor()
    {
        return $this->belongsTo(User::class, 'actor_id')->withTrashed();
    }
}
