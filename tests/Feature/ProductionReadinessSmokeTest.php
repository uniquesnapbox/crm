<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ProductionReadinessSmokeTest extends TestCase
{
    public function testGuestCannotAccessCoreAuthenticatedModules(): void
    {
        foreach ([
            '/account/dashboard',
            '/account/lead-contact',
            '/account/deals',
            '/account/tasks',
            '/account/invoices',
            '/account/payments',
        ] as $uri) {
            $this->get($uri)->assertRedirect('/login');
        }
    }

    public function testPublicSyncUserPermissionsRouteIsNotRegistered(): void
    {
        $this->assertFalse(Route::has('sync_user_permissions'));

        $this->get('/sync-user-permissions')->assertNotFound();
    }

    public function testWhatsappAutomationCommandsAreRegistered(): void
    {
        $commands = array_keys(Artisan::all());

        foreach ([
            'send-daily-pending-task-whatsapp-summary',
            'send-daily-lead-follow-up-whatsapp-summary',
            'send-auto-followup-reminder',
            'send-lead-followup-whatsapp-reminders',
        ] as $command) {
            $this->assertContains($command, $commands);
        }
    }

    public function testCriticalCrmRoutesAreRegistered(): void
    {
        foreach ([
            'login',
            'user-permissions.index',
            'lead-contact.index',
            'lead-contact.convert_to_client',
            'deals.index',
            'tasks.index',
            'invoices.index',
            'payments.index',
            'whatsapp.send-message',
            'api.login',
        ] as $routeName) {
            $this->assertTrue(Route::has($routeName), "Missing route [{$routeName}].");
        }
    }

    public function testApiAuthenticationRejectsInvalidLogin(): void
    {
        $this->postJson('/api/login', [
            'email' => 'invalid@example.com',
            'password' => 'wrong-password',
        ])->assertStatus(422);
    }

    public function testLostLeadStatusMigrationHasRun(): void
    {
        $this->assertTrue(
            DB::table('migrations')
                ->where('migration', '2026_08_08_000004_add_lost_to_lead_statuses')
                ->exists()
        );
    }
}
