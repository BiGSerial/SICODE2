<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EntityContact extends Model
{
    use HasFactory;

    protected $fillable = [
        'entity_id',
        'name',
        'email',
        'url',
        'user',
        'password',
    ];

    public function entity()
    {
        return $this->belongsTo(Entity::class);
    }
}
