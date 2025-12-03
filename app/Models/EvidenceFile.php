<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EvidenceFile extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'user_id','original_name','stored_name','disk','path','mime', 'extension','size','sha256','uploaded_at', 'origin'
    ];

    protected $casts = [
        'uploaded_at' => 'datetime',
        'size'        => 'integer',
    ];

    public function evidenciable()
    {
        return $this->morphTo();
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

