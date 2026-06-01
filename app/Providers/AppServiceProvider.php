<?php

namespace App\Providers;

use App\Models\Form;
use App\Models\Production;
use App\Models\ProtestJob;
use App\Models\CancellationRequest;
use App\Observers\AuditObserver;
use App\Observers\FormObserver;
use App\Observers\ProtestJobObserver;
use App\Repositories\SurveyRepository;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(SurveyRepository::class, function ($app) {
            return new SurveyRepository();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Production::observe(AuditObserver::class);
        CancellationRequest::observe(AuditObserver::class);
        Form::observe(FormObserver::class);
        ProtestJob::observe(ProtestJobObserver::class);
    }
}
