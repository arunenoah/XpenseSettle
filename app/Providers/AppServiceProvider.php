<?php

namespace App\Providers;

use Illuminate\Support\Facades\Blade;
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
        // Register a Blade directive to output the CSP nonce value
        // Returns the raw nonce string for use in script tag attributes
        Blade::directive('nonce', function () {
            return "<?php echo request()->attributes->get('nonce', ''); ?>";
        });

        // Register a Blade macro for formatting currency with 1 decimal place
        Blade::macro('currency', function ($amount, $decimals = 1) {
            return number_format($amount, $decimals, '.', '');
        });
    }
}
