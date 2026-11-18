<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        Gate::define('manage-shop', fn (User $u) => $u->is_admin);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Local-only transport: even a mistakenly configured SMTP default cannot send externally.
        config(['mail.default' => 'log', 'mail.from.address' => 'local@example.test', 'mail.from.name' => 'Frekvens 17 - LOCAL TEST']);
        ResetPassword::createUrlUsing(fn ($user, $token) => url('/reset-password').'?'.http_build_query(['token' => $token, 'email' => $user->email]));
    }
}
