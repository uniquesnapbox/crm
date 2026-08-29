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
        $setting->send_lead_created_message = $request->has('send_lead_created_message') ? 'yes' : 'no';
        $setting->send_lead_interest_message = $request->has('send_lead_interest_message') ? 'yes' : 'no';
        $setting->send_lead_followup_message = $request->has('send_lead_followup_message') ? 'yes' : 'no';
        $setting->send_ticket_message = $request->has('send_ticket_message') ? 'yes' : 'no';
        $setting->send_ticket_assigned_staff_message = $request->has('send_ticket_assigned_staff_message') ? 'yes' : 'no';
        $setting->send_ticket_assigned_client_message = $request->has('send_ticket_assigned_client_message') ? 'yes' : 'no';
        $setting->send_ticket_resolved_client_message = $request->has('send_ticket_resolved_client_message') ? 'yes' : 'no';
        $setting->send_task_assigned_message = $request->has('send_task_assigned_message') ? 'yes' : 'no';
        $setting->send_task_daily_pending_message = $request->has('send_task_daily_pending_message') ? 'yes' : 'no';
        $setting->send_task_completed_message = $request->has('send_task_completed_message') ? 'yes' : 'no';
        $setting->lead_created_template = trim((string) ($request->lead_created_template ?: WhatsappNotificationSetting::DEFAULT_LEAD_CREATED_TEMPLATE));
        $setting->lead_interest_template = trim((string) ($request->lead_interest_template ?: WhatsappNotificationSetting::DEFAULT_LEAD_INTEREST_TEMPLATE));
        $setting->lead_followup_template = trim((string) ($request->lead_followup_template ?: WhatsappNotificationSetting::DEFAULT_LEAD_FOLLOWUP_TEMPLATE));
        $resolvedSession = preg_replace('/\D+/', '', (string) $request->lead_created_sender_number);
        if ($resolvedSession === '') {
            $resolvedSession = preg_replace('/\D+/', '', (string) config('services.whatsapp_service.session', config('app.admin_whatsapp', '')));
        }
        $setting->lead_created_sender_number = $resolvedSession;
        $setting->ticket_assigned_staff_template = trim((string) ($request->ticket_assigned_staff_template ?: WhatsappNotificationSetting::DEFAULT_TICKET_ASSIGNED_STAFF_TEMPLATE));
        $setting->ticket_assigned_client_template = trim((string) ($request->ticket_assigned_client_template ?: WhatsappNotificationSetting::DEFAULT_TICKET_ASSIGNED_CLIENT_TEMPLATE));
        $setting->ticket_resolved_client_template = trim((string) ($request->ticket_resolved_client_template ?: WhatsappNotificationSetting::DEFAULT_TICKET_RESOLVED_CLIENT_TEMPLATE));
        $setting->task_assigned_staff_template = trim((string) ($request->task_assigned_staff_template ?: WhatsappNotificationSetting::DEFAULT_TASK_ASSIGNED_TEMPLATE));
        $setting->task_daily_pending_template = trim((string) ($request->task_daily_pending_template ?: WhatsappNotificationSetting::DEFAULT_TASK_DAILY_PENDING_TEMPLATE));
        $setting->task_completed_template = trim((string) ($request->task_completed_template ?: WhatsappNotificationSetting::DEFAULT_TASK_COMPLETED_TEMPLATE));
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

        $sessionKey = $setting->resolved_whatsapp_session_key;

        $forceRefresh = request()->boolean('refresh');

        $health = $gatewayService->getHealth();
        $qr = $gatewayService->getQr($sessionKey, $forceRefresh);

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

        $sessionKey = $setting->resolved_whatsapp_session_key;

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
