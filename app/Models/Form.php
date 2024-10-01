<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Form extends Model
{
    use HasFactory;

    protected $fillable = [
        'viability_id',
        'user_id',
        'reason',
        'description',
        'changes',
        'responsible',
        'rejected',
        'approved',
        'historic',
    ];

    public function Files()
    {
        return $this->belongsToMany(File::class);
    }

    public function Viability()
    {
        return $this->belongsTo(Viability::class);
    }

    public function User()
    {
        return $this->belongsTo(User::class);
    }
}
