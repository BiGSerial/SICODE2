<?php

namespace App\Models;

use App\Services\Audit\AuditPayload;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Audit extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'action',
        'before',
        'after',
        'model_class',
        'note_id',
        'service_id',
        'record_id',
        'changes_count',
        'changed_fields',
    ];

    protected static function booted(): void
    {
        // Contexto indexável extraído do JSON na gravação: é o que a tela de auditoria filtra (ver AuditPayload).
        static::creating(function (Audit $audit): void {
            if ($audit->note_id === null && $audit->service_id === null && $audit->record_id === null) {
                $audit->fill(AuditPayload::context($audit->before, $audit->after, $audit->action));
            }
        });
    }

    public function User()
    {
        return $this->belongsTo(User::class)->withTrashed();
    }
}
