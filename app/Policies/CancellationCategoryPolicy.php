<?php

namespace App\Policies;

use App\Models\CancellationCategory;
use App\Models\User;

class CancellationCategoryPolicy
{
    public function manage(User $user): bool
    {
        return (bool) ($user->superadm || $user->admin || $user->management || $user->can_dispatch || $user->operator);
    }
}
