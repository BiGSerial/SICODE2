<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WallScreen extends Model
{
    use HasFactory;

    protected $fillable = [
        'wall_id',
        'name',
        'screen_type',
        'enabled',
        'display_order',
        'duration_seconds',
        'service_rotation_seconds',
        'screen_config',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'wall_id' => 'integer',
        'enabled' => 'boolean',
        'display_order' => 'integer',
        'duration_seconds' => 'integer',
        'service_rotation_seconds' => 'integer',
        'screen_config' => 'array',
    ];

    public function items()
    {
        return $this->hasMany(WallScreenService::class)->orderBy('display_order')->orderBy('id');
    }

    public function wall()
    {
        return $this->belongsTo(Wall::class);
    }
}
