<?php

namespace App\Policies;

use App\Models\{QualityProcess, User};
use App\Services\Quality\QualityRoles;

/** Portão rápido (403) sobre os papéis. As regras de fluxo continuam validadas no QualityWorkflowService. */
class QualityProcessPolicy
{
    public function __construct(private readonly QualityRoles $roles)
    {
    }

    public function view(User $user, QualityProcess $process): bool
    {
        return $this->roles->canViewProcess($user, $process);
    }

    public function actAsN1(User $user, QualityProcess $process): bool
    {
        return $this->roles->canActAsN1($user, $process);
    }

    public function actAsN2(User $user, QualityProcess $process): bool
    {
        return $this->roles->canActAsN2($user, $process);
    }

    public function comment(User $user, QualityProcess $process): bool
    {
        return $this->view($user, $process);
    }
}
