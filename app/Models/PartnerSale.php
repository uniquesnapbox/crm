<?php

namespace App\Models;

use App\Traits\HasCompany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PartnerSale extends BaseModel
{
    use HasCompany;

    protected $table = 'partner_sales';

    protected $fillable = [
        'company_id', 'partner_id', 'lead_id', 'client_id', 'order_id',
        'invoice_id', 'order_item_id', 'product_id', 'quantity', 'sale_amount', 'commission_type',
        'commission_rate', 'commission_amount', 'sale_date', 'status', 'notes', 'created_by',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'sale_amount' => 'decimal:2',
        'commission_rate' => 'decimal:2',
        'commission_amount' => 'decimal:2',
        'sale_date' => 'date',
    ];

    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(User::class, 'client_id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }
}
