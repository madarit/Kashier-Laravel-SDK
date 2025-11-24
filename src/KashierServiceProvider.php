<?php

namespace Madarit\LaravelKashier;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;
use Madarit\LaravelKashier\Http\Middleware\VerifyWebhookSignature;
use Madarit\LaravelKashier\Http\Middleware\VerifyCallbackSignature;

class KashierServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        // Merge package config with app config
        $this->mergeConfigFrom(
            __DIR__.'/../config/kashier.php', 'kashier'
        );

        // Register the service as singleton
        $this->app->singleton(KashierService::class, function ($app) {
            return new KashierService();
        });

        // Register alias
        $this->app->alias(KashierService::class, 'kashier');
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        // Publish configuration file
        $this->publishes([
            __DIR__.'/../config/kashier.php' => config_path('kashier.php'),
        ], 'kashier-config');

        // Publish views
        $this->publishes([
            __DIR__.'/../resources/views' => resource_path('views/vendor/kashier'),
        ], 'kashier-views');

        // Load views
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'kashier');

        // Load routes
        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');

        // Register middleware
        $this->app['router']->aliasMiddleware('kashier.webhook', VerifyWebhookSignature::class);
        $this->app['router']->aliasMiddleware('kashier.callback', VerifyCallbackSignature::class);

        // Register webhook routes if enabled
        if (config('kashier.webhook.enabled', true)) {
            $this->registerWebhookRoutes();
        }
    }

    /**
     * Register webhook routes.
     *
     * @return void
     */
    protected function registerWebhookRoutes()
    {
        Route::group([
            'prefix' => config('kashier.webhook.prefix', 'kashier'),
            'middleware' => ['api', 'kashier.webhook'],
        ], function () {
            Route::post('/webhook', [
                'uses' => '\Madarit\LaravelKashier\Http\Controllers\WebhookController@handle',
                'as' => 'kashier.webhook',
            ]);
        });
    }
}
