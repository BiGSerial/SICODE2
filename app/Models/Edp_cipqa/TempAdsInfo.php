<?php

namespace App\Models\Edp_cipqa;

use App\Models\Note;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TempAdsInfo extends Model
{
    use HasFactory;

    protected $fillable = [
        'note_id',
        'company_name',
        'from',
        'sended_at',
        'note',
    ];

    protected $casts = [
        'sended_at'  => 'date',
    ];

    public function Note()
    {
        return $this->belongsTo(Note::class);
    }
}
