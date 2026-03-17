<?php

namespace App\Http\Controllers;

use App\Helper\Reply;
use App\Models\EmailNotificationSetting;
use App\Models\PusherSetting;
use App\Models\PushNotificationSetting;
use App\Models\SlackSetting;
use App\Models\SmtpSetting;
use App\Models\WhatsappNotificationSetting;

class NotificationSettingController extends AccountBaseController
{
    private function resolveSmtpPassword(SmtpSetting $smtpSetting): string
    {
        try {
            return (string) ($smtpSetting->mail_password ?? '');
        } catch (\Throwable $e) {
            return '';
        }
    }


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

    public function index()
    {
        $tab = request('tab');

        $this->emailSettings = EmailNotificationSetting::all();

        $this->slackSettings = SlackSetting::first() ?: new SlackSetting([
            'status' => 'inactive',
            'slack_webhook' => null,
            'slack_logo_url' => null,
        ]);
        $this->pushSettings = PushNotificationSetting::first() ?: new PushNotificationSetting([
            'status' => 'inactive',
            'onesignal_app_id' => null,
            'onesignal_rest_api_key' => null,
        ]);
        $this->pusherSettings = PusherSetting::first() ?: new PusherSetting([
            'status' => 0,
            'pusher_app_id' => null,
            'pusher_app_key' => null,
            'pusher_app_secret' => null,
            'pusher_cluster' => null,
            'force_tls' => 0,
            'taskboard' => 0,
            'messages' => 0,
        ]);
        $this->whatsappSettings = WhatsappNotificationSetting::first() ?: new WhatsappNotificationSetting([
            'status' => 'inactive',
            'base_url' => 'https://api-whatsapp.wascript.com.br',
            'api_token' => null,
            'default_country_code' => null,
            'test_number' => null,
        ]);

        switch ($tab) {
        case 'slack-setting':
            $this->checkedAll = $this->emailSettings->count() == $this->emailSettings->filter(function ($value) {
                    return $value->send_slack == 'yes';
            })->count();

            $this->view = 'notification-settings.ajax.slack-setting';
            break;

        case 'push-notification-setting':
            $this->checkedAll = $this->emailSettings->count() == $this->emailSettings->filter(function ($value) {
                    return $value->send_push == 'yes';
            })->count();

            $this->view = 'notification-settings.ajax.push-notification-setting';
            break;

        case 'pusher-setting':
            $this->view = 'notification-settings.ajax.pusher-setting';
            break;

        case 'whatsapp-setting':
            $this->whatsappEventSettings = $this->emailSettings
                ->whereIn('slug', EmailNotificationSetting::WHATSAPP_NOTIFICATION_SLUGS)
                ->values();
            $this->checkedAll = $this->whatsappEventSettings->count() > 0
                && $this->whatsappEventSettings->count() === $this->whatsappEventSettings->filter(function ($value) {
                    return $value->send_whatsapp == 'yes';
                })->count();

            $this->view = 'notification-settings.ajax.whatsapp-setting';
            break;

        default:
            $this->checkedAll = $this->emailSettings->count() == $this->emailSettings->filter(function ($value) {
                    return $value->send_email == 'yes';
            })->count();

            $this->smtpSetting = SmtpSetting::first() ?: new SmtpSetting();
            $this->smtpPassword = $this->resolveSmtpPassword($this->smtpSetting);
            $this->view = 'notification-settings.ajax.email-setting';
            break;
        }

        $this->activeTab = $tab ?: 'email-setting';

        if (request()->ajax()) {
            $html = view($this->view, $this->data)->render();

            return Reply::dataOnly(['status' => 'success', 'html' => $html, 'title' => $this->pageTitle, 'activeTab' => $this->activeTab]);
        }

        return view('notification-settings.index', $this->data);
    }

}
