<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        // 'App\Models\Model' => 'App\Policies\ModelPolicy',
    ];

    public function boot(): void
    {
        Log::info('AuthServiceProvider boot method CALLED.');
        $this->registerPolicies(); // 1回だけ呼び出す

        Gate::define('is-admin', function ($user) {
            Log::info('[Gate:is-admin] Checking user ID: ' . ($user ? $user->id : 'Guest') . ' with role: \'' . ($user ? $user->role : 'N/A') . '\'. Access ' . ($user && $user->role === 'admin' ? 'granted.' : 'denied.'));
            return $user && $user->role === 'admin';
        });
    }
}