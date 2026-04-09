<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Wall extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'enabled',
        'display_order',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'enabled' => 'boolean',
        'display_order' => 'integer',
    ];

    public function screens()
    {
        return $this->hasMany(WallScreen::class)->orderBy('display_order')->orderBy('id');
    }
}
