<?php

namespace App\Models;

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
    ];

    public function Address()
    {
        return $this->hasMany(Andresscompany::class);
    }

    public function contracts()
    {
        return $this->hasMany(Contract::class);
    }

    public function Users()
    {
        return $this->belongsToMany(User::class, 'company_user')->withTrashed();
    }
}
