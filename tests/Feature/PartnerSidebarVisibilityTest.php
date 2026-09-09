<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;

class PartnerSidebarVisibilityTest extends TestCase
{
    public function test_admin_can_open_partners_and_sees_the_sidebar_link(): void
    {
        $admin = User::withoutGlobalScopes()->findOrFail(1);

        $response = $this->actingAs($admin)->get(route('partners.index'));

        $response->assertOk();
        $response->assertSee('Partners');
        $response->assertSee(route('partners.index'), false);
    }
}
