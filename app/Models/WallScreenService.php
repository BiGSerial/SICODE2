<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WallScreenService extends Model
{
    use HasFactory;

    protected $fillable = [
        'wall_screen_id',
        'service_id',
        'previous_service_id',
        'enabled',
        'use_rule_builder',
        'display_order',
    ];

    protected $casts = [
        'enabled' => 'boolean',
        'use_rule_builder' => 'boolean',
        'display_order' => 'integer',
    ];

    public function screen()
    {
        return $this->belongsTo(WallScreen::class, 'wall_screen_id');
    }

    public function service()
    {
        return $this->belongsTo(Service::class, 'service_id', 'uuid');
    }

    public function previousService()
    {
        return $this->belongsTo(Service::class, 'previous_service_id', 'uuid');
    }
}
