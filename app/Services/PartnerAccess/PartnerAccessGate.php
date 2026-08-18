<?php

namespace App\Services\PartnerAccess;

use App\Models\PartnerRole;
use App\Models\PartnerUserPermissionException;
use App\Models\User;

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

        if (str_starts_with($permissionKey, 'admin_') || $permissionKey === 'admin_panel.access') {
            if (!$user->admin) {
                return false;
            }
        }

        $companyId = self::companyIdFor($user);

        if (!$companyId) {
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

    public static function companyIdFor(User $user): ?string
    {
        if ($user->company_id) {
            return $user->company_id;
        }

        return $user->Companies()->select('companies.id')->value('companies.id');
    }
}
