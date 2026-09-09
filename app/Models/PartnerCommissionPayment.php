<?php

namespace App\Models;

use App\Traits\HasCompany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PartnerCommissionPayment extends BaseModel
{
    use HasCompany;

    protected $table = 'partner_commission_payments';

    protected $fillable = [
        'company_id', 'partner_id', 'amount', 'payment_date', 'payment_method',
        'reference', 'notes', 'created_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'payment_date' => 'date',
    ];

    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class);
    }
}
