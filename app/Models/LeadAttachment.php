<?php

namespace App\Models;

use App\Traits\IconTrait;

class LeadAttachment extends BaseModel
{
    use IconTrait;

    const FILE_PATH = 'lead-contact-attachments';

    protected $table = 'lead_contact_attachments';

    protected $guarded = ['id'];

    protected $appends = ['file_url', 'icon'];

    public function getFileUrlAttribute()
    {
        return asset_url_local_s3(self::FILE_PATH . '/' . $this->lead_id . '/' . $this->hashname);
    }
}
