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

        $sessionKey = preg_replace('/\D+/', '', (string) $setting->lead_created_sender_number);
        $sessionKey = $sessionKey !== '' ? $sessionKey : trim((string) config('services.whatsapp_service.session', 'default'));

        $health = $gatewayService->getHealth();
        $qr = $gatewayService->getQr($sessionKey);

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
}
