<?php

namespace App\Models\Closure;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class ClosureSetting extends Model
{
    protected $fillable = [
        'key',
        'value',
        'updated_by',
    ];

    protected $casts = [
        'value' => 'json',
    ];

    public function UpdatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by')->withTrashed();
    }
}
