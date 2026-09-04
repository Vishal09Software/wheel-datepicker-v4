<?php

namespace Laratribe\WheelDatePicker;

use Illuminate\Support\ServiceProvider;

class WheelDatePickerServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/wheel-datepicker.php', 'wheel-datepicker');
    }

    public function boot(): void
    {
        // NOTE: unlike the web-view version, we do NOT call Blade::component() here.
        // The `wheel_date_picker` EDGE element is registered automatically at PHP
        // boot from nativephp.json's `components` array, and the native Kotlin/Swift
        // renderers are wired in by the platform compilers at build time
        // (php artisan native:run). See nativephp.json in this package.

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/wheel-datepicker.php' => config_path('wheel-datepicker.php'),
            ], 'wheel-datepicker-config');
        }
    }
}
