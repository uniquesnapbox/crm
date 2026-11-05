<?php

namespace App\Services;

use App\Models\GoogleDriveBackupHistory;
use App\Models\GoogleDriveBackupIntegration;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Google_Client;
use Google_Service_Drive;
use Google_Service_Drive_DriveFile;
use ZipArchive;

class GoogleDriveBackupService
{
    public function client(GoogleDriveBackupIntegration $integration): Google_Client
    {
        $client = new Google_Client();
        $client->setClientId($integration->client_id);
        $client->setClientSecret($integration->client_secret);
        $client->setRedirectUri(route('google-drive-backup.callback'));
        $client->setScopes(['https://www.googleapis.com/auth/drive.file']);
        $client->setAccessType('offline');
        $client->setPrompt('consent');
        return $client;
    }

    public function verify(GoogleDriveBackupIntegration $integration): array
    {
        $client = $this->authenticatedClient($integration);
        $about = (new Google_Service_Drive($client))->about->get(['fields' => 'user(displayName,emailAddress),storageQuota']);
        return [
            'email' => $about->getUser()->getEmailAddress(),
            'display_name' => $about->getUser()->getDisplayName(),
        ];
    }

    public function authenticatedClient(GoogleDriveBackupIntegration $integration): Google_Client
    {
        $client = $this->client($integration);
        $token = [
            'access_token' => $integration->access_token,
            'refresh_token' => $integration->refresh_token,
            'expires_in' => max(0, ($integration->token_expires_at ?? 0) - now()->timestamp),
            'created' => now()->timestamp,
        ];
        $client->setAccessToken($token);
        if ($client->isAccessTokenExpired() && $integration->refresh_token) {
            $newToken = $client->fetchAccessTokenWithRefreshToken($integration->refresh_token);
            if (!empty($newToken['access_token'])) {
                $integration->access_token = $newToken['access_token'];
                $integration->token_expires_at = now()->timestamp + (int) ($newToken['expires_in'] ?? 3600);
                $integration->save();
                $client->setAccessToken(array_merge($token, $newToken));
            }
        }
        return $client;
    }

    public function createAndUpload(GoogleDriveBackupIntegration $integration, GoogleDriveBackupHistory $history): void
    {
        $history->update(['status' => 'running', 'started_at' => now(), 'error_message' => null]);
        $tmpRoot = storage_path('app/google-drive-backups/' . Str::uuid());
        File::ensureDirectoryExists($tmpRoot);
        $archive = $tmpRoot . DIRECTORY_SEPARATOR . $history->file_name;

        try {
            $this->createArchive($integration, $archive, $tmpRoot);
            $client = $this->authenticatedClient($integration);
            $drive = new Google_Service_Drive($client);
            $metadata = new Google_Service_Drive_DriveFile([
                'name' => $history->file_name,
                'description' => 'USB CRM automated backup',
            ]);
            $folderId = $integration->drive_folder_id ?: null;
            if ($folderId) {
                $metadata->setParents([$folderId]);
            }
            $uploaded = $drive->files->create($metadata, [
                'data' => file_get_contents($archive),
                'mimeType' => 'application/zip',
                'uploadType' => 'multipart',
                'fields' => 'id,name,size,webViewLink',
            ]);
            $history->update([
                'status' => 'success',
                'file_size' => filesize($archive),
                'google_drive_file_id' => $uploaded->getId(),
                'google_drive_url' => $uploaded->getWebViewLink() ?: 'https://drive.google.com/open?id=' . $uploaded->getId(),
                'completed_at' => now(),
            ]);
            $integration->update(['last_backup_at' => now(), 'google_email' => $integration->google_email]);
            $this->cleanupOldBackups($integration);
        } catch (\Throwable $e) {
            Log::error('Google Drive CRM backup failed', ['history_id' => $history->id, 'exception' => $e]);
            $history->update(['status' => 'failed', 'error_message' => Str::limit($e->getMessage(), 5000), 'completed_at' => now()]);
            throw $e;
        } finally {
            File::deleteDirectory($tmpRoot);
        }
    }

    protected function createArchive(GoogleDriveBackupIntegration $integration, string $archive, string $tmpRoot): void
    {
        Artisan::call('backup:run', ['--only-db' => true, '--disable-notifications' => true]);
        $backupDisk = \Storage::disk('localBackup');
        $dbBackup = collect($backupDisk->files('/backup'))
            ->filter(fn ($file) => str_ends_with($file, '.zip'))
            ->sortByDesc(fn ($file) => $backupDisk->lastModified($file))->first();
        if (!$dbBackup) {
            throw new \RuntimeException('Database backup archive was not created.');
        }

        $zip = new ZipArchive();
        if ($zip->open($archive, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException('Unable to create backup ZIP.');
        }
        $sourceZip = $tmpRoot . DIRECTORY_SEPARATOR . 'database.zip';
        File::put($sourceZip, $backupDisk->get($dbBackup));
        $source = new ZipArchive();
        $source->open($sourceZip);
        for ($i = 0; $i < $source->numFiles; $i++) {
            $name = $source->getNameIndex($i);
            if ($name && !str_ends_with($name, '/')) {
                $zip->addFromString('database/' . basename($name), $source->getFromIndex($i));
            }
        }
        $source->close();

        if ($integration->include_uploads) {
            foreach ([public_path('user-uploads'), storage_path('app/public')] as $path) {
                if (is_dir($path)) {
                    $this->addDirectory($zip, $path, 'uploads');
                }
            }
        }
        if ($integration->include_configuration) {
            foreach (['config', 'routes', 'composer.json'] as $path) {
                $full = base_path($path);
                if (is_file($full)) {
                    $zip->addFile($full, 'configuration/' . basename($full));
                } elseif (is_dir($full)) {
                    $this->addDirectory($zip, $full, 'configuration/' . basename($full), ['.env', '.key', '.pem', '.log']);
                }
            }
        }
        $zip->addFromString('backup-manifest.json', json_encode([
            'application' => config('app.name'), 'created_at' => now()->toIso8601String(),
            'database' => true, 'uploads' => (bool) $integration->include_uploads,
            'configuration' => (bool) $integration->include_configuration,
            'excluded' => ['.env', 'passwords', 'private keys', 'logs', 'cache'],
        ], JSON_PRETTY_PRINT));
        $zip->close();
    }

    protected function addDirectory(ZipArchive $zip, string $directory, string $prefix, array $blocked = []): void
    {
        $base = realpath($directory);
        foreach (File::allFiles($directory) as $file) {
            $path = $file->getPathname();
            $lower = strtolower($path);
            if (str_contains($lower, DIRECTORY_SEPARATOR . '.env') || str_ends_with($lower, '.key') || str_ends_with($lower, '.pem') || str_ends_with($lower, '.log')) {
                continue;
            }
            foreach ($blocked as $item) {
                if (str_ends_with(strtolower($path), strtolower($item))) continue 2;
            }
            $relative = ltrim(str_replace($base, '', realpath($path)), DIRECTORY_SEPARATOR);
            $zip->addFile($path, trim($prefix . '/' . $relative, '/'));
        }
    }

    protected function cleanupOldBackups(GoogleDriveBackupIntegration $integration): void
    {
        $cutoff = now()->subDays(max(1, $integration->retention_days));
        $client = $this->authenticatedClient($integration);
        $drive = new Google_Service_Drive($client);
        $files = $drive->files->listFiles(['q' => "trashed = false and name contains 'CRM-Database-'", 'fields' => 'files(id,name,createdTime)'])->getFiles();
        foreach ($files as $file) {
            if ($file->getCreatedTime() && \Carbon\Carbon::parse($file->getCreatedTime())->lt($cutoff)) {
                $drive->files->delete($file->getId());
            }
        }
    }
}
