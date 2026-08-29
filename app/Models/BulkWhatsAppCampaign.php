<?php

namespace App\Models;

use App\Traits\HasCompany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\User;

class BulkWhatsAppCampaign extends BaseModel
{
    use HasCompany;

    protected $table = 'bulk_whatsapp_campaigns';

    protected $fillable = [
        'company_id',
        'created_by',
        'template_id',
        'name',
        'session_key',
        'message_body',
        'lead_filters',
        'recipient_count',
        'sent_count',
        'failed_count',
        'status',
        'batch_id',
        'started_at',
        'completed_at',
        'last_error',
    ];

    protected $casts = [
        'lead_filters' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function template(): BelongsTo
    {
        return $this->belongsTo(BulkWhatsAppTemplate::class, 'template_id');
    }

    public function recipients(): HasMany
    {
        return $this->hasMany(BulkWhatsAppCampaignRecipient::class, 'campaign_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function refreshProgress(): array
    {
        $counts = [
            'pending' => $this->recipients()->where('status', 'pending')->count(),
            'sent' => $this->recipients()->where('status', 'sent')->count(),
            'failed' => $this->recipients()->where('status', 'failed')->count(),
        ];

        $processed = $counts['sent'] + $counts['failed'];
        $total = max(0, (int) $this->recipient_count);
        $progress = $total > 0 ? (int) round(($processed / $total) * 100) : 0;

        if ($total > 0 && $processed >= $total) {
            $this->status = $counts['sent'] > 0 ? 'completed' : 'failed';
            $this->completed_at = now();
        } elseif (in_array($this->status, ['queued', 'draft'], true)) {
            $this->status = 'running';
        }

        $this->sent_count = $counts['sent'];
        $this->failed_count = $counts['failed'];
        $this->saveQuietly();

        return [
            'total' => $total,
            'processed' => $processed,
            'progress' => min(100, $progress),
            'sent' => $counts['sent'],
            'failed' => $counts['failed'],
            'pending' => $counts['pending'],
            'status' => $this->status,
        ];
    }
}
