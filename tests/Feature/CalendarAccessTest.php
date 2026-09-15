<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CalendarAccessTest extends TestCase
{
    use DatabaseTransactions;

    private const VIEWABLE_PERMISSION_TYPES = ['all', 'added', 'owned', 'both'];

    public function test_user_without_lead_view_permission_cannot_access_calendar_or_events(): void
    {
        $user = $this->findNonAdminUser(function (User $candidate): bool {
            return !in_array($candidate->permission('view_lead'), self::VIEWABLE_PERMISSION_TYPES, true)
                || !in_array($candidate->permission('view_lead_follow_up'), self::VIEWABLE_PERMISSION_TYPES, true);
        });

        if (!$user) {
            $this->markTestSkipped('No non-admin user without calendar permissions is available.');
        }

        $this->actingAs($user)
            ->get(route('calendar.index'))
            ->assertForbidden();

        $this->actingAs($user)
            ->getJson(route('crm.calendar.events', [
                'start' => '2026-09-01T00:00:00+05:30',
                'end' => '2026-10-01T00:00:00+05:30',
            ]))
            ->assertForbidden();
    }

    public function test_admin_calendar_is_range_limited_and_returns_status_metadata(): void
    {
        $admin = $this->findAdmin();

        if (!$admin) {
            $this->markTestSkipped('No admin user is available.');
        }

        $inRangeLead = $this->createLead($admin->company_id, $admin->id, $admin->id);
        $outsideRangeLead = $this->createLead($admin->company_id, $admin->id, $admin->id);
        $scheduledInRange = Carbon::parse('2099-09-15 10:00:00', 'Asia/Kolkata');
        $scheduledOutsideRange = Carbon::parse('2099-11-15 10:00:00', 'Asia/Kolkata');

        $this->createFollowUp($inRangeLead, $admin->id, $scheduledInRange, 'completed');
        $this->createFollowUp($outsideRangeLead, $admin->id, $scheduledOutsideRange, 'completed');

        $response = $this->actingAs($admin)->getJson(route('crm.calendar.events', [
            'start' => '2099-09-01T00:00:00+05:30',
            'end' => '2099-10-01T00:00:00+05:30',
        ]));

        $response->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.extendedProps.status', 'completed')
            ->assertJsonPath('0.color', '#16a34a');
    }

    public function test_lead_owner_and_assigned_salesperson_can_see_follow_up(): void
    {
        $owner = $this->findNonAdminUser(fn (User $user): bool => $this->hasCalendarPermissions($user));
        $assigned = $this->findNonAdminUser(fn (User $user): bool => $this->hasCalendarPermissions($user)
            && (int) $user->company_id === (int) ($owner?->company_id)
            && $user->id !== $owner?->id);

        if (!$owner || !$assigned) {
            $this->markTestSkipped('Two non-admin calendar users are required.');
        }

        $leadId = $this->createLead($owner->company_id, $owner->id, $owner->id);
        DB::table('lead_assignees')->insert([
            'lead_id' => $leadId,
            'user_id' => $assigned->id,
            'assigned_by' => $owner->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->createFollowUp($leadId, $owner->id, Carbon::parse('2098-09-15 10:00:00', 'Asia/Kolkata'), 'pending');
        $range = [
            'start' => '2098-09-01T00:00:00+05:30',
            'end' => '2098-10-01T00:00:00+05:30',
        ];

        $this->actingAs($owner)
            ->getJson(route('crm.calendar.events', $range))
            ->assertOk()
            ->assertJsonCount(1);

        $this->actingAs($assigned)
            ->getJson(route('crm.calendar.events', $range))
            ->assertOk()
            ->assertJsonCount(1);
    }

    public function test_user_cannot_see_follow_up_for_unowned_unassigned_lead(): void
    {
        $owner = $this->findNonAdminUser(fn (User $user): bool => $this->hasCalendarPermissions($user));
        $viewer = $this->findNonAdminUser(fn (User $user): bool => $this->hasCalendarPermissions($user)
            && (int) $user->company_id === (int) ($owner?->company_id)
            && $user->id !== $owner?->id);

        if (!$owner || !$viewer) {
            $this->markTestSkipped('Two non-admin calendar users in one company are required.');
        }

        $leadId = $this->createLead($owner->company_id, $owner->id, $owner->id);
        $this->createFollowUp($leadId, $owner->id, Carbon::parse('2098-09-15 10:00:00', 'Asia/Kolkata'), 'pending');

        $this->actingAs($viewer)
            ->getJson(route('crm.calendar.events', [
                'start' => '2098-09-01T00:00:00+05:30',
                'end' => '2098-10-01T00:00:00+05:30',
            ]))
            ->assertOk()
            ->assertJsonCount(0);
    }

    public function test_completed_canceled_and_overdue_follow_ups_keep_distinct_statuses(): void
    {
        $admin = $this->findAdmin();

        if (!$admin) {
            $this->markTestSkipped('No admin user is available.');
        }

        $completedLead = $this->createLead($admin->company_id, $admin->id, $admin->id);
        $canceledLead = $this->createLead($admin->company_id, $admin->id, $admin->id);
        $overdueLead = $this->createLead($admin->company_id, $admin->id, $admin->id);
        $past = Carbon::parse('2000-01-02 10:00:00', 'Asia/Kolkata');

        $this->createFollowUp($completedLead, $admin->id, $past, 'completed');
        $this->createFollowUp($canceledLead, $admin->id, $past, 'canceled');
        $this->createFollowUp($overdueLead, $admin->id, $past, 'pending');

        $events = $this->actingAs($admin)
            ->getJson(route('crm.calendar.events', [
                'start' => $past->copy()->subDay()->toIso8601String(),
                'end' => $past->copy()->addDay()->toIso8601String(),
            ]))
            ->assertOk()
            ->json();

        $byStatus = collect($events)->keyBy(fn (array $event): string => $event['extendedProps']['status']);

        $this->assertSame('#16a34a', $byStatus->get('completed')['color']);
        $this->assertSame('#6b7280', $byStatus->get('canceled')['color']);
        $this->assertSame('#dc2626', $byStatus->get('overdue')['color']);
    }

    private function findAdmin(): ?User
    {
        return User::withoutGlobalScopes()
            ->get()
            ->first(fn (User $user): bool => $user->hasRole('admin')
                && $user->status === 'active'
                && $user->login === 'enable');
    }

    private function findNonAdminUser(callable $predicate): ?User
    {
        return User::withoutGlobalScopes()
            ->get()
            ->first(fn (User $user): bool => !$user->hasRole('admin')
                && $user->status === 'active'
                && $user->login === 'enable'
                && $predicate($user));
    }

    private function hasCalendarPermissions(User $user): bool
    {
        return in_array($user->permission('view_lead'), self::VIEWABLE_PERMISSION_TYPES, true)
            && in_array($user->permission('view_lead_follow_up'), self::VIEWABLE_PERMISSION_TYPES, true);
    }

    private function createLead(int $companyId, int $ownerId, int $assignedId): int
    {
        return (int) DB::table('leads')->insertGetId([
            'company_id' => $companyId,
            'column_priority' => 0,
            'client_name' => 'Calendar Audit Lead ' . uniqid(),
            'client_email' => uniqid('calendar-audit-') . '@example.test',
            'added_by' => $ownerId,
            'assigned_to' => $assignedId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createFollowUp(int $leadId, int $addedBy, Carbon $scheduledAt, string $status): int
    {
        return (int) DB::table('lead_follow_up')->insertGetId([
            'lead_id' => $leadId,
            'remark' => 'Calendar audit follow-up',
            'next_follow_up_date' => $scheduledAt->copy()->setTimezone('UTC'),
            'added_by' => $addedBy,
            'last_updated_by' => $addedBy,
            'send_reminder' => 'no',
            'status' => $status,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
