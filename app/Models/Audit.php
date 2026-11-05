<?php

namespace App\Models;

use App\Services\Audit\AuditPayload;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

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
            if (static::hasContextColumns() && $audit->note_id === null && $audit->service_id === null && $audit->record_id === null) {
                $audit->fill(AuditPayload::context($audit->before, $audit->after, $audit->action));
            }
        });
    }

    /** @var array{checked_at: int, has: bool}|null */
    private static ?array $contextColumns = null;

    /**
     * As colunas de contexto vêm de uma migration. Se o código subir antes dela (deploy sem banco), a gravação da auditoria não pode
     * quebrar a operação do usuário: sem as colunas, grava só o JSON como antes. Reavalia a cada 60 s para pegar a migration depois.
     */
    public static function hasContextColumns(): bool
    {
        if (self::$contextColumns === null || (!self::$contextColumns['has'] && time() - self::$contextColumns['checked_at'] > 60)) {
            self::$contextColumns = ['checked_at' => time(), 'has' => Schema::hasColumn('audits', 'note_id')];
        }

        return self::$contextColumns['has'];
    }

    public function User()
    {
        return $this->belongsTo(User::class)->withTrashed();
    }
}
