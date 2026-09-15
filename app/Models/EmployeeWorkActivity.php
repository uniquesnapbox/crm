<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeWorkActivity extends BaseModel
{
    protected $table = 'employee_work_activities';

    protected $fillable = [
        'company_id',
        'user_id',
        'activity_date',
        'start_time',
        'end_time',
        'activity',
        'status',
    ];

    protected $casts = [
        'activity_date' => 'date',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id')->withoutGlobalScope(\App\Scopes\ActiveScope::class);
    }
}
