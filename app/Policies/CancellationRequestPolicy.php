<?php

namespace App\Policies;

use App\Enum\CancellationRequestStatus;
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
        if ($request->requested_by === $user->id || $this->isTeam($user) || $this->isSupervisor($user)) {
            return true;
        }

        return (bool) ($user->engineer && (string) $request->engineer_approver_id === (string) $user->id);
    }

    public function viewQueue(User $user): bool
    {
        return $this->isTeam($user) || $this->isSupervisor($user);
    }

    public function claim(User $user, CancellationRequest $request): bool
    {
        return $this->viewQueue($user) && $request->status === CancellationRequestStatus::SUBMITTED;
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

    public function edit(User $user, CancellationRequest $request): bool
    {
        if (!$this->viewQueue($user)) {
            return false;
        }

        if ($this->isSupervisor($user)) {
            return true;
        }

        return $request->assigned_to === $user->id;
    }

    public function transfer(User $user, CancellationRequest $request): bool
    {
        if (!$this->viewQueue($user)) {
            return false;
        }

        if ($this->isSupervisor($user)) {
            return true;
        }

        return $request->assigned_to === $user->id;
    }

    public function abort(User $user, CancellationRequest $request): bool
    {
        return $this->finalize($user, $request);
    }

    public function delete(User $user, CancellationRequest $request): bool
    {
        return $this->isSupervisor($user);
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
