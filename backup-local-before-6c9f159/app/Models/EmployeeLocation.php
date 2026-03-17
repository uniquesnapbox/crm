<?php

namespace App\Models;

use App\Scopes\ActiveScope;
use App\Traits\HasCompany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeLocation extends BaseModel
{
    use HasCompany;

    protected $guarded = ['id'];

    protected $casts = [
        'timestamp' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'employee_id')->withoutGlobalScope(ActiveScope::class);
    }
}
