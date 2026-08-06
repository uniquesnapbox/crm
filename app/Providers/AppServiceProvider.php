<?php

namespace App\Providers;

use App\Models\Company;
use App\Models\PushNotificationSetting;
use App\Support\BootstrapProfiler;
use App\Support\BootstrapSettings;
use Barryvdh\LaravelIdeHelper\IdeHelperServiceProvider;
use Carbon\CarbonInterval;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Laravel\Cashier\Cashier;
use Laravel\Sanctum\Sanctum;
use function config;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        BootstrapProfiler::measure(static::class, 'register', function () {
        Cashier::ignoreMigrations();
        Sanctum::ignoreMigrations();

        if (config('app.redirect_https')) {
            $this->app['request']->server->set('HTTPS', true);
        }
        });
    }

    public function boot()
    {
        BootstrapProfiler::measure(static::class, 'boot', function () {
        Cashier::useCustomerModel(Company::class);

        if (config('app.redirect_https')) {
            \URL::forceScheme('https');
        }

        Schema::defaultStringLength(191);

        // Handle push notification setting safely
        try {
            if (!BootstrapSettings::shouldSkipWebOnlyBootstrap()) {
                $pushSetting = BootstrapSettings::remember('push_notification_settings:first', function () {
                    if (!Schema::hasTable('push_notification_settings')) {
                        return null;
                    }

                    return DB::table('push_notification_settings')->first();
                });

                View::share('pushSetting', $pushSetting);
            } else {
                View::share('pushSetting', null);
            }
        } catch (\Exception $e) {
            View::share('pushSetting', null);
        }

        View::share('pageTitle', 'USB CRM');

        if (app()->environment('development')) {
            $this->app->register(IdeHelperServiceProvider::class);
        }

        // prevent scripts from timing out during long operations
        ini_set('max_execution_time', '0');

        CarbonInterval::macro('formatHuman', function ($totalMinutes, $seconds = false): string {

            if ($seconds) {
                return static::seconds($totalMinutes)->cascade()->forHumans(['short' => true, 'options' => 0]); /** @phpstan-ignore-line */
            }

            return static::minutes($totalMinutes)->cascade()->forHumans(['short' => true, 'options' => 0]); /** @phpstan-ignore-line */
        });
        });
    }
}
