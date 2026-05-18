<?php

namespace App\Providers;

use App\Services\GoogleCalendarService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
        $this->app->singleton(GoogleCalendarService::class, function ($app) {
            return new GoogleCalendarService();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
