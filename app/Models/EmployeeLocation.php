<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeLocation extends BaseModel
{
    protected $guarded = ['id'];

    public $timestamps = true;

    protected $dates = [
        'timestamp',
        'clock_in_at',
        'last_update_at',
        'clock_out_at',
    ];

    protected $casts = [
        'clock_in_latitude' => 'float',
        'clock_in_longitude' => 'float',
        'last_latitude' => 'float',
        'last_longitude' => 'float',
        'clock_out_latitude' => 'float',
        'clock_out_longitude' => 'float',
        'is_active' => 'bool',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'employee_id');
    }

    public function attendance(): BelongsTo
    {
        return $this->belongsTo(Attendance::class, 'attendance_id');
    }
}
