<?php

namespace Tests\Unit;

use App\Models\Task;
use App\Models\User;
use App\Services\TaskWhatsAppNotificationService;
use App\Services\WhatsAppGatewayService;
use Carbon\Carbon;
use Mockery;
use Tests\TestCase;

class TaskWhatsAppNotificationServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    public function test_it_replaces_user_name_for_task_templates(): void
    {
        $service = new TaskWhatsAppNotificationService(
            Mockery::mock(WhatsAppGatewayService::class)
        );

        $task = new Task();
        $task->id = 44;
        $task->heading = 'Fix WhatsApp Auto Logout';
        $task->due_date = Carbon::create(2026, 8, 29, 10, 30, 0);
        $task->completed_on = Carbon::create(2026, 8, 29, 14, 22, 0);

        $recipient = new User();
        $recipient->name = 'Arindam Dey';

        $method = new \ReflectionMethod($service, 'renderTemplate');
        $method->setAccessible(true);

        $message = $method->invoke(
            $service,
            'Hello *{{user_name}}*, Task: {{task_heading}}. Completed by {{completed_by}}',
            $task,
            $recipient
        );

        $this->assertSame(
            'Hello *Arindam Dey*, Task: Fix WhatsApp Auto Logout. Completed by ',
            $message
        );
    }
}
