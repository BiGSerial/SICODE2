<?php

namespace App\Observers;

use App\Models\Audit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class AuditObserver
{
    /**
     * Handle the Production "created" event.
     */
    public function created(Model $model): void
    {
        $this->logChanges('created', $model);
    }

    /**
     * Handle the Production "updated" event.
     */
    public function updated(Model $model): void
    {
        $this->logChanges('updated', $model);
    }

    /**
     * Handle the Production "deleted" event.
     */
    public function deleted(Model $model): void
    {
        $this->logChanges('deleted', $model);
    }

    /**
     * Handle the Production "restored" event.
     */
    public function restored(Model $model): void
    {
        $this->logChanges('restored', $model);
    }

    /**
     * Handle the Production "force deleted" event.
     */
    public function forceDeleted(Model $model): void
    {
        $this->logChanges('force_deleted', $model);
    }

    protected function logChanges($action, Model $model)
    {
        $originalData = $model->getOriginal();
        $currentData  = $model->getAttributes();

        if (Auth::check()) {
            Audit::create([
                'user_id'     => auth()->id(),
                'model_class' => get_class($model),
                'action'      => $action,
                'before'      => json_encode($originalData),
                'after'       => json_encode($currentData),
            ]);
        }

    }
}
