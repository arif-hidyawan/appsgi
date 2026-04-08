<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Spatie\Backup\Tasks\Backup\BackupJob;
use Illuminate\Support\Facades\Gate;

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
       // --- TAMBAHKAN KODE INI ---
        Gate::before(function ($user, $ability) {
            return $user->hasRole('super_admin') ? true : null;
        });
        // --------------------------
    }
}
