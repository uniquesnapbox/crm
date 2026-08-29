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

## Laravel Scheduler

Laravel scheduled commands only run when a scheduler process is already alive. The cron line in the UI is correct for Linux/macOS servers, but on Windows/XAMPP it is not enough by itself because nothing is launching `schedule:run` or `schedule:work` continuously.

### Linux/macOS

Use the standard cron entry shown in the app:

```bash
* * * * * php /path/to/crm/artisan schedule:run >> /dev/null 2>&1
```

### Windows/XAMPP

Use the PowerShell launcher in `scripts/windows/start-scheduler.ps1` and register it as a Windows Scheduled Task:

```powershell
powershell -NoProfile -ExecutionPolicy Bypass -WindowStyle Hidden -File "C:\xampp\htdocs\CRM\crm-main\scripts\windows\install-scheduler-task.ps1"
```

The installed task runs `schedule:work` in a loop, restarts it if it exits, and writes logs to:

- `storage/logs/scheduler.log`
- `storage/logs/scheduler-error.log`

This is the reliable way to keep follow-up reminders, backups, and other scheduled jobs running on Windows.

The WhatsApp bridge must also be running separately. The startup installer creates a hidden login-startup shortcut for `start-whatsapp-service.ps1`; the launcher keeps port `3100` available and restarts the Node service if it exits. Confirm the bridge with `http://127.0.0.1:3100/health`. A `qr_required` or `UNPAIRED` response means the bridge is healthy but the configured WhatsApp account must be scanned once from the CRM QR setup page.

### Safe Reminder Backfill

If the scheduler was down and reminders need a controlled replay, use the reminder command with a bounded backfill window:

```bash
php artisan send-lead-followup-whatsapp-reminders --backfill-hours=168
```

Safety guarantees:

- `whatsapp_reminder_sent_at` is set only after a successful WhatsApp send.
- Each reminder uses an idempotency key derived from the follow-up id and scheduled timestamp.
- The backfill window is bounded so it does not reprocess the entire history unless you intentionally raise the lookback window.
- Already-sent reminders are skipped automatically.

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
