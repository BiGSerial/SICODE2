<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\HasApiTokens;

/**
 * @property \Illuminate\Notifications\DatabaseNotification[]|\Illuminate\Database\Eloquent\Collection $notifications
 * @property \Illuminate\Notifications\DatabaseNotification[]|\Illuminate\Database\Eloquent\Collection $unreadNotifications
 */
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
        'manager_id',
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
        'company_id',
        'responsible',
        'btzero',
        'can_dispatch',
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
        'first_pass'       => 'boolean',
        'bypassprod'      => 'boolean',
        'engineer'       => 'boolean',
        'onlyparner'      => 'boolean',
    ];

    public function Employee()
    {
        return $this->hasOne(Employee::class);
    }

    public function Company()
    {
        return $this->belongsTo(Company::class)->withTrashed();
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
        return $this->belongsToMany(Company::class, 'company_user')->withTrashed();
    }

    public function d5Return()
    {
        return $this->hasOne(D5Return::class);
    }

    public function Files()
    {
        return $this->hasMany(File::class);
    }

    public function ToServices()
    {
        return $this->hasMany(ServiceUser::class);
    }

    public function Approvals()
    {
        return $this->hasMany(ViabilityApproval::class);
    }

    public function Assignments()
    {
        return $this->hasMany(UserAssignment::class);
    }


    public function UserProtest()
    {
        return $this->hasOne(ProtestUser::class);
    }

    /* -------------------
       Relações de chefia
    --------------------*/

    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    public function reports(): HasMany
    {
        return $this->hasMany(User::class, 'manager_id');
    }

    /* -------------------
       Delegações
    --------------------*/

    public function delegationsGiven(): HasMany
    {
        // Sou o titular (principal)
        return $this->hasMany(UserDelegation::class, 'principal_id');
    }

    public function delegationsReceived(): HasMany
    {
        // Sou o delegado
        return $this->hasMany(UserDelegation::class, 'delegate_id');
    }

    /* -----------------------------------------
      Helpers de hierarquia (consultas prontas)
    ------------------------------------------*/

    /**
     * Descendentes (Users) "abaixo" deste usuário (inclui ele mesmo se $includeSelf = true).
     */
    public function descendantsQuery(bool $includeSelf = true)
    {
        $q = static::query()
            ->join('user_closure as uc', 'uc.descendant_id', '=', 'users.id')
            ->where('uc.ancestor_id', $this->id);

        if (!$includeSelf) {
            $q->where('uc.depth', '>', 0);
        }

        return $q->select('users.*')->distinct();
    }

    /**
     * Verifica rapidamente se ESTE usuário pode ver $targetUserId agora
     * (considerando closure + delegações).
     */
    public function canSeeUser(string $targetUserId): bool
    {
        return DB::table('user_visibility_current')
            ->where('viewer_id', $this->id)
            ->where('descendant_id', $targetUserId)
            ->exists();
    }


}
