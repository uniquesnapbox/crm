<?php

namespace App\Models;

class GoogleDriveBackupHistory extends BaseModel
{
    protected $table = 'google_drive_backup_history';

    protected $fillable = [
        'company_id', 'file_name', 'file_size', 'status', 'google_drive_file_id',
        'google_drive_url', 'error_message', 'started_at', 'completed_at',
    ];

    protected $casts = [
        'file_size' => 'integer',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];
}
