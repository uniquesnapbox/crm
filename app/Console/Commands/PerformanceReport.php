<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PerformanceReport extends Command
{
    protected $signature = 'perf:report {--hours=24 : Analyze last N hours} {--top=10 : Number of endpoints to show} {--prune : Prune old rows based on config retention}';
    protected $description = 'Show top slow endpoints and p95 latency from request performance logs';

    public function handle(): int
    {
        if (!Schema::hasTable('request_performance_logs')) {
            $this->error('request_performance_logs table not found. Run migrations first.');
            return self::FAILURE;
        }

        if ($this->option('prune')) {
            $retentionDays = (int) config('performance.retention_days', 14);
            DB::table('request_performance_logs')
                ->where('created_at', '<', now()->subDays($retentionDays))
                ->delete();
        }

        $hours = (int) $this->option('hours');
        $top = (int) $this->option('top');
        $since = now()->subHours($hours);

        $rows = DB::table('request_performance_logs')
            ->where('created_at', '>=', $since)
            ->select('method', 'path', 'duration_ms', 'query_time_ms', 'query_count')
            ->get()
            ->groupBy(function ($row) {
                return strtoupper($row->method) . ' ' . $row->path;
            })
            ->map(function ($group, $endpoint) {
                $durations = $group->pluck('duration_ms')->sort()->values()->all();
                $count = count($durations);
                $p95Index = max(0, (int) ceil($count * 0.95) - 1);
                $p99Index = max(0, (int) ceil($count * 0.99) - 1);
                $p95 = $count > 0 ? (float) $durations[$p95Index] : 0.0;
                $p99 = $count > 0 ? (float) $durations[$p99Index] : 0.0;

                return [
                    'endpoint' => $endpoint,
                    'hits' => $count,
                    'avg_ms' => round((float) $group->avg('duration_ms'), 2),
                    'p95_ms' => round($p95, 2),
                    'p99_ms' => round($p99, 2),
                    'avg_query_ms' => round((float) $group->avg('query_time_ms'), 2),
                    'avg_queries' => round((float) $group->avg('query_count'), 2),
                ];
            })
            ->sortByDesc('p95_ms')
            ->take($top)
            ->values();

        if ($rows->isEmpty()) {
            $this->warn('No performance rows found in selected window.');
            return self::SUCCESS;
        }

        $this->info("Top {$top} endpoints in last {$hours} hours");
        $this->table(
            ['Endpoint', 'Hits', 'Avg (ms)', 'P95 (ms)', 'P99 (ms)', 'Avg Query (ms)', 'Avg Queries'],
            $rows->map(function ($row) {
                return [
                    $row['endpoint'],
                    $row['hits'],
                    $row['avg_ms'],
                    $row['p95_ms'],
                    $row['p99_ms'],
                    $row['avg_query_ms'],
                    $row['avg_queries'],
                ];
            })->all()
        );

        return self::SUCCESS;
    }
}
