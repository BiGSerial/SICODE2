<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Entity extends Model
{
    use HasFactory;

    protected $fillable = [
        'entity_type_id',
        'name',
        'approve',
        'eon',
        'cad',
        'map',
        'docs',
        'observations',
        'nick',
    ];

    protected $casts = [
        'docs' => 'array',
        'approve' => 'boolean',
        'eon'     => 'boolean',
        'cad'     => 'boolean',
        'map'     => 'boolean',
    ];

    public function type()
    {
        return $this->belongsTo(EntityType::class, 'entity_type_id');
    }

    public function contacts()
    {
        return $this->hasMany(EntityContact::class);
    }


}
