<?php

namespace Tests\Feature;

use App\Models\CustomPage;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class CustomPagePublicAccessTest extends TestCase
{
    use DatabaseTransactions;

    public function test_active_custom_page_is_available_without_authentication(): void
    {
        Cache::forget('global_setting');

        $admin = User::query()
            ->whereNotNull('company_id')
            ->whereHas('roles', fn ($query) => $query->where('name', 'admin'))
            ->first();

        if (!$admin) {
            $this->markTestSkipped('No admin user is available.');
        }

        $page = CustomPage::create([
            'company_id' => $admin->company_id,
            'added_by' => $admin->id,
            'page_title' => 'Public Test Page',
            'slug' => 'public-test-page',
            'content' => 'This page is publicly accessible.',
            'status' => 'active',
        ]);

        $this->get(route('custom-pages.public', $page->slug))
            ->assertOk()
            ->assertSee($page->page_title)
            ->assertSee($page->content);
    }

    public function test_inactive_custom_page_is_not_public(): void
    {
        Cache::forget('global_setting');

        $admin = User::query()
            ->whereNotNull('company_id')
            ->whereHas('roles', fn ($query) => $query->where('name', 'admin'))
            ->first();

        if (!$admin) {
            $this->markTestSkipped('No admin user is available.');
        }

        $page = CustomPage::create([
            'company_id' => $admin->company_id,
            'added_by' => $admin->id,
            'page_title' => 'Inactive Test Page',
            'slug' => 'inactive-test-page',
            'content' => 'This page should remain private.',
            'status' => 'inactive',
        ]);

        $this->get(route('custom-pages.public', $page->slug))
            ->assertNotFound();
    }
}
