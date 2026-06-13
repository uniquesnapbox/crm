<?php

namespace App\Models;

use App\Traits\HasCompany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentTemplateMergeTag extends BaseModel
{
    use HasCompany;

    protected $fillable = [
        'company_id',
        'template_id',
        'tag_key',
        'tag_label',
        'source_type',
        'source_path',
        'is_required',
    ];

    protected $casts = [
        'is_required' => 'boolean',
    ];

    public function template(): BelongsTo
    {
        return $this->belongsTo(DocumentTemplate::class, 'template_id');
    }
}
