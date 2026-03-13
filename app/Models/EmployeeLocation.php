<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeLocation extends BaseModel
{
    protected $guarded = ['id'];

    public $timestamps = false;

    protected $dates = ['timestamp'];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'employee_id');
    }
}
