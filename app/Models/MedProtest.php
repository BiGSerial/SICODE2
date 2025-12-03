<?php

namespace App\Models;

use App\Enum\ProtestType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class MedProtest extends Model
{
    use HasFactory;

    protected $fillable = [
        'protest_id',
        'med_id',
        'statusSist',
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

}
