<?php

namespace App\Http\Controllers;

use App\Helper\Reply;
use App\Http\Requests\WhatsappSetting\UpdateRequest;
use App\Models\WhatsappNotificationSetting;
use App\Services\WhatsAppGatewayService;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\PngWriter;

class WhatsappSettingController extends AccountBaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->pageTitle = 'app.menu.notificationSettings';
        $this->activeSettingMenu = 'notification_settings';
        $this->middleware(function ($request, $next) {
            abort_403(user()->permission('manage_notification_setting') !== 'all');

            return $next($request);
        });
    }

    public function update(UpdateRequest $request, $id)
    {
        $setting = WhatsappNotificationSetting::firstOrNew([
            'company_id' => company()->id,
        ]);

        $setting->status = 'active';
        $setting->send_lead_created_message = 'yes';
        $setting->lead_created_template = trim((string) ($request->lead_created_template ?: WhatsappNotificationSetting::DEFAULT_LEAD_CREATED_TEMPLATE));
        $setting->lead_created_sender_number = preg_replace('/\D+/', '', (string) $request->lead_created_sender_number);
        $setting->ticket_assigned_staff_template = trim((string) ($request->ticket_assigned_staff_template ?: 'A new ticket has been assigned to you. Ticket #{{ticket_number}}: {{subject}}'));
        $setting->ticket_assigned_client_template = trim((string) ($request->ticket_assigned_client_template ?: 'Your ticket #{{ticket_number}} has been forwarded to our team. We will get back to you soon.'));
        $setting->ticket_resolved_client_template = trim((string) ($request->ticket_resolved_client_template ?: 'Your ticket #{{ticket_number}} has been resolved. If you need anything else, please let us know.'));
        $setting->task_assigned_staff_template = trim((string) ($request->task_assigned_staff_template ?: 'A new task has been assigned to you. Task: {{task_heading}}'));
        $setting->save();

        session()->forget('whatsapp_setting');
        session()->forget('email_notification_setting');

        return Reply::success(__('messages.updateSuccess'));
    }

    public function connectionStatus(WhatsAppGatewayService $gatewayService)
    {
        $setting = WhatsappNotificationSetting::firstOrNew([
            'company_id' => company()->id,
        ]);

        $preferredSessionKey = preg_replace('/\D+/', '', (string) $setting->lead_created_sender_number);
        $fallbackSessionKey = trim((string) config('services.whatsapp_service.session', 'default'));
        $fallbackSessionKey = $fallbackSessionKey !== '' ? $fallbackSessionKey : 'default';
        $sessionKey = $preferredSessionKey !== '' ? $preferredSessionKey : $fallbackSessionKey;

        $forceRefresh = request()->boolean('refresh');

        $health = $gatewayService->getHealth();
        $qr = $gatewayService->getQr($sessionKey, $forceRefresh);

        // If sender-number-based session has no QR/status, fall back to configured default session.
        $qrStatus = (string) ($qr['data']['status'] ?? '');
        $qrValue = (string) ($qr['data']['qr'] ?? '');
        $needsFallback = $preferredSessionKey !== ''
            && $preferredSessionKey !== $fallbackSessionKey
            && (
                !($qr['success'] ?? false)
                || $qrValue === ''
                || in_array($qrStatus, ['unknown', 'disconnected'], true)
            );

        if ($needsFallback) {
            $sessionKey = $fallbackSessionKey;
            $qr = $gatewayService->getQr($sessionKey, $forceRefresh);
        }

        $qrData = $qr['data']['qr'] ?? null;
        $qrImage = null;

        if (is_string($qrData) && trim($qrData) !== '') {
            $qrImage = Builder::create()
                ->writer(new PngWriter())
                ->data($qrData)
                ->encoding(new Encoding('UTF-8'))
                ->errorCorrectionLevel(ErrorCorrectionLevel::Low)
                ->size(280)
                ->margin(12)
                ->roundBlockSizeMode(RoundBlockSizeMode::Margin)
                ->build()
                ->getDataUri();
        }

        return response()->json([
            'status' => 'success',
            'health' => $health,
            'qr' => [
                'success' => $qr['success'] ?? false,
                'error' => $qr['error'] ?? null,
                'data' => $qr['data'] ?? null,
                'image' => $qrImage,
            ],
            'sessionKey' => $sessionKey,
            'baseUrl' => config('services.whatsapp_service.base_url'),
        ]);
    }

    public function sendTestNotification(WhatsAppGatewayService $gatewayService)
    {
        $setting = WhatsappNotificationSetting::firstOrNew([
            'company_id' => company()->id,
        ]);

        $mobile = preg_replace('/\D+/', '', (string) ($setting->test_number ?? ''));

        if ($mobile === '') {
            return Reply::error('Please set a test number first.');
        }

        $sessionKey = preg_replace('/\D+/', '', (string) $setting->lead_created_sender_number);
        $sessionKey = $sessionKey !== '' ? $sessionKey : trim((string) config('services.whatsapp_service.session', 'default'));

        $sent = $gatewayService->sendMessage(
            $mobile,
            'This is a test WhatsApp notification from CRM.',
            $sessionKey
        );

        if (!$sent) {
            return Reply::error((string) ($gatewayService->getLastError() ?: 'Unable to send test WhatsApp message.'));
        }

        return Reply::success('Test WhatsApp notification sent successfully.');
    }
}
