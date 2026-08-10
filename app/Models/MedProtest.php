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

    public const CONSTRUCTION_MEASURE_CODES = [
        'AL36',
        'CA03',
        'CC05',
        'CP08',
        'DE01',
        'DE12',
        'DE14',
        'EG02',
        'EG03',
        'EL06',
        'IR01',
        'MR09',
        'NB01',
        'NB12',
        'OU03',
        'OU08',
        'OU15',
        'OU16',
        'OU44',
        'OU47',
        'OU53',
        'OU80',
        'PL01',
        'RA01',
        'SA02',
        'SA07',
        'SA10',
        'SU01',
        'SU12',
        'SU14',
        'SU16',
        'SU27',
        'SU29',
    ];

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
        return in_array($this->normalizedMeasureCode(), self::CONSTRUCTION_MEASURE_CODES, true);
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
        return $query->whereIn(DB::raw('UPPER(TRIM(med_protests.codMedida))'), self::CONSTRUCTION_MEASURE_CODES);
    }

    public function scopeNotIdentifiedAsConstruction(Builder $query): Builder
    {
        return $query->where(function (Builder $q) {
            $q->whereNull('med_protests.codMedida')
                ->orWhereNotIn(DB::raw('UPPER(TRIM(med_protests.codMedida))'), self::CONSTRUCTION_MEASURE_CODES);
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
