<?php

namespace App\Models;

use App\Traits\HasCompany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Partner extends BaseModel
{
    use HasCompany;

    protected $table = 'partners';

    protected $fillable = [
        'company_id', 'partner_name', 'company_name', 'mobile', 'email',
        'address', 'notes', 'status', 'created_by', 'last_updated_by',
    ];

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function sales(): HasMany
    {
        return $this->hasMany(PartnerSale::class);
    }

    public function commissionConfigs(): HasMany
    {
        return $this->hasMany(PartnerProductCommission::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(PartnerCommissionPayment::class);
    }
}
