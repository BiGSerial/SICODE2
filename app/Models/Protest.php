<?php

namespace App\Models;

use App\Enum\ProtestType;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Support\Collection;

class Protest extends Model
{
    use HasFactory;

    protected $fillable = [
        'nota',
        'tipoNota',
        'codecodf',
        'txtGrpCodificacao',
        'dtAberturaNota',
        'dtConclusaoDesej',
        'cenPlan',
        'cidade',
        'statUsuar',
        'descCausa',
        'descSubCausa',
        'descricao',
        'resume',
        'type',
    ];

    protected $appends = ['data_final_valida'];
    // protected $appends = [];

    protected $casts = [

        'dtAberturaNota' => 'date',
        'dtConclusaoDesej' => 'date',
    ];

    public function Notes()
    {
        return $this->morphToMany(
            Note::class,
            'noteable',
            'noteables'
        )->withPivot('id');
    }

    public function medProtests()
    {
        return $this->hasMany(MedProtest::class, 'protest_id');
    }

    public function Comments()
    {
        return $this->morphMany(Comment::class, 'commentable');
    }

    public function assignments()
    {
        return $this->morphMany(UserAssignment::class, 'assignable');
    }

    public function City()
    {
        return $this->belongsTo(City::class, 'cidade', 'cidade');
    }


    public function evidenceFiles(): HasManyThrough
    {
        return $this->hasManyThrough(
            EvidenceFile::class,   // destino final
            MedProtest::class,     // intermediário
            'protest_id',          // FK em med_protests que aponta pra protests.id
            'evidenciable_id',     // FK em evidence_files que aponta pra med_protests.id
            'id',                  // PK local em protests
            'id'                   // PK local em med_protests
        )
        ->where('evidenciable_type', MedProtest::class)
        ->whereNull('evidence_files.deleted_at'); // já que EvidenceFile usa SoftDeletes
    }



    //Accessors
    protected function dataFinalValida(): Attribute
    {
        return Attribute::make(
            get: function () {

                $isInvalidated = $this->medProtests()
                    ->where('statusSist', 'MEDA')
                    ->exists();

                if ($isInvalidated) {
                    return null;
                }


                return $this->medProtests()->latest('dtFimMedida')->first()?->dtFimMedida;
            },
        );
    }

    public function getAllNotesAttribute(): Collection
    {
        $this->loadMissing('notes', 'medProtests.notes');

        return $this->notes
            ->merge($this->medProtests->flatMap->notes)
            ->unique('id')
            ->values();
    }

    public function ProtestJobs()
    {
        return $this->hasMany(ProtestJob::class);
    }


}
