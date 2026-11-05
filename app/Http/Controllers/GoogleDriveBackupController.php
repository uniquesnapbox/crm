<?php

namespace App\Http\Controllers;

use App\Helper\Reply;
use App\Jobs\RunGoogleDriveBackup;
use App\Models\GoogleDriveBackupHistory;
use App\Models\GoogleDriveBackupIntegration;
use App\Services\GoogleDriveBackupService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class GoogleDriveBackupController extends AccountBaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->pageTitle = 'Google Drive Backup';
        $this->activeSettingMenu = 'database_backup_settings';
        $this->middleware(function ($request, $next) {
            abort_403(!in_array('admin', user_roles()));
            return $next($request);
        });
    }

    public function index()
    {
        return redirect()->route('company-settings.index');
    }

    public function save(Request $request)
    {
        $data = $request->validate([
            'client_id' => ['nullable', 'string', 'max:500'],
            'client_secret' => ['nullable', 'string', 'max:500'],
            'drive_folder_id' => ['nullable', 'string', 'max:255'],
            'schedule' => ['required', 'in:manual,daily,weekly'],
            'retention_days' => ['required', 'integer', 'in:7,15,30,90'],
        ]);
        $integration = GoogleDriveBackupIntegration::firstOrCreate(['company_id' => company()->id]);
        if ($data['client_id'] !== null && $data['client_id'] !== '') $integration->client_id = $data['client_id'];
        if ($data['client_secret'] !== null && $data['client_secret'] !== '') $integration->client_secret = $data['client_secret'];
        $integration->fill([
            'drive_folder_id' => $data['drive_folder_id'] ?: null,
            'schedule' => $data['schedule'], 'retention_days' => $data['retention_days'],
            'include_uploads' => $request->boolean('include_uploads', true),
            'include_configuration' => $request->boolean('include_configuration', true),
        ])->save();
        return Reply::success('Google Drive backup settings saved.');
    }

    public function connect()
    {
        $integration = GoogleDriveBackupIntegration::firstOrCreate(['company_id' => company()->id]);
        abort_if(!$integration->client_id || !$integration->client_secret, 422, 'Save Google OAuth Client ID and Client Secret first.');
        $state = Str::random(64);
        session(['google_drive_backup_state' => $state]);
        $client = app(GoogleDriveBackupService::class)->client($integration);
        $client->setState($state);
        return redirect()->away($client->createAuthUrl());
    }

    public function callback(Request $request, GoogleDriveBackupService $service)
    {
        abort_unless($request->filled('code') && hash_equals((string) session('google_drive_backup_state'), (string) $request->get('state')), 403, 'Invalid Google OAuth state.');
        session()->forget('google_drive_backup_state');
        $integration = GoogleDriveBackupIntegration::firstOrCreate(['company_id' => company()->id]);
        $client = $service->client($integration);
        $token = $client->fetchAccessTokenWithAuthCode($request->get('code'));
        if (!empty($token['error'])) abort(502, $token['error_description'] ?? 'Google authorization failed.');
        $integration->access_token = $token['access_token'] ?? null;
        if (!empty($token['refresh_token'])) $integration->refresh_token = $token['refresh_token'];
        $integration->token_expires_at = now()->timestamp + (int) ($token['expires_in'] ?? 3600);
        $integration->save();
        $account = $service->verify($integration);
        $integration->update(['google_email' => $account['email']]);
        return redirect()->route('google-drive-backup.index')->with('success', 'Google Drive connected successfully.');
    }

    public function verify(GoogleDriveBackupService $service)
    {
        $integration = GoogleDriveBackupIntegration::where('company_id', company()->id)->firstOrFail();
        $account = $service->verify($integration);
        $integration->update(['google_email' => $account['email']]);
        return Reply::success('Google Drive connection verified: ' . $account['email']);
    }

    public function disconnect()
    {
        $integration = GoogleDriveBackupIntegration::where('company_id', company()->id)->firstOrFail();
        $integration->update(['access_token' => null, 'refresh_token' => null, 'token_expires_at' => null, 'google_email' => null]);
        return Reply::success('Google Drive disconnected.');
    }

    public function backupNow()
    {
        $integration = GoogleDriveBackupIntegration::where('company_id', company()->id)->firstOrFail();
        abort_if(!$integration->refresh_token, 422, 'Connect Google Drive before starting a backup.');
        $active = GoogleDriveBackupHistory::where('company_id', company()->id)->whereIn('status', ['pending', 'running'])->exists();
        abort_if($active, 409, 'A backup is already running.');
        $history = GoogleDriveBackupHistory::create([
            'company_id' => company()->id,
            'file_name' => 'CRM-Database-' . now()->format('Y-m-d-His') . '.zip',
            'status' => 'pending',
        ]);
        RunGoogleDriveBackup::dispatch($history->id)->onQueue('default');
        return Reply::success('Backup started. Status will appear in Backup History.');
    }

    public function retry(GoogleDriveBackupHistory $history)
    {
        abort_unless($history->company_id === company()->id, 403);
        abort_if($history->status === 'running', 409, 'This backup is already running.');
        $history->update(['status' => 'pending', 'error_message' => null, 'completed_at' => null]);
        RunGoogleDriveBackup::dispatch($history->id)->onQueue('default');
        return Reply::success('Backup retry started.');
    }
}
