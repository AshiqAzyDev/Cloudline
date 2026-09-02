<?php

namespace App\Providers;

use App\Models\BillingEntity;
use App\Models\User;
use App\Observers\BillingEntityObserver;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Gate::before(function (?User $user, string $ability) {
            if ($user?->hasRole('admin')) {
                return true;
            }

            return null;
        });

        RateLimiter::for('pay', function (Request $request) {
            return Limit::perMinute(30)->by($request->ip());
        });

        RateLimiter::for('login', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip().'|'.$request->input('email'));
        });

        BillingEntity::observe(BillingEntityObserver::class);
    }
}
