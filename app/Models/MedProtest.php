<?php

namespace App\Models;

use App\Enum\ProtestType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

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

    protected $appends = ['protest_type_label', 'protest_type_badge_class', 'protest_classification_label'];

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
        return $this->protest_classification_label;
    }

    public function getProtestTypeBadgeClassAttribute(): string
    {
        return $this->isConstructionMeasure() ? 'badge bg-primary' : 'badge bg-warning text-dark';
    }

    public function getProtestClassificationLabelAttribute(): string
    {
        return $this->isConstructionMeasure() ? 'Construção' : 'CIP';
    }

    public function isConstructionMeasure(): bool
    {
        return in_array($this->normalizedMeasureCode(), ProtestMeasureCodeClassification::constructionCodes(), true);
    }

    protected function normalizedMeasureCode(): string
    {
        return mb_strtoupper(trim((string) $this->codMedida));
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

    public function scopeIdentifiedAsConstruction(Builder $query): Builder
    {
        $constructionCodes = ProtestMeasureCodeClassification::constructionCodes();

        if (empty($constructionCodes)) {
            return $query->whereRaw('1 = 0');
        }

        return $query->whereIn(DB::raw('UPPER(TRIM(med_protests.codMedida))'), $constructionCodes);
    }

    public function scopeNotIdentifiedAsConstruction(Builder $query): Builder
    {
        $constructionCodes = ProtestMeasureCodeClassification::constructionCodes();

        return $query->where(function (Builder $q) use ($constructionCodes) {
            $q->whereNull('med_protests.codMedida')
                ->orWhere(DB::raw('TRIM(med_protests.codMedida)'), '');

            if (!empty($constructionCodes)) {
                $q->orWhereNotIn(DB::raw('UPPER(TRIM(med_protests.codMedida))'), $constructionCodes);
            } else {
                $q->orWhereNotNull('med_protests.codMedida');
            }
        });
    }

    public function scopeIdentifiedAsBtzero(Builder $query): Builder
    {
        return $query->identifiedAsConstruction();
    }

    public function scopeNotIdentifiedAsBtzero(Builder $query): Builder
    {
        return $query->notIdentifiedAsConstruction();
    }

}
