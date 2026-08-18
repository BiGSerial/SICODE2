<?php

namespace App\Services\PartnerAccess;

use App\Models\Company;
use App\Models\PartnerCompanyPermissionGrant;
use App\Models\PartnerRole;
use App\Models\PartnerUserPermissionException;
use App\Models\User;
use Illuminate\Support\Collection;

class PartnerAccessGate
{
    public static function allows(?User $user, string $permissionKey): bool
    {
        if (!$user) {
            return false;
        }

        if ($user->superadm) {
            return true;
        }

        $adminPermission = self::isAdminPermission($permissionKey);

        if ($adminPermission) {
            if (!$user->admin) {
                return false;
            }
        }

        $companyId = self::permissionCompanyIdFor($user);

        if (!$companyId) {
            return false;
        }

        if ($permissionKey === 'portal.access') {
            return self::grantedPermissionKeysForCompany($companyId)
                ->contains(fn (string $grantedPermissionKey) => self::allows($user, $grantedPermissionKey));
        }

        if (!self::companyGrantAllows($companyId, $permissionKey)) {
            return false;
        }

        $exception = PartnerUserPermissionException::query()
            ->where('company_id', $companyId)
            ->where('user_id', $user->id)
            ->where('permission_key', $permissionKey)
            ->first();

        if ($exception) {
            return (bool) $exception->enabled;
        }

        if ($adminPermission) {
            return true;
        }

        $role = PartnerRole::query()
            ->where('company_id', $companyId)
            ->with('permissions')
            ->first();

        if (!$role) {
            return true;
        }

        $groupKey = PartnerPermissionCatalog::groupFor($permissionKey);

        if (!$groupKey) {
            return false;
        }

        $permissions = $role->permissions->keyBy('permission_key');
        $groupPermission = $permissions->get($groupKey);

        if (!$groupPermission || !$groupPermission->enabled) {
            return false;
        }

        if ($permissionKey === $groupKey) {
            return true;
        }

        $itemPermission = $permissions->get($permissionKey);

        if ($itemPermission) {
            return (bool) $itemPermission->enabled;
        }

        return true;
    }

    public static function denies(?User $user, string $permissionKey): bool
    {
        return !self::allows($user, $permissionKey);
    }

    private static function isAdminPermission(string $permissionKey): bool
    {
        return $permissionKey === 'admin'
            || str_starts_with($permissionKey, 'admin_')
            || $permissionKey === 'admin_panel.access';
    }

    public static function companyIdFor(User $user): ?string
    {
        if ($user->company_id) {
            return $user->company_id;
        }

        return $user->Companies()->select('companies.id')->value('companies.id');
    }

    public static function permissionCompanyIdFor(User $user): ?string
    {
        $companyId = self::companyIdFor($user);

        if (!$companyId) {
            return null;
        }

        $company = Company::withTrashed()->find($companyId);

        return $company?->parent_id ?: $companyId;
    }

    public static function grantedPermissionKeysForCompany(string $companyId): Collection
    {
        $grants = PartnerCompanyPermissionGrant::query()
            ->where('company_id', $companyId)
            ->get();

        if ($grants->isEmpty()) {
            return PartnerPermissionCatalog::allPermissionKeys();
        }

        return self::enabledKeysFromPermissionRows($grants);
    }

    private static function companyGrantAllows(string $companyId, string $permissionKey): bool
    {
        $grants = PartnerCompanyPermissionGrant::query()
            ->where('company_id', $companyId)
            ->get();

        if ($grants->isEmpty()) {
            return true;
        }

        return self::permissionRowsAllow($grants, $permissionKey);
    }

    private static function permissionRowsAllow(Collection $rows, string $permissionKey): bool
    {
        $groupKey = PartnerPermissionCatalog::groupFor($permissionKey);

        if (!$groupKey) {
            return false;
        }

        $permissions = $rows->keyBy('permission_key');
        $groupPermission = $permissions->get($groupKey);

        if (!$groupPermission || !$groupPermission->enabled) {
            return false;
        }

        if ($permissionKey === $groupKey) {
            return true;
        }

        $itemPermission = $permissions->get($permissionKey);

        if ($itemPermission) {
            return (bool) $itemPermission->enabled;
        }

        return true;
    }

    private static function enabledKeysFromPermissionRows(Collection $rows): Collection
    {
        return PartnerPermissionCatalog::allPermissionKeys()
            ->filter(fn (string $permissionKey) => self::permissionRowsAllow($rows, $permissionKey))
            ->values();
    }
}
