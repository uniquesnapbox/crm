<?php

namespace App\Providers;

use App\Models\Company;
use App\Models\PushNotificationSetting;
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
        Cashier::ignoreMigrations();
        Sanctum::ignoreMigrations();

        if (config('app.redirect_https')) {
            $this->app['request']->server->set('HTTPS', true);
        }
    }

    public function boot()
    {
        Cashier::useCustomerModel(Company::class);

        if (config('app.redirect_https')) {
            \URL::forceScheme('https');
        }

        Schema::defaultStringLength(191);
        $pushSetting = null;

        try {
            if (Schema::hasTable('push_notification_settings')) {
                $pushSetting = DB::table('push_notification_settings')->first();
            }
        }
        // @codingStandardsIgnoreLine
        catch (\Exception $e) {
        }

        View::share('pushSetting', $pushSetting);
        View::share('pageTitle', 'USB CRM');

        if (app()->environment('development')) {
            $this->app->register(IdeHelperServiceProvider::class);
        }

        // prevent scripts from timing out during long operations
        ini_set('max_execution_time', '300');

        CarbonInterval::macro('formatHuman', function ($totalMinutes, $seconds = false): string {

            if ($seconds) {
                return static::seconds($totalMinutes)->cascade()->forHumans(['short' => true, 'options' => 0]); /** @phpstan-ignore-line */
            }

            return static::minutes($totalMinutes)->cascade()->forHumans(['short' => true, 'options' => 0]); /** @phpstan-ignore-line */
        });

    }

}
