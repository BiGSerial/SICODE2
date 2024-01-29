<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Ramsey\Uuid\Uuid;

class Service extends Model
{
    use HasFactory;

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->uuid = Uuid::uuid4()->toString();
        });
    }

    protected $fillable = [
        'service',
        'status',
        'folder'
    ];



    public function Contracts()
    {
        return $this->belongsToMany(Contract::class, 'service_contract_rules')
                    ->withPivot(['posts', 'qtd', 'days', 'dispatch'])
                    ->withTimestamps();
    }

    public function Status()
    {
        return $this->hasMany(AuxiliarService::class, 'service_id', 'uuid');
    }
}
