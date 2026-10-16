<?php

namespace App\Models\Closure;

use App\Models\{Company, EvidenceFile, Note, Order, User};
use Illuminate\Database\Eloquent\{Builder, Model};

class ClosureWorkMessage extends Model
{
    public const ROLE_INTERNAL   = 'INTERNAL';
    public const ROLE_DISPATCHER = 'DISPATCHER';
    public const ROLE_PARTNER    = 'PARTNER';

    public const VISIBILITY_INTERNAL_ONLY = 'INTERNAL_ONLY';
    public const VISIBILITY_WITH_PARTNER  = 'WITH_PARTNER';

    protected $fillable = [
        'note_id',
        'closure_issue_id',
        'order_id',
        'author_id',
        'author_company_id',
        'author_role',
        'body',
        'visibility',
    ];

    public function Note()
    {
        return $this->belongsTo(Note::class);
    }

    public function Issue()
    {
        return $this->belongsTo(ClosureIssue::class, 'closure_issue_id');
    }

    public function Order()
    {
        return $this->belongsTo(Order::class);
    }

    public function Author()
    {
        return $this->belongsTo(User::class, 'author_id')->withTrashed();
    }

    public function AuthorCompany()
    {
        return $this->belongsTo(Company::class, 'author_company_id')->withTrashed();
    }

    public function EvidenceFiles()
    {
        return $this->morphMany(EvidenceFile::class, 'evidenciable');
    }

    /**
     * Escopo explícito de visibilidade para a parceira: nunca devolve INTERNAL_ONLY.
     * Toda leitura voltada a usuário de parceira deve passar por aqui (ver ClosureWorkService::messages).
     */
    public function scopeVisibleToPartner(Builder $query): Builder
    {
        return $query->where('visibility', self::VISIBILITY_WITH_PARTNER);
    }
}
