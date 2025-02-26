<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Manualnote extends Model
{
    use HasFactory;

    protected $fillable = [
        'note',
        'status',
        'service_id',
        'user_id',
        'solicitante',
        'setor',
        'finish_at',
        'confirmed',
        'completed',
        'cancel',
    ];

    protected $cast = [
        'finish_at' => 'datetime',
        'confirmed' => 'boolean',
        'completed' => 'boolean',
        'cancel' => 'boolean',
    ];

    public function Service()
    {
        return $this->belongsTo(Service::class, 'service_id', 'uuid');
    }

    public function User()
    {
        return $this->belongsTo(User::class)->withTrashed();
    }
}
