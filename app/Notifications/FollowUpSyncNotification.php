<?php

namespace App\Notifications;

use NotificationChannels\OneSignal\OneSignalChannel;
use NotificationChannels\OneSignal\OneSignalMessage;

class FollowUpSyncNotification extends BaseNotification
{
    public function __construct(private readonly array $followUp)
    {
    }

    public function via($notifiable): array
    {
        return [OneSignalChannel::class];
    }

    public function toOneSignal($notifiable): OneSignalMessage
    {
        $status = (string) ($this->followUp['status'] ?? 'pending');
        $action = (string) ($this->followUp['action'] ?? 'sync');
        $isPending = $status === 'pending' && !empty($this->followUp['scheduled_at']);
        $title = $isPending ? 'Follow-up scheduled' : 'Follow-up updated';
        $body = $isPending
            ? 'A follow-up was scheduled for ' . ($this->followUp['lead_name'] ?? 'a lead') . '.'
            : 'A follow-up was marked ' . ($status ?: $action) . '.';

        return OneSignalMessage::create()
            ->setSubject($title)
            ->setBody($body)
            ->setData('type', 'follow_up_sync')
            ->setData('action', $action)
            ->setData('follow_up_id', (int) ($this->followUp['id'] ?? 0))
            ->setData('lead_id', (int) ($this->followUp['lead_id'] ?? 0))
            ->setData('lead_name', (string) ($this->followUp['lead_name'] ?? 'Lead'))
            ->setData('client_number', (string) ($this->followUp['client_number'] ?? ''))
            ->setData('remarks', (string) ($this->followUp['remarks'] ?? ''))
            ->setData('note', (string) ($this->followUp['note'] ?? ''))
            ->setData('scheduled_at', $this->followUp['scheduled_at'] ?? '')
            ->setData('trigger_at', (int) ($this->followUp['trigger_at'] ?? 0))
            ->setData('status', $status);
    }
}
