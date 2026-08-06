<?php

namespace App\Models;

use App\Traits\HasCompany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class DocumentTemplate extends BaseModel
{
    use HasCompany, SoftDeletes;

    protected $fillable = [
        'company_id',
        'name',
        'slug',
        'category',
        'document_type',
        'subject',
        'content_html',
        'content_json',
        'requires_approval',
        'requires_signature',
        'is_active',
        'version',
        'created_by',
        'last_updated_by',
    ];

    protected $casts = [
        'requires_approval' => 'boolean',
        'requires_signature' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function mergeTags(): HasMany
    {
        return $this->hasMany(DocumentTemplateMergeTag::class, 'template_id');
    }

    public function workflows(): HasMany
    {
        return $this->hasMany(DocumentWorkflow::class, 'template_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
