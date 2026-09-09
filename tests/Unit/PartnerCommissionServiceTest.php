<?php

namespace Tests\Unit;

use App\Services\PartnerCommissionService;
use PHPUnit\Framework\TestCase;

class PartnerCommissionServiceTest extends TestCase
{
    public function test_fixed_commission_is_calculated_per_quantity(): void
    {
        $this->assertSame(1000.0, (new PartnerCommissionService())->calculate('fixed', 500, 10000, 2));
    }

    public function test_percentage_commission_is_calculated_on_sale_amount(): void
    {
        $this->assertSame(750.0, (new PartnerCommissionService())->calculate('percentage', 7.5, 10000, 2));
    }
}
