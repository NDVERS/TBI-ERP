<?php

namespace App\Providers;

use App\Models\LabConversion;
use App\Models\MaterialReceive;
use App\Models\SalesOrder;
use App\Models\StockOpname;
use App\Models\User;
use App\Observers\AuditObserver;
use Illuminate\Support\Facades\URL;
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
        if (request()->isSecure() || request()->header('x-forwarded-proto') === 'https' || str_starts_with((string) config('app.url'), 'https://')) {
            URL::forceScheme('https');
        }

        SalesOrder::observe(AuditObserver::class);
        MaterialReceive::observe(AuditObserver::class);
        StockOpname::observe(AuditObserver::class);
        LabConversion::observe(AuditObserver::class);
        User::observe(AuditObserver::class);
    }
}
