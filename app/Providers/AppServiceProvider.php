<?php

namespace App\Providers;

use App\Models\{Form, Production};
use App\Observers\{AuditObserver, FormObserver};
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Production::observe(AuditObserver::class);
        Form::observe(FormObserver::class);
    }
}
