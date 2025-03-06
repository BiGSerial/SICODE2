<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Centerjob extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'center',
        'deposit',
        'centerjob',
    ];

    public function Company()
    {
        return $this->belongsTo(Company::class);
    }
}
