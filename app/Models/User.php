<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
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
        'avatar',
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
        'analyst',
        'permission_locks',
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

        // booleans
        'first_pass'   => 'boolean',
        'bypassprod'   => 'boolean',
        'engineer'     => 'boolean',
        'onlyparner'   => 'boolean',
        'superadm'     => 'boolean',
        'admin'        => 'boolean',
        'management'   => 'boolean',
        'operator'     => 'boolean',
        'user'         => 'boolean',
        'contract'     => 'boolean',
        'responsible'  => 'boolean',
        'btzero'       => 'boolean',
        'can_dispatch' => 'boolean',
        'analyst'      => 'boolean',
        'permission_locks' => 'array',

        ];


    protected $appends = [
        'avatar_url',
        'is_delegated',
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


    /**
     * ESCOPO ESTÁTICO
     */

    // Retorna os níveis de profundidade disponíveis para um viewerId específico
    public static function depthsAvailableFor(
        string $viewerIdOrAncestorId,
        bool $includeSelf = false,
        bool $includeDelegations = true,
        bool $includeDelegatesTreesForPrincipal = false
    ): Collection {
        $viewerIds = static::visibleAncestorIdsFor(
            $viewerIdOrAncestorId,
            $includeDelegations,
            $includeDelegatesTreesForPrincipal
        );

        return DB::table('user_visibility_current as uv')
            ->whereIn('uv.viewer_id', $viewerIds)
            ->when(!$includeSelf, fn ($q) => $q->where('uv.depth', '>', 0))
            ->distinct()
            ->orderBy('uv.depth')
            ->pluck('uv.depth')
            ->map(fn ($d) => (int) $d)
            ->values();
    }

    /** Query de usuários em um depth específico, a partir de um ancestor/viewer arbitrário */
    public static function descendantsAtLevelFor(
        string $viewerIdOrAncestorId,
        int $depth,
        bool $includeDelegations = true,
        bool $includeDelegatesTreesForPrincipal = false
    ): Builder {
        $viewerIds = static::visibleAncestorIdsFor(
            $viewerIdOrAncestorId,
            $includeDelegations,
            $includeDelegatesTreesForPrincipal
        );

        return static::query()
            ->join('user_visibility_current as uv', 'uv.descendant_id', '=', 'users.id')
            ->whereIn('uv.viewer_id', $viewerIds)
            ->where('uv.depth', $depth)
            ->when(
                in_array(SoftDeletes::class, class_uses_recursive(static::class)),
                fn ($q) => $q->whereNull('users.deleted_at')
            )
            ->select('users.*')
            ->distinct();
    }

    /** Monta o conjunto de ancestors “visíveis” para um viewer/ancestor arbitrário */
    protected static function visibleAncestorIdsFor(
        string $viewerIdOrAncestorId,
        bool $includeDelegations = true,
        bool $includeDelegatesTreesForPrincipal = false
    ): Collection {
        $ids = collect([$viewerIdOrAncestorId]);

        if ($includeDelegations) {
            // Quem delegou para este viewer
            $principalIds = UserDelegation::query()
                ->where('delegate_id', $viewerIdOrAncestorId)
                ->pluck('principal_id');
            $ids = $ids->merge($principalIds);
        }

        if ($includeDelegatesTreesForPrincipal) {
            // Delegados deste viewer (se quiser incluir as árvores deles)
            $delegateIds = UserDelegation::query()
                ->where('principal_id', $viewerIdOrAncestorId)
                ->pluck('delegate_id');
            $ids = $ids->merge($delegateIds);
        }

        return $ids->unique()->values();
    }



    public static function usersAtDepthGlobal(int $depth): Builder
    {
        $rootIds = static::query()->whereNull('manager_id')->pluck('id');

        return static::query()
            ->join('user_closure as uc', 'uc.descendant_id', '=', 'users.id')
            ->whereIn('uc.ancestor_id', $rootIds) // <-- só caminhos a partir das raízes
            ->where('uc.depth', $depth)
            ->select('users.*')
            ->distinct();
    }

    public static function depthsGlobal(bool $includeZero = false): \Illuminate\Support\Collection
    {
        return DB::table('user_closure as uc')
            ->when(!$includeZero, fn ($q) => $q->where('uc.depth', '>', 0))
            ->distinct()
            ->orderBy('uc.depth')
            ->pluck('uc.depth')
            ->map(fn ($d) => (int) $d)
            ->values();
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

    public function observationsGiven(): HasMany
    {
        return $this->hasMany(UserObservation::class, 'observer_id');
    }

    public function observationsReceived(): HasMany
    {
        return $this->hasMany(UserObservation::class, 'target_id');
    }

    public function getAvatarUrlAttribute(): string
    {
        if ($this->avatar) {
            if (Str::startsWith($this->avatar, 'dicebear:')) {
                $seed = trim(Str::after($this->avatar, 'dicebear:')) ?: $this->email;

                return 'https://api.dicebear.com/9.x/pixel-art/svg?seed=' . urlencode($seed);
            }

            if (Str::startsWith($this->avatar, ['http://', 'https://'])) {
                return $this->avatar;
            }

            if (Storage::disk('public')->exists($this->avatar)) {
                return asset('storage/' . $this->avatar);
            }
        }

        return 'https://api.dicebear.com/9.x/pixel-art/svg?seed=' . urlencode($this->email ?? 'sicode');
    }

    public function getIsDelegatedAttribute(): bool
    {
        return $this->delegationsReceived()->active()->exists();
    }

    /* -----------------------------------------
      Helpers de hierarquia (consultas prontas)
    ------------------------------------------*/

    /**
     * Descendentes (Users) "abaixo" deste usuário (inclui ele mesmo se $includeSelf = true).
     */
    public function descendantsQuery(
        bool $includeSelf = true,
        bool $includeDelegations = false,
        bool $includeDelegatesTreesForPrincipal = false
    ): Builder {
        $viewerIds = $this->visibleAncestorIds($includeDelegations, $includeDelegatesTreesForPrincipal);

        $q = static::query()
            ->join('user_visibility_current as uv', 'uv.descendant_id', '=', 'users.id')
            ->whereIn('uv.viewer_id', $viewerIds)
            ->when(!$includeSelf, fn ($q2) => $q2->where('uv.depth', '>', 0))
            ->when(in_array(SoftDeletes::class, class_uses_recursive(static::class)), fn ($q2) => $q2->whereNull('users.deleted_at'))
            ->select('users.*', 'uv.depth')
            ->distinct();

        return $q;
    }

    protected function visibleAncestorIds(bool $includeDelegations = true, bool $includeDelegatesTreesForPrincipal = false): Collection
    {
        // Começa sempre pelo próprio usuário
        $ids = collect([$this->id]);

        if ($includeDelegations) {
            // Se eu sou DELEGADO, também enxergo as árvores de quem me delegou
            // (UserDelegation: principal_id -> delegate_id = $this->id)
            $principalIds = $this->delegationsReceived()->active()->pluck('principal_id');
            $ids = $ids->merge($principalIds);
        }

        if ($includeDelegatesTreesForPrincipal) {
            // Se eu sou o DELEGADOR e quero incluir as árvores dos meus delegados
            // (UserDelegation: principal_id = $this->id -> delegate_id)
            $delegateIds = $this->delegationsGiven()->active()->pluck('delegate_id');
            $ids = $ids->merge($delegateIds);
        }

        return $ids->unique()->values();
    }


    public function depthsAvailable(bool $includeSelf = false, bool $includeDelegations = true, bool $includeDelegatesTreesForPrincipal = false): Collection
    {
        $viewerIds = $this->visibleAncestorIds($includeDelegations, $includeDelegatesTreesForPrincipal);

        return DB::table('user_visibility_current as uv')
            ->whereIn('uv.viewer_id', $viewerIds)
            ->when(!$includeSelf, fn ($q) => $q->where('uv.depth', '>', 0))
            ->distinct()
            ->orderBy('uv.depth')
            ->pluck('uv.depth');
    }

    public function descendantsAtLevel(int $depth, bool $includeDelegations = true, bool $includeDelegatesTreesForPrincipal = false): Builder
    {
        $viewerIds = $this->visibleAncestorIds($includeDelegations, $includeDelegatesTreesForPrincipal);

        return static::query()
            ->join('user_visibility_current as uv', 'uv.descendant_id', '=', 'users.id')
            ->whereIn('uv.viewer_id', $viewerIds)
            ->where('uv.depth', $depth)
            ->when(in_array(SoftDeletes::class, class_uses_recursive(static::class)), fn ($q) => $q->whereNull('users.deleted_at'))
            ->select('users.*')
            ->distinct();
    }

    /**
     * Verifica rapidamente se ESTE usuário pode ver $targetUserId agora
     * (considerando closure + delegações).
     */
    public function canSeeUser(string $targetUserId): bool
    {
        $viewerIds = $this->visibleAncestorIds(true, false);

        return DB::table('user_visibility_current')
            ->whereIn('viewer_id', $viewerIds)
            ->where('descendant_id', $targetUserId)
            ->exists();
    }

    /**
     * IDs de usuários que este usuário pode operar (si + hierarquia + delegações + observações vigentes).
     */
    public function visibleUserIdsForWork(): Collection
    {
        return $this->descendantsQuery(
            includeSelf: true,
            includeDelegations: true,
            includeDelegatesTreesForPrincipal: false
        )
            ->pluck('users.id')
            ->unique()
            ->values();
    }

}
