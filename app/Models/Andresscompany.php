<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Andresscompany extends Model
{
    use HasFactory;

    protected $fillable = [
        'street',
        'city',
        'uf',
        'complement',
        'company_id',

    ];

    public function Company()
    {
        return $this->belongsTo(Company::class)->withTrashed();
    }
}
