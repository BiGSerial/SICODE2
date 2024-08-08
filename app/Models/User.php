<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens;
    use HasFactory;
    use HasUuids;
    use Notifiable;
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'Registration',
        'email',
        'password',
        'superadm',
        'admin',
        'management',
        'operator',
        'user',
        'contract',
        'first_pass',
        'bypassprod',
        'engineer',
        'onlyparner',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password'          => 'hashed',
    ];

    public function Employee()
    {
        return $this->hasOne(Employee::class);
    }

    public function Priorities()
    {
        return $this->hasMany(Priority::class);
    }

    public function Productions()
    {
        return $this->hasMany(Production::class);
    }

    public function Watchdog()
    {
        return $this->hasOne(Activeuser::class);
    }

    public function Companies()
    {
        return $this->belongsToMany(Company::class);
    }

    public function d5Return()
    {
        return $this->hasOne(D5Return::class);
    }

    public function ToServices()
    {
        return $this->hasMany(ServiceUser::class);
    }
}
