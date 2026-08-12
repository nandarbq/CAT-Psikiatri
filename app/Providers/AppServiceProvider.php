<?php

namespace App\Providers;

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

    public static function guardTokenBypassProduction(): void
    {
        if (app()->environment('production') && (bool) config('exam.token_bypass_enabled', false)) {
            throw new \RuntimeException('EXAM_TOKEN_BYPASS cannot be enabled in production.');
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        self::guardTokenBypassProduction();
    }
}
