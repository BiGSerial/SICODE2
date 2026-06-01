<?php

namespace App\Models\Legal;

use App\Models\Note;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class LegalDemandNoteInstruction extends Model
{
    protected $table = 'legal_demand_note_instructions';

    protected $fillable = [
        'legal_demand_id',
        'note_id',
        'created_by',
        'instruction',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];

    public function demand()
    {
        return $this->belongsTo(LegalDemand::class, 'legal_demand_id');
    }

    public function note()
    {
        return $this->belongsTo(Note::class, 'note_id');
    }

    public function author()
    {
        return $this->belongsTo(User::class, 'created_by')->withTrashed();
    }
}

