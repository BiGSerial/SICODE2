<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Protocol extends Model
{
    use HasFactory;

    protected $fillable = [
        'external_id',
        'protocol',
        'description',
    ];

    public function External()
    {
        return $this->belongsTo(External::class);
    }




}
