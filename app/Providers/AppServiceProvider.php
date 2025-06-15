<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Spatie\Permission\Models\Role;

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
        try {
            if ($this->app->environment('local')) {
                if (Role::count() === 0) {
                    Role::create(['name' => 'super-admin']);
                    Role::create(['name' => 'checker']);
                }
            }
        } catch (\Exception $e) {
            // Handle database not ready yet
        }
    }
}
