<?php

namespace Tests\Feature;

use App\Rules\UniqueLeadMobile;
use App\Support\LeadMobile;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class LeadMobileValidationTest extends TestCase
{
    use DatabaseTransactions;

    public function test_a_lead_can_keep_its_mobile_but_another_lead_cannot_reuse_it(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            $this->markTestSkipped('Lead mobile duplicate protection requires the MySQL REGEXP_REPLACE expression.');
        }

        $companyId = DB::table('companies')->value('id');
        if (!$companyId) {
            $this->markTestSkipped('No company is available in the test database.');
        }

        $mobile = '+9198' . str_pad((string) random_int(0, 99999999), 8, '0', STR_PAD_LEFT);
        $leadId = DB::table('leads')->insertGetId([
            'company_id' => $companyId,
            'client_name' => 'Lead mobile regression test',
            'mobile' => $mobile,
            'mobile_normalized' => LeadMobile::normalize($mobile),
            'column_priority' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->assertTrue((new UniqueLeadMobile((int) $companyId, (int) $leadId))->passes('mobile', $mobile));
        $this->assertFalse((new UniqueLeadMobile((int) $companyId, (int) $leadId + 1))->passes('mobile', $mobile));
    }
}
