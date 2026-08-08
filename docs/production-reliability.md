# Production Reliability Runbook

## Queue Driver

Redis was checked first, but this environment does not have a Redis PHP extension, Redis Composer client, Redis CLI, or Redis service available. Use the database queue unless Redis is installed and verified on the production server.

Required production environment values:

```env
QUEUE_CONNECTION=database
```

The application already has the required queue tables:

- `jobs`
- `failed_jobs`
- `job_batches`

## Queue Worker

Run a dedicated queue worker under Supervisor/systemd instead of relying only on web requests.

Recommended command:

```bash
php artisan queue:work database --queue=high,default,low --sleep=3 --tries=3 --timeout=120 --max-time=3600
```

Recommended Supervisor example:

```ini
[program:crm-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/crm/artisan queue:work database --queue=high,default,low --sleep=3 --tries=3 --timeout=120 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/path/to/crm/storage/logs/worker.log
stopwaitsecs=3600
```

After deployment:

```bash
php artisan queue:restart
php artisan queue:failed
```

## Queue Candidates

These areas are already queue-oriented or should remain queue-backed in production:

- WhatsApp summary jobs: `app/Jobs/SendDailyLeadFollowUpSummaryJob.php`
- WhatsApp summary command dispatch: `app/Console/Commands/SendDailyLeadFollowUpWhatsappSummary.php`
- Notifications implementing `ShouldQueue`: `app/Notifications/*`
- Queued listeners: `app/Listeners/*`
- Imports: `app/Jobs/ImportLeadJob.php`, `app/Jobs/ImportClientJob.php`, `app/Jobs/ImportEmployeeJob.php`, `app/Jobs/ImportProjectJob.php`, `app/Jobs/ImportDealJob.php`, `app/Jobs/ImportAttendanceJob.php`
- Batch imports: `app/Traits/ImportExcel.php`
- Database backups: `app/Http/Controllers/DatabaseBackupSettingController.php`
- Mail ticket replies: `app/Observers/TicketReplyObserver.php`

## Production Cache

Safe rebuild sequence:

```bash
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

`view:cache` can take more than one minute because the project contains more than one thousand Blade files. Use a deployment timeout of at least 180 seconds.
