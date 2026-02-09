<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\{Model, SoftDeletes};

class Company extends Model
{
    use HasFactory;
    use HasUuids;
    use SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'telephone',
        'img_b_path',
        'img_w_path',
        'img_rb_path',
        'img_rw_path',
    ];

    public function Address()
    {
        return $this->hasMany(Andresscompany::class);
    }

    public function contracts()
    {
        return $this->hasMany(Contract::class);
    }

    public function Viabilies()
    {
        return $this->hasMany(Viability::class);
    }

    public function toUsers()
    {
        return $this->hasMany(User::class);
    }

    public function Users()
    {
        return $this->belongsToMany(User::class, 'company_user')->withTrashed();
    }

    public function Centerjobs()
    {
        return $this->hasMany(Centerjob::class);
    }

    public function WorkReports()
    {
        return $this->hasMany(WorkReport::class);
    }

    public function scopeLinkedToService(Builder $query, string $serviceUuid): Builder
    {
        return $query->whereHas('contracts.services', function (Builder $serviceQuery) use ($serviceUuid) {
            $serviceQuery->where('services.uuid', $serviceUuid);
        });
    }
}
