<?php

namespace App\Models\Legal;

use App\Models\Note;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class LegalDemandNoteActivityLink extends Model
{
    protected $table = 'legal_demand_note_activity_links';

    protected $fillable = [
        'legal_demand_id',
        'note_id',
        'activity_type',
        'activity_id',
        'linked_by',
        'linked_at',
        'unlinked_at',
        'unlink_reason',
        'meta',
    ];

    protected $casts = [
        'linked_at' => 'datetime',
        'unlinked_at' => 'datetime',
        'meta' => 'array',
    ];

    public function demand()
    {
        return $this->belongsTo(LegalDemand::class, 'legal_demand_id');
    }

    public function note()
    {
        return $this->belongsTo(Note::class, 'note_id');
    }

    public function linkedBy()
    {
        return $this->belongsTo(User::class, 'linked_by')->withTrashed();
    }
}

