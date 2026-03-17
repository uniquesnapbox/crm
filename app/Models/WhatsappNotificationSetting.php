<?php

namespace App\Models;

use App\Traits\HasCompany;

class WhatsappNotificationSetting extends BaseModel
{
    use HasCompany;

    protected $guarded = ['id'];

    protected $casts = [
        'last_sent_at' => 'datetime',
    ];
}
