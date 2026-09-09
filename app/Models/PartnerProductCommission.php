<?php

namespace App\Models;

use App\Traits\HasCompany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PartnerProductCommission extends BaseModel
{
    use HasCompany;

    protected $table = 'partner_product_commissions';

    protected $fillable = [
        'company_id', 'partner_id', 'product_id', 'commission_type',
        'commission_value', 'is_active',
    ];

    protected $casts = [
        'commission_value' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
