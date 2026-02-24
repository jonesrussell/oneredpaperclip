<?php

namespace App\Providers;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

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
        $this->suppressTempnamFallbackNotice();
        $this->configureDefaults();
    }

    /**
     * Suppress PHP's notice when tempnam() falls back to system temp (e.g. when
     * the requested directory is not writable by the web server user). The
     * file is still created and the operation succeeds; suppressing avoids
     * the notice being converted to a 500 by Laravel's error handler.
     */
    protected function suppressTempnamFallbackNotice(): void
    {
        $previous = set_error_handler(function (int $severity, string $message, string $file, int $line) use (&$previous): bool {
            if ($severity === E_NOTICE && str_contains($message, "file created in the system's temporary directory")) {
                return true;
            }

            return $previous ? $previous($severity, $message, $file, $line) : false;
        }, E_NOTICE);
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(8)
                ->letters()
                ->numbers()
                ->uncompromised()
            : null
        );
    }
}
