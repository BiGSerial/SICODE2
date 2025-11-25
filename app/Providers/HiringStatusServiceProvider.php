<?php

namespace App\Providers;

use App\Services\HiringStatus\HiringStatusBuilder;
use App\Services\HiringStatus\Rules\{
    ApprovalApprovedRule,
    ApprovalWithCompletedReclaimRule,
    ApprovalWithoutReclaimsRule,
    ApprovalWithPendingReclaimRule,
    FinishedOrNotNeedHiring,
    HiringDirectlyNoNeedApproveRule,
    NoApprovalRule,
    ViabilityApprovedHired,
    ViabilityApprovedNotHiredRule,
    ViabilityInProgressRule,
    ViabilityRejectedWithCompletedReclaimRule,
    ViabilityRejectedWithoutReclaimsRule,
    ViabilityRejectedWithPendingReclaimRule,
    WaitingsWithPendingReclaimRule
};
use Illuminate\Support\ServiceProvider;

class HiringStatusServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        // Registra o builder como singleton, injetando as regras na ordem de prioridade
        $this->app->singleton(HiringStatusBuilder::class, function ($app) {
            return new HiringStatusBuilder([
                $app->make(NoApprovalRule::class),
                $app->make(ApprovalWithoutReclaimsRule::class),
                $app->make(ApprovalWithPendingReclaimRule::class),
                $app->make(ApprovalWithCompletedReclaimRule::class),
                $app->make(ApprovalApprovedRule::class),

                $app->make(WaitingsWithPendingReclaimRule::class),

                $app->make(ViabilityRejectedWithCompletedReclaimRule::class),
                $app->make(ViabilityRejectedWithPendingReclaimRule::class),
                $app->make(ViabilityRejectedWithoutReclaimsRule::class),
                $app->make(ViabilityInProgressRule::class),
                $app->make(ViabilityApprovedNotHiredRule::class),
                $app->make(ViabilityApprovedHired::class),
                $app->make(HiringDirectlyNoNeedApproveRule::class),
                $app->make(FinishedOrNotNeedHiring::class),

            ]);
        });

        // Caso queira, registre cada regra como singleton (opcional)
        // $this->app->singleton(NoApprovalRule::class);
        // $this->app->singleton(ApprovalWithoutReclaimsRule::class);
        // $this->app->singleton(ApprovalWithPendingReclaimRule::class);
        // $this->app->singleton(ApprovalWithCompletedReclaimRule::class);
        // $this->app->singleton(ApprovalApprovedRule::class);

        // $this->app->singleton(WaitingsWithPendingReclaimRule::class);

        // $this->app->singleton(ViabilityRejectedWithCompletedReclaimRule::class);
        // $this->app->singleton(ViabilityRejectedWithPendingReclaimRule::class);
        // $this->app->singleton(ViabilityRejectedWithoutReclaimsRule::class);
        // $this->app->singleton(ViabilityInProgressRule::class);
        // $this->app->singleton(ViabilityApprovedNotHiredRule::class);
        // $this->app->singleton(ViabilityApprovedHired::class);
    }


    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        // sem ações de boot necessárias no momento
    }
}
