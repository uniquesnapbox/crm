<?php

namespace App\Jobs;

use App\Models\GoogleDriveBackupHistory;
use App\Models\GoogleDriveBackupIntegration;
use App\Services\GoogleDriveBackupService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RunGoogleDriveBackup implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $historyId) {}

    public function handle(GoogleDriveBackupService $service): void
    {
        $history = GoogleDriveBackupHistory::findOrFail($this->historyId);
        $integration = GoogleDriveBackupIntegration::where('company_id', $history->company_id)->firstOrFail();
        $service->createAndUpload($integration, $history);
    }

    public function failed(\Throwable $exception): void
    {
        GoogleDriveBackupHistory::whereKey($this->historyId)->update([
            'status' => 'failed', 'error_message' => $exception->getMessage(), 'completed_at' => now(),
        ]);
    }
}
