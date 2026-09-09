<?php

namespace App\Services;

use App\Models\Partner;
use App\Models\PartnerProductCommission;
use App\Models\Product;

class PartnerCommissionService
{
    public function resolve(Partner $partner, ?Product $product): array
    {
        $config = $product ? PartnerProductCommission::query()
            ->where('partner_id', $partner->id)
            ->where('product_id', $product->id)
            ->where('is_active', true)
            ->first() : null;

        return [
            'commission_type' => $config?->commission_type ?? 'fixed',
            'commission_rate' => (float) ($config?->commission_value ?? 0),
        ];
    }

    public function calculate(string $type, float $rate, float $amount, float $quantity): float
    {
        return round($type === 'percentage' ? ($amount * $rate / 100) : ($rate * $quantity), 2);
    }
}
