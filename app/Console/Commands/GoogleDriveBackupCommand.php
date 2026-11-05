<?php

namespace App\Console\Commands;

use App\Jobs\RunGoogleDriveBackup;
use App\Models\GoogleDriveBackupHistory;
use App\Models\GoogleDriveBackupIntegration;
use Illuminate\Console\Command;

class GoogleDriveBackupCommand extends Command
{
    protected $signature = 'crm:google-drive-backup {--force : Run regardless of configured schedule}';
    protected $description = 'Create and upload the CRM backup to Google Drive';

    public function handle(): int
    {
        $integrations = GoogleDriveBackupIntegration::whereNotNull('refresh_token')->get();
        foreach ($integrations as $integration) {
            if (!$this->option('force') && $integration->schedule === 'manual') continue;
            if (!$this->option('force') && $integration->last_backup_at && $integration->schedule === 'daily' && $integration->last_backup_at->isToday()) continue;
            if (!$this->option('force') && $integration->last_backup_at && $integration->schedule === 'weekly' && $integration->last_backup_at->greaterThanOrEqualTo(now()->subDays(7))) continue;
            $name = 'CRM-Database-' . now()->format('Y-m-d-His') . '.zip';
            $history = GoogleDriveBackupHistory::create(['company_id' => $integration->company_id, 'file_name' => $name, 'status' => 'pending']);
            RunGoogleDriveBackup::dispatchSync($history->id);
            $this->info("Backup {$name}: {$history->fresh()->status}");
        }
        return self::SUCCESS;
    }
}
