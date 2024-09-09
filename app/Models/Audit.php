<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Audit extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'action',
        'before',
        'after',
        'model_class',
    ];

    public function User()
    {
        return $this->belongsTo(User::class)->withTrashed();
    }
}
