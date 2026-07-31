<?php

namespace App\Providers;

use App\Support\BootstrapProfiler;
use App\Support\BootstrapSettings;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class TranslateSettingConfigProvider extends ServiceProvider
{

    public function register()
    {
        BootstrapProfiler::measure(static::class, 'register', function () {
        try {
            if (BootstrapSettings::shouldSkipWebOnlyBootstrap()) {
                return;
            }

            $translateSetting = BootstrapSettings::remember('translate_settings:first', function () {
                if (!Schema::hasTable('translate_settings')) {
                    return null;
                }

                return DB::table('translate_settings')->first();
            });

            if ($translateSetting) {
                Config::set('laravel_google_translate.google_translate_api_key', $translateSetting->google_key);
            }


        }
        // @codingStandardsIgnoreLine
        catch (\Exception $e) {
        }
        });

    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }

}
