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
            'send_lead_created_message' => 'no',
            'lead_created_template' => WhatsappNotificationSetting::DEFAULT_LEAD_CREATED_TEMPLATE,
            'lead_created_sender_number' => config('app.admin_whatsapp', ''),
            'ticket_assigned_staff_template' => 'A new ticket has been assigned to you. Ticket #{{ticket_number}}: {{subject}}',
            'ticket_assigned_client_template' => 'Your ticket #{{ticket_number}} has been forwarded to our team. We will get back to you soon.',
            'ticket_resolved_client_template' => 'Your ticket #{{ticket_number}} has been resolved. If you need anything else, please let us know.',
            'task_assigned_staff_template' => 'A new task has been assigned to you. Task: {{task_heading}}',
            'last_send_status' => null,
            'last_error_message' => null,
            'last_http_status' => null,
            'last_response_body' => null,
            'last_sent_at' => null,
            'last_normalized_phone' => null,
            'last_response_message' => null,
            'last_delivery_status' => null,
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
