<?php

namespace App\Providers;

use App\Models\{CancellationRequest, Form, Production, ProtestJob, Reclaim};
use App\Notifications\Channels\IdempotentDatabaseChannel;
use App\Observers\{AuditObserver, ClosureReclaimObserver, FormObserver, ProtestJobObserver};
use App\Repositories\SurveyRepository;
use Illuminate\Notifications\Channels\DatabaseChannel;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(DatabaseChannel::class, IdempotentDatabaseChannel::class);

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
        Reclaim::observe(ClosureReclaimObserver::class);

        $this->configureLivewireBaseUrl();
    }

    /**
     * Quando o app roda em subpasta (ex.: /es_qa, /sp), o Livewire precisa da raiz real da requisição
     * para achar livewire.js e para enviar as atualizações; sem isso ele usa a raiz do domínio (404).
     * Valores explícitos em config/livewire.php têm prioridade.
     */
    private function configureLivewireBaseUrl(): void
    {
        if ($this->app->runningInConsole()) {
            return;
        }

        $root = rtrim($this->app['request']->root(), '/');

        config([
            'livewire.asset_url' => config('livewire.asset_url') ?: $root,
            'livewire.app_url'   => config('livewire.app_url') ?: $root,
        ]);
    }
}
