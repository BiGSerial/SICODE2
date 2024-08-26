<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExternalComment extends Model
{
    use HasFactory;

    protected $fillable = [
        'external_id',
        'user_id',
        'title',
        'comment',
    ];

    public function External()
    {
        return $this->belongsTo(External::class);
    }

    public function User()
    {
        return $this->belongsTo(User::class)->withTrashed();
    }

}
