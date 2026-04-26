<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class TrackRequestPerformance
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!config('performance.enabled')) {
            return $next($request);
        }

        foreach (config('performance.ignored_paths', []) as $ignoredPath) {
            if ($request->is($ignoredPath)) {
                return $next($request);
            }
        }

        $queryCount = 0;
        $queryTimeMs = 0.0;
        $slowThresholdMs = (float) config('performance.slow_query_threshold_ms', 250);

        DB::listen(function (QueryExecuted $query) use (&$queryCount, &$queryTimeMs, $slowThresholdMs, $request) {
            $queryCount++;
            $queryTimeMs += (float) $query->time;

            if ($query->time >= $slowThresholdMs) {
                Log::channel('slow_query')->warning('Slow query detected', [
                    'path' => $request->path(),
                    'method' => $request->method(),
                    'time_ms' => round((float) $query->time, 2),
                    'sql' => $query->sql,
                ]);
            }
        });

        $startedAt = microtime(true);
        /** @var Response $response */
        $response = $next($request);
        $durationMs = (microtime(true) - $startedAt) * 1000;

        if ($durationMs < (float) config('performance.track_min_request_ms', 200)) {
            return $response;
        }

        if (DB::getSchemaBuilder()->hasTable('request_performance_logs')) {
            DB::table('request_performance_logs')->insert([
                'company_id' => optional(company())->id,
                'method' => $request->method(),
                'path' => $request->path(),
                'status_code' => $response->getStatusCode(),
                'duration_ms' => round($durationMs, 2),
                'query_time_ms' => round($queryTimeMs, 2),
                'query_count' => $queryCount,
                'route_name' => optional($request->route())->getName(),
                'request_id' => (string) str()->uuid(),
                'created_at' => now(),
            ]);
        }

        return $response;
    }
}

