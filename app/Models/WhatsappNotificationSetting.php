<?php

namespace App\Models;

use App\Traits\HasCompany;

class WhatsappNotificationSetting extends BaseModel
{
    use HasCompany;

    public const YES = 'yes';
    public const NO = 'no';
    public const DEFAULT_LEAD_CREATED_TEMPLATE = "Hello {{client_name}}, thank you for your interest. We have received your lead and our team will contact you soon.";
    public const DEFAULT_LEAD_INTEREST_TEMPLATE = "Hello {{client_name}}, thank you for sharing your interest in {{products_services}}. Our team will contact you soon.";
    public const DEFAULT_TICKET_ASSIGNED_STAFF_TEMPLATE = 'A new ticket has been assigned to you. Ticket #{{ticket_number}}: {{subject}}';
    public const DEFAULT_TICKET_ASSIGNED_CLIENT_TEMPLATE = 'Your ticket #{{ticket_number}} has been forwarded to our team. We will get back to you soon.';
    public const DEFAULT_TICKET_RESOLVED_CLIENT_TEMPLATE = 'Your ticket #{{ticket_number}} has been resolved. If you need anything else, please let us know.';
    public const DEFAULT_TASK_ASSIGNED_TEMPLATE = 'A new task has been assigned to you. Task: {{task_heading}}';
    public const DEFAULT_TASK_DAILY_PENDING_TEMPLATE = "Good morning {{user_name}}, you have {{pending_count}} pending task(s):\n{{task_list}}";
    public const DEFAULT_TASK_COMPLETED_TEMPLATE = "Task completed: {{task_heading}}\nProject: {{project_name}}\nCompleted on: {{completed_on}}";

    protected $guarded = ['id'];

    protected $casts = [
        'last_sent_at' => 'datetime',
    ];

    public function getResolvedLeadCreatedSenderNumberAttribute(): string
    {
        return (string) ($this->lead_created_sender_number ?: config('app.admin_whatsapp', ''));
    }

    public function getDeliveryStatusLabelAttribute(): string
    {
        return match ($this->last_delivery_status) {
            'sent' => 'Sent confirmed',
            'accepted' => 'Accepted only',
            'failed' => 'Failed',
            default => 'Not sent yet',
        };
    }

    public function isLeadMessageEnabled(): bool
    {
        return $this->isLeadCreatedMessageEnabled();
    }

    public function isLeadCreatedMessageEnabled(): bool
    {
        return ($this->send_lead_created_message ?? self::NO) === self::YES;
    }

    public function isLeadInterestMessageEnabled(): bool
    {
        return ($this->send_lead_interest_message ?? self::YES) === self::YES;
    }

    public function isTicketMessageEnabled(): bool
    {
        return ($this->send_ticket_message ?? self::YES) === self::YES;
    }

    public function isTicketAssignedStaffMessageEnabled(): bool
    {
        return ($this->send_ticket_assigned_staff_message ?? self::YES) === self::YES;
    }

    public function isTicketAssignedClientMessageEnabled(): bool
    {
        return ($this->send_ticket_assigned_client_message ?? self::YES) === self::YES;
    }

    public function isTicketResolvedClientMessageEnabled(): bool
    {
        return ($this->send_ticket_resolved_client_message ?? self::YES) === self::YES;
    }

    public function isTaskMessageEnabled(): bool
    {
        return ($this->send_task_assigned_message ?? self::YES) === self::YES;
    }

    public function isTaskAssignedMessageEnabled(): bool
    {
        return ($this->send_task_assigned_message ?? self::YES) === self::YES;
    }

    public function isTaskDailyPendingMessageEnabled(): bool
    {
        return ($this->send_task_daily_pending_message ?? self::YES) === self::YES;
    }

    public function isTaskCompletedMessageEnabled(): bool
    {
        return ($this->send_task_completed_message ?? self::YES) === self::YES;
    }
}
