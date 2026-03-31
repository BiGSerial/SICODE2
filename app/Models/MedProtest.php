<?php

namespace App\Models;

use App\Enum\ProtestType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class MedProtest extends Model
{
    use HasFactory;

    public const RESULT_PROCEDENTE = 'procedente';
    public const RESULT_IMPROCEDENTE = 'improcedente';

    protected $fillable = [
        'protest_id',
        'med_id',
        'statusSist',
        'statMedida',
        'codMedida',
        'txtCodCodificacao',
        'txtCodMedida',
        'dtCriacaoMedida',
        'dtFimMedidaDesej',
        'dtFimMedida',
        'completed',
        'completed_at',
        'needsEvidence',
        'needsConfirmation',
        'protest_type',
        'result',
    ];

    protected $casts = [
        'dtCriacaoMedida' => 'date',
        'dtFimMedidaDesej' => 'date',
        'dtFimMedida' => 'date',
        'completed_at' => 'datetime',
        'completed' => 'boolean',
        'needsEvidence' => 'boolean',
        'needsConfirmation' => 'boolean',
        'protest_type' => ProtestType::class,
    ];

    protected $appends = ['protest_type_label', 'protest_type_badge_class'];

    public static function resultOptions(): array
    {
        return [
            self::RESULT_PROCEDENTE,
            self::RESULT_IMPROCEDENTE,
        ];
    }

    public static function normalizeResult(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = strtolower(trim($value));

        return in_array($value, self::resultOptions(), true) ? $value : null;
    }

    public function getProtestTypeLabelAttribute(): string
    {
        return $this->protest_type?->label() ?? 'Desconhecido';
    }

    public function getProtestTypeBadgeClassAttribute(): string
    {
        return $this->protest_type?->badgeClass() ?? 'badge bg-dark';
    }


    public function Notes()
    {
        return $this->morphToMany(
            Note::class,
            'noteable',
            'noteables'
        )->withPivot('id');
    }

    public function Protest()
    {
        return $this->belongsTo(Protest::class, 'protest_id');
    }

    public function Comments()
    {
        return $this->morphMany(Comment::class, 'commentable');
    }

    public function Assignments()
    {
        return $this->morphMany(UserAssignment::class, 'assignable');
    }

    public function EvidenceFiles()
    {
        return $this->morphMany(EvidenceFile::class, 'evidenciable');
    }

    public function TechnicalReport()
    {
        return $this->hasOne(TechnicalReport::class);
    }


    public function getAllNotesAttribute(): Collection
    {
        $this->loadMissing('Notes', 'Protest.Notes');

        return $this->Notes
            ->merge($this->Protest->Notes ?? collect())
            ->unique('id')
            ->values();
    }

    public function ProtestJobs()
    {
        return $this->hasMany(ProtestJob::class);
    }

    public function LastProtestJob()
    {
        return $this->hasOne(ProtestJob::class)->latestOfMany();
    }

    /**
     * Identifica registros BTZERO com fallback textual:
     * - protest_type = BTZERO
     * - txtCodMedida contendo "btzero" (normalizado)
     * - protest.txtGrpCodificacao contendo "btzero" (normalizado)
     */
    public function scopeIdentifiedAsBtzero(Builder $query): Builder
    {
        return $query->where(function (Builder $q) {
            $q->where('protest_type', ProtestType::BTZERO->value)
                ->orWhereRaw("COALESCE(REPLACE(REPLACE(LOWER(txtCodMedida), '-', ''), ' ', ''), '') LIKE '%btzero%'")
                ->orWhereHas('protest', function (Builder $protestQuery) {
                    $protestQuery->whereRaw("COALESCE(REPLACE(REPLACE(LOWER(txtGrpCodificacao), '-', ''), ' ', ''), '') LIKE '%btzero%'");
                });
        });
    }

    /**
     * Registros não classificados como BTZERO pelos mesmos critérios.
     */
    public function scopeNotIdentifiedAsBtzero(Builder $query): Builder
    {
        return $query
            ->where(function (Builder $q) {
                $q->whereNull('protest_type')
                    ->orWhere('protest_type', '!=', ProtestType::BTZERO->value);
            })
            ->whereRaw("COALESCE(REPLACE(REPLACE(LOWER(txtCodMedida), '-', ''), ' ', ''), '') NOT LIKE '%btzero%'")
            ->whereDoesntHave('protest', function (Builder $protestQuery) {
                $protestQuery->whereRaw("COALESCE(REPLACE(REPLACE(LOWER(txtGrpCodificacao), '-', ''), ' ', ''), '') LIKE '%btzero%'");
            });
    }

}
