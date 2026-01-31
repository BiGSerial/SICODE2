<?php

namespace App\Policies;

use App\Models\CancellationRequest;
use App\Models\User;

class CancellationRequestPolicy
{
    public function create(User $user): bool
    {
        return true;
    }

    public function view(User $user, CancellationRequest $request): bool
    {
        return $request->requested_by === $user->id || $this->isTeam($user) || $this->isSupervisor($user);
    }

    public function viewQueue(User $user): bool
    {
        return $this->isTeam($user) || $this->isSupervisor($user);
    }

    public function claim(User $user, CancellationRequest $request): bool
    {
        return $this->viewQueue($user) && $request->status === CancellationRequest::STATUS_SUBMITTED;
    }

    public function finalize(User $user, CancellationRequest $request): bool
    {
        if (!$this->viewQueue($user)) {
            return false;
        }

        if ($this->isSupervisor($user)) {
            return true;
        }

        return $request->assigned_to === $user->id;
    }

    public function manageCategories(User $user): bool
    {
        return $this->isSupervisor($user);
    }

    private function isSupervisor(User $user): bool
    {
        return (bool) ($user->superadm || $user->admin || $user->management);
    }

    private function isTeam(User $user): bool
    {
        return (bool) ($user->can_dispatch || $user->operator);
    }
}
