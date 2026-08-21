<?php

namespace App\Models;

use App\Traits\IconTrait;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeadFollowUpAttachment extends BaseModel
{
    use IconTrait;

    public const FILE_PATH = 'lead-follow-up-attachments';

    protected $table = 'lead_follow_up_attachments';

    protected $guarded = ['id'];

    protected $appends = ['file_url', 'icon'];

    public function getFileUrlAttribute()
    {
        return asset_url_local_s3(self::FILE_PATH . '/' . $this->lead_follow_up_id . '/' . $this->hashname);
    }

    public function leadFollowUp(): BelongsTo
    {
        return $this->belongsTo(LeadFollowUp::class, 'lead_follow_up_id');
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class, 'lead_id');
    }
}
