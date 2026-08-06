<?php

namespace App\Providers;

use App\Support\BootstrapProfiler;
use App\Support\BootstrapSettings;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;

class BroadcastServiceProvider extends ServiceProvider
{

    public function register()
    {
        BootstrapProfiler::measure(static::class, 'register', function () {
        try {
            if (BootstrapSettings::shouldSkipWebOnlyBootstrap()) {
                return;
            }

            $pusherSetting = BootstrapSettings::remember('pusher_settings:first', function () {
                return DB::table('pusher_settings')->first();
            });

            if ($pusherSetting) {

                if (!in_array(config('app.env'), ['demo', 'development'])) {

                    $driver = ($pusherSetting->status == 1) ? 'pusher' : 'null';

                    Config::set('broadcasting.default', $driver);
                    Config::set('broadcasting.connections.pusher.key', $pusherSetting->pusher_app_key);
                    Config::set('broadcasting.connections.pusher.secret', $pusherSetting->pusher_app_secret);
                    Config::set('broadcasting.connections.pusher.app_id', $pusherSetting->pusher_app_id);
                    Config::set('broadcasting.connections.pusher.options.host', 'api-'.$pusherSetting->pusher_cluster.'.pusher.com');
                }
            }
        }
        // @codingStandardsIgnoreLine
        catch (\Exception $e) {
        } // phpcs:ignore
        });
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Broadcast::routes();

        require base_path('routes/channels.php');
    }

}
