<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RamalReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'note_id',
        'company_id',
        'user_id',
        'date',
        'equipment',
        'connection',
        'observation',
        'retry',
    ];

    public function Note()
    {
        return $this->belongsTo(Note::class);
    }

    public function Company()
    {
        return $this->belongsTo(Company::class);
    }

    public function BtzeroEquipment()
    {
        return $this->hasMany(BtzeroEquipment::class);
    }
}
