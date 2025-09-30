<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

class FiveNote extends Model
{
    use HasFactory;

    protected $fillable = [
        'note_d5',
        'note_id',
        'loc_install',
        'conjunto',
        'pep',
        'e_pep',
        'codify',
        'company_id',
        'sintoms',
        'reason',
        'description',
        'name',
        'dispatch_at',
        'visible_partner',
        'is_completed',
        'completed_at',
        'is_supervisioned',
        'supervisioned_at',
        'is_payed',
        'payed_at',
        'is_archived',
    ];

    protected $casts = [
        'dispatch_at'      => 'datetime',
        'visible_partner'  => 'boolean',
        'is_completed'      => 'boolean',
        'completed_at'      => 'datetime',
        'is_supervisioned'  => 'boolean',
        'supervisioned_at'  => 'datetime',
        'is_payed'          => 'boolean',
        'payed_at'          => 'datetime',
        'is_archived'       => 'boolean',
    ];

    public function note(): BelongsTo
    {
        return $this->belongsTo(Note::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function productions(): MorphToMany
    {
        return $this->morphToMany(
            Production::class,
            'productionable',
            'productionables',
            'productionable_id', // FK deste model (FiveNote) na pivot
            'production_id'      // FK do outro (Production) na pivot
        )->withTimestamps();
    }

    public function EvidenceFiles()
    {
        return $this->morphMany(EvidenceFile::class, 'evidenciable');
    }



}
