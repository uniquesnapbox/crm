<?php

namespace App\Models;

class GoogleDriveBackupIntegration extends BaseModel
{
    protected $table = 'google_drive_backup_integrations';

    protected $fillable = [
        'company_id', 'client_id', 'client_secret', 'access_token', 'refresh_token',
        'token_expires_at', 'google_email', 'drive_folder_id', 'schedule',
        'retention_days', 'include_uploads', 'include_configuration', 'last_backup_at',
    ];

    protected $casts = [
        'client_id' => 'encrypted',
        'client_secret' => 'encrypted',
        'access_token' => 'encrypted',
        'refresh_token' => 'encrypted',
        'token_expires_at' => 'integer',
        'retention_days' => 'integer',
        'include_uploads' => 'boolean',
        'include_configuration' => 'boolean',
        'last_backup_at' => 'datetime',
    ];
}
