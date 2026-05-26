<?php

namespace App\Models\Legal;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LegalExternalContact extends Model
{
    use HasFactory;

    protected $table = 'legal_external_contacts';

    protected $fillable = [
        'name',
        'email',
        'created_by',
        'last_used_at',
        'is_active',
    ];

    protected $casts = [
        'last_used_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by')->withTrashed();
    }
}

