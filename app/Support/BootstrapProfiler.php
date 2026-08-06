<?php

namespace App\Support;

use Closure;
use Illuminate\Support\Facades\DB;

class BootstrapProfiler
{
    public static function measure(string $provider, string $phase, Closure $callback)
    {
        if (!static::enabled()) {
            return $callback();
        }

        $startedAt = microtime(true);
        $queryCountBefore = static::queryCount();

        try {
            return $callback();
        } finally {
            $durationMs = round((microtime(true) - $startedAt) * 1000, 2);
            $queryCount = max(0, static::queryCount() - $queryCountBefore);

            static::write([
                'provider' => $provider,
                'phase' => $phase,
                'duration_ms' => $durationMs,
                'query_count' => $queryCount,
                'path' => request()?->path(),
                'console' => app()->runningInConsole(),
            ]);
        }
    }

    private static function enabled(): bool
    {
        return (bool) config(
            'performance.bootstrap_provider_profiling',
            (bool) config('app.debug', false)
        );
    }

    private static function queryCount(): int
    {
        try {
            DB::enableQueryLog();

            return count(DB::getQueryLog());
        } catch (\Throwable) {
            return 0;
        }
    }

    private static function write(array $payload): void
    {
        try {
            $line = json_encode($payload, JSON_UNESCAPED_SLASHES) . PHP_EOL;
            file_put_contents(storage_path('logs/bootstrap-provider.log'), $line, FILE_APPEND | LOCK_EX);
        } catch (\Throwable) {
            //
        }
    }
}
