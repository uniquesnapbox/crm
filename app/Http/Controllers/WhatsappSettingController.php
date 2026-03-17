<?php

namespace App\Http\Controllers;

use App\Helper\Reply;
use App\Http\Requests\WhatsappSetting\UpdateRequest;
use App\Models\EmailNotificationSetting;
use App\Models\WhatsappNotificationSetting;
use App\Services\WhatsApp\WascriptService;

class WhatsappSettingController extends AccountBaseController
{
    public function __construct(private WascriptService $wascriptService)
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
        $this->saveWhatsAppNotificationSettings($request);

        $setting = WhatsappNotificationSetting::firstOrNew([
            'company_id' => company()->id,
        ]);

        $setting->status = $request->has('whatsapp_status') ? 'active' : 'inactive';
        $setting->base_url = $request->base_url;
        $setting->api_token = $request->api_token;
        $setting->default_country_code = $request->default_country_code;
        $setting->test_number = $request->test_number;
        $setting->save();

        session()->forget('whatsapp_setting');
        session()->forget('email_notification_setting');

        return Reply::success(__('messages.updateSuccess'));
    }

    public function sendTestNotification()
    {
        $setting = WhatsappNotificationSetting::first();

        if (!$setting || empty($setting->api_token) || empty($setting->test_number)) {
            return Reply::error('Please configure WhatsApp API settings and test number first.');
        }

        $this->wascriptService->sendText(
            $setting->test_number,
            'This is a test WhatsApp notification from CRM',
            $setting
        );

        return Reply::success('Test WhatsApp notification sent.');
    }

    private function saveWhatsAppNotificationSettings($request): void
    {
        EmailNotificationSetting::whereIn('slug', EmailNotificationSetting::WHATSAPP_NOTIFICATION_SLUGS)
            ->update(['send_whatsapp' => 'no']);

        if ($request->send_whatsapp) {
            EmailNotificationSetting::whereIn('id', $request->send_whatsapp)
                ->whereIn('slug', EmailNotificationSetting::WHATSAPP_NOTIFICATION_SLUGS)
                ->update(['send_whatsapp' => 'yes']);
        }
    }
}
