<?php

namespace App\Providers;

use App\Models\MaterialReceive;
use App\Models\SalesOrder;
use App\Models\StockOpname;
use App\Models\User;
use App\Observers\AuditObserver;
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
        SalesOrder::observe(AuditObserver::class);
        MaterialReceive::observe(AuditObserver::class);
        StockOpname::observe(AuditObserver::class);
        User::observe(AuditObserver::class);
    }
}
