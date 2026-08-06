<?php

namespace App\Services;

use App\Models\Ticket;
use App\Models\User;
use App\Models\WhatsappNotificationSetting;
use Illuminate\Support\Facades\Log;

class TicketWhatsAppNotificationService
{
    public function __construct(private WhatsAppGatewayService $gatewayService)
    {
    }

    public function sendAssignedNotifications(Ticket $ticket): void
    {
        $setting = $this->settings($ticket);

        if (!$setting || $setting->status !== 'active' || !$setting->isTicketMessageEnabled()) {
            return;
        }

        $senderNumber = $setting->resolved_lead_created_sender_number;

        if ($ticket->agent && $setting->isTicketAssignedStaffMessageEnabled()) {
            $staffMessage = $this->renderTemplate(
                $setting->ticket_assigned_staff_template ?: WhatsappNotificationSetting::DEFAULT_TICKET_ASSIGNED_STAFF_TEMPLATE,
                $ticket
            );

            $this->sendToUser(
                $ticket,
                $ticket->agent,
                $staffMessage,
                'whatsapp_assigned_staff',
                $senderNumber
            );
        }

        if ($ticket->requester && $setting->isTicketAssignedClientMessageEnabled()) {
            $clientMessage = $this->renderTemplate(
                $setting->ticket_assigned_client_template ?: WhatsappNotificationSetting::DEFAULT_TICKET_ASSIGNED_CLIENT_TEMPLATE,
                $ticket
            );

            $this->sendToUser(
                $ticket,
                $ticket->requester,
                $clientMessage,
                'whatsapp_assigned_client',
                $senderNumber
            );
        }
    }

    public function sendResolvedClientNotification(Ticket $ticket): void
    {
        $setting = $this->settings($ticket);

        if (
            !$setting
            || $setting->status !== 'active'
            || !$setting->isTicketMessageEnabled()
            || !$setting->isTicketResolvedClientMessageEnabled()
            || !$ticket->requester
        ) {
            return;
        }

        $senderNumber = $setting->resolved_lead_created_sender_number;
        $message = $this->renderTemplate(
            $setting->ticket_resolved_client_template ?: WhatsappNotificationSetting::DEFAULT_TICKET_RESOLVED_CLIENT_TEMPLATE,
            $ticket
        );

        $this->sendToUser(
            $ticket,
            $ticket->requester,
            $message,
            'whatsapp_resolved_client',
            $senderNumber
        );
    }

    private function sendToUser(Ticket $ticket, User $user, string $message, string $prefix, string $senderNumber): void
    {
        $mobile = $this->userMobile($user);

        if ($mobile === '') {
            $ticket->forceFill([
                $prefix . '_status' => 'failed',
                $prefix . '_error' => 'User mobile number is missing.',
                $prefix . '_sent_at' => null,
            ])->saveQuietly();

            return;
        }

        $sent = $this->gatewayService->sendMessage($mobile, $message, $senderNumber);
        $error = $this->gatewayService->getLastError();

        if ($sent) {
            $ticket->forceFill([
                $prefix . '_status' => 'sent',
                $prefix . '_error' => null,
                $prefix . '_sent_at' => now(),
            ])->saveQuietly();

            return;
        }

        $ticket->forceFill([
            $prefix . '_status' => 'failed',
            $prefix . '_error' => $error,
            $prefix . '_sent_at' => null,
        ])->saveQuietly();

        Log::warning('Ticket WhatsApp notification failed.', [
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
            'mobile' => $mobile,
            'sender_number' => $senderNumber,
            'type' => $prefix,
            'error' => $error,
        ]);
    }

    private function renderTemplate(string $template, Ticket $ticket): string
    {
        $assignedAgent = $ticket->agent;
        $requester = $ticket->requester;

        $placeholders = [
            '{{ticket_number}}' => (string) $ticket->ticket_number,
            '{{subject}}' => (string) $ticket->subject,
            '{{status}}' => (string) $ticket->status,
            '{{priority}}' => (string) $ticket->priority,
            '{{agent_name}}' => (string) ($assignedAgent?->name ?: ''),
            '{{client_name}}' => (string) ($requester?->name ?: ''),
        ];

        return trim(strtr($template, $placeholders));
    }

    private function userMobile(User $user): string
    {
        if (!empty($user->mobile) && !empty($user->country_phonecode)) {
            return preg_replace('/\D+/', '', $user->country_phonecode . $user->mobile);
        }

        return preg_replace('/\D+/', '', (string) $user->mobile);
    }

    private function settings(Ticket $ticket): ?WhatsappNotificationSetting
    {
        return WhatsappNotificationSetting::where('company_id', $ticket->company_id)->first();
    }
}
