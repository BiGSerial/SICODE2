<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\{Model, SoftDeletes};
use Illuminate\Support\Facades\Storage;

class Company extends Model
{
    use HasFactory;
    use HasUuids;
    use SoftDeletes;

    protected $fillable = [
        'parent_id',
        'name',
        'email',
        'telephone',
        'partner_user_inactivity_days',
        'img_b_path',
        'img_w_path',
        'img_rb_path',
        'img_rw_path',
    ];

    protected $casts = [
        'partner_user_inactivity_days' => 'integer',
    ];

    public function Address()
    {
        return $this->hasMany(Andresscompany::class);
    }

    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_id')->withTrashed();
    }

    public function branches()
    {
        return $this->hasMany(self::class, 'parent_id')->withTrashed();
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

    public function getLogoUrlAttribute(): string
    {
        if ($this->img_rw_path && Storage::disk('public')->exists($this->img_rw_path)) {
            return Storage::disk('public')->url($this->img_rw_path);
        }

        if ($this->parent?->img_rw_path && Storage::disk('public')->exists($this->parent->img_rw_path)) {
            return Storage::disk('public')->url($this->parent->img_rw_path);
        }

        return asset('img/edp-img/edp-avatar.jpg');
    }

    public function getDisplayNameAttribute(): string
    {
        if (!$this->parent) {
            return $this->name;
        }

        return "{$this->parent->name} / {$this->name}";
    }

    public function getIsBranchAttribute(): bool
    {
        return (bool) $this->parent_id;
    }

    public function getIsConcentratorAttribute(): bool
    {
        return !$this->parent_id && $this->branches->isNotEmpty();
    }

    public function scopeRoots(Builder $query): Builder
    {
        return $query->whereNull('parent_id');
    }

    public function partnerRole()
    {
        return $this->hasOne(PartnerRole::class);
    }

    public function partnerPermissionGrants()
    {
        return $this->hasMany(PartnerCompanyPermissionGrant::class);
    }

    public function partnerUserBranches()
    {
        return $this->hasMany(PartnerUserBranch::class);
    }

    public function scopeLinkedToService(Builder $query, string $serviceUuid): Builder
    {
        return $query->whereHas('contracts.services', function (Builder $serviceQuery) use ($serviceUuid) {
            $serviceQuery->where('services.uuid', $serviceUuid);
        });
    }
}
