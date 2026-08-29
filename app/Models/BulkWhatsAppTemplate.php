<?php

namespace App\Models;

use App\Traits\HasCompany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use App\Models\User;

class BulkWhatsAppTemplate extends BaseModel
{
    use HasCompany;

    protected $table = 'bulk_whatsapp_templates';

    protected $fillable = [
        'company_id',
        'created_by',
        'updated_by',
        'name',
        'message',
        'attachment_path',
        'attachment_name',
        'attachment_mime',
        'attachment_size',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'bool',
        'attachment_size' => 'integer',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getAttachmentUrlAttribute(): ?string
    {
        if (blank($this->attachment_path)) {
            return null;
        }

        return Storage::disk('public')->url($this->attachment_path);
    }
}
