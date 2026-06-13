<?php

namespace Tests\Feature\Core;

use App\Models\User;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class BasicFlowAccessTest extends TestCase
{
    use WithFaker;

    public function testGuestIsRedirectedFromLeadsInvoicesAndPayments(): void
    {
        $this->get('/account/lead-contact')->assertRedirect('/login');
        $this->get('/account/invoices')->assertRedirect('/login');
        $this->get('/account/payments')->assertRedirect('/login');
    }

    public function testApiLoginRejectsInvalidCredentials(): void
    {
        $response = $this->postJson('/api/login', [
            'email' => 'invalid@example.com',
            'password' => 'wrong-pass',
        ]);

        $response->assertStatus(422)
            ->assertJsonStructure(['message']);
    }

    public function testApiLoginReturnsTokenForActiveUser(): void
    {
        $password = 'StrongPass123!';

        $user = User::withoutGlobalScopes()->create([
            'name' => 'Perf Test User',
            'email' => 'perf.user.' . now()->timestamp . '@example.com',
            'password' => Hash::make($password),
            'status' => 'active',
            'login' => 'enable',
        ]);

        $response = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => $password,
            'device_name' => 'phpunit',
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'token',
                'user' => ['id', 'name', 'email', 'roles'],
                'employee_id',
            ]);
    }
}
