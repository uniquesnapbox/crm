<?php

namespace App\Support;

use Closure;
use Illuminate\Support\Facades\Cache;

class BootstrapSettings
{
    private const TTL_SECONDS = 60;

    private static array $requestCache = [];

    public static function remember(string $key, Closure $callback, ?int $ttlSeconds = null)
    {
        if (array_key_exists($key, self::$requestCache)) {
            return self::$requestCache[$key];
        }

        $value = Cache::remember(
            "bootstrap-settings:{$key}",
            $ttlSeconds ?? self::TTL_SECONDS,
            $callback
        );

        self::$requestCache[$key] = $value;

        return $value;
    }

    public static function isApiRequest(): bool
    {
        try {
            return request()?->is('api/*') ?? false;
        } catch (\Throwable) {
            return false;
        }
    }

    public static function shouldSkipWebOnlyBootstrap(): bool
    {
        return self::isApiRequest() || self::isSafeConsoleCommand();
    }

    public static function shouldLoadStorageSettings(): bool
    {
        if (self::isSafeConsoleCommand()) {
            return false;
        }

        if (!self::isApiRequest()) {
            return true;
        }

        return request()?->is('api/attendance/clock-in', 'api/attendance/clock-out') ?? false;
    }

    private static function isSafeConsoleCommand(): bool
    {
        if (!app()->runningInConsole()) {
            return false;
        }

        $argv = $_SERVER['argv'] ?? [];
        $command = $argv[1] ?? '';

        return $command === ''
            || $command === '--version'
            || $command === '-V'
            || $command === 'list'
            || $command === 'help'
            || str_starts_with($command, 'route:')
            || str_starts_with($command, 'config:')
            || str_starts_with($command, 'cache:')
            || str_starts_with($command, 'package:');
    }
}
