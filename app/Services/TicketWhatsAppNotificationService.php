<?php

namespace App\Services;

use App\Models\TicketAgentGroups;
use App\Models\Ticket;
use App\Models\User;
use App\Models\WhatsappNotificationSetting;
use Illuminate\Support\Collection;
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

        if ($setting->isTicketAssignedStaffMessageEnabled()) {
            $staffRecipients = $this->ticketStaffRecipients($ticket);
            $staffResults = [];

            foreach ($staffRecipients as $recipient) {
                $staffMessage = $this->renderTemplate(
                    $setting->ticket_assigned_staff_template ?: WhatsappNotificationSetting::DEFAULT_TICKET_ASSIGNED_STAFF_TEMPLATE,
                    $ticket,
                    $recipient
                );

                $staffResults[] = $this->sendToUser(
                    $recipient,
                    $staffMessage,
                    $senderNumber
                );
            }

            $this->recordTicketNotificationResult(
                $ticket,
                'whatsapp_assigned_staff',
                $staffResults,
                'No enabled ticket agents were found for this ticket group.'
            );
        }

        if ($ticket->requester && $setting->isTicketAssignedClientMessageEnabled()) {
            $clientMessage = $this->renderTemplate(
                $setting->ticket_assigned_client_template ?: WhatsappNotificationSetting::DEFAULT_TICKET_ASSIGNED_CLIENT_TEMPLATE,
                $ticket
            );

            $this->sendToUser(
                $ticket->requester,
                $clientMessage,
                $senderNumber
            );
        }
    }

    public function sendAssignedClientNotification(Ticket $ticket): void
    {
        $setting = $this->settings($ticket);

        if (
            !$setting
            || $setting->status !== 'active'
            || !$setting->isTicketMessageEnabled()
            || !$setting->isTicketAssignedClientMessageEnabled()
            || !$ticket->requester
        ) {
            return;
        }

        $senderNumber = $setting->resolved_lead_created_sender_number;
        $clientMessage = $this->renderTemplate(
            $setting->ticket_assigned_client_template ?: WhatsappNotificationSetting::DEFAULT_TICKET_ASSIGNED_CLIENT_TEMPLATE,
            $ticket
        );

        $this->sendToUser(
            $ticket->requester,
            $clientMessage,
            $senderNumber,
            'ticket-assigned-client|' . $ticket->id . '|' . $ticket->requester->id
        );
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
            $ticket->requester,
            $message,
            $senderNumber
        );
    }

    private function sendToUser(User $user, string $message, string $senderNumber, ?string $idempotencySeed = null): array
    {
        $mobile = $this->userMobile($user);

        if ($mobile === '') {
            Log::warning('Ticket WhatsApp notification skipped due to missing user mobile.', [
                'user_id' => $user->id,
                'message_preview' => mb_substr($message, 0, 120),
            ]);

            return [
                'sent' => false,
                'error' => 'User mobile number is missing.',
            ];
        }

        $sent = $this->gatewayService->sendMessage($mobile, $message, $senderNumber, null, $idempotencySeed);
        $error = $this->gatewayService->getLastError();

        if ($sent) {
            return [
                'sent' => true,
                'error' => null,
            ];
        }

        Log::warning('Ticket WhatsApp notification failed.', [
            'user_id' => $user->id,
            'mobile' => $mobile,
            'sender_number' => $senderNumber,
            'error' => $error,
        ]);

        return [
            'sent' => false,
            'error' => $error ?: 'Unable to send WhatsApp message.',
        ];
    }

    private function renderTemplate(string $template, Ticket $ticket, ?User $recipient = null): string
    {
        $assignedAgent = $recipient ?: $ticket->agent;
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

    private function ticketStaffRecipients(Ticket $ticket): Collection
    {
        $recipients = TicketAgentGroups::query()
            ->where('company_id', $ticket->company_id)
            ->where('group_id', $ticket->group_id)
            ->where('status', 'enabled')
            ->whereHas('user')
            ->with(['user' => function ($query) {
                $query->select('id', 'name', 'mobile', 'country_phonecode');
            }])
            ->get()
            ->pluck('user')
            ->filter()
            ->unique('id')
            ->values();

        if ($recipients->isEmpty() && $ticket->agent) {
            return collect([$ticket->agent]);
        }

        return $recipients;
    }

    private function recordTicketNotificationResult(Ticket $ticket, string $prefix, array $results, string $emptyMessage): void
    {
        $sentCount = collect($results)->where('sent', true)->count();
        $failedMessages = collect($results)
            ->where('sent', false)
            ->pluck('error')
            ->filter()
            ->values()
            ->all();

        if (empty($results)) {
            $ticket->forceFill([
                $prefix . '_status' => 'failed',
                $prefix . '_error' => $emptyMessage,
                $prefix . '_sent_at' => null,
            ])->saveQuietly();

            return;
        }

        $status = $sentCount === count($results) ? 'sent' : ($sentCount > 0 ? 'partial' : 'failed');

        $ticket->forceFill([
            $prefix . '_status' => $status,
            $prefix . '_error' => empty($failedMessages) ? null : implode(' | ', $failedMessages),
            $prefix . '_sent_at' => $sentCount > 0 ? now() : null,
        ])->saveQuietly();
    }

    private function userMobile(User $user): string
    {
        $mobile = preg_replace('/\D+/', '', (string) $user->mobile);
        $countryPhoneCode = preg_replace('/\D+/', '', (string) $user->country_phonecode);

        while ($countryPhoneCode !== '' && strlen($mobile) > 10 && str_starts_with($mobile, $countryPhoneCode)) {
            $mobile = substr($mobile, strlen($countryPhoneCode));
        }

        $mobile = ltrim($mobile, '0');

        if ($mobile !== '' && !empty($user->country_phonecode)) {
            return preg_replace('/\D+/', '', $user->country_phonecode . $mobile);
        }

        return $mobile;
    }

    private function settings(Ticket $ticket): ?WhatsappNotificationSetting
    {
        return WhatsappNotificationSetting::where('company_id', $ticket->company_id)->first();
    }
}
