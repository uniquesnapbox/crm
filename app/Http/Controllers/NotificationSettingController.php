<?php

namespace App\Http\Controllers;

use App\Helper\Reply;
use App\Models\EmailNotificationSetting;
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
        $allowedTabs = ['email-setting', 'whatsapp-setting'];

        if (!in_array($tab, $allowedTabs, true)) {
            $tab = 'email-setting';
        }

        $this->emailSettings = EmailNotificationSetting::all();
        $this->whatsappSettings = WhatsappNotificationSetting::first() ?: new WhatsappNotificationSetting([
            'status' => 'inactive',
            'base_url' => 'https://api-whatsapp.wascript.com.br',
            'api_token' => null,
            'default_country_code' => null,
            'test_number' => null,
            'send_lead_created_message' => 'yes',
            'send_lead_interest_message' => 'yes',
            'send_ticket_message' => 'yes',
            'send_ticket_assigned_staff_message' => 'yes',
            'send_ticket_assigned_client_message' => 'yes',
            'send_ticket_resolved_client_message' => 'yes',
            'send_task_assigned_message' => 'yes',
            'send_task_daily_pending_message' => 'yes',
            'send_task_completed_message' => 'yes',
            'lead_created_template' => WhatsappNotificationSetting::DEFAULT_LEAD_CREATED_TEMPLATE,
            'lead_interest_template' => WhatsappNotificationSetting::DEFAULT_LEAD_INTEREST_TEMPLATE,
            'lead_created_sender_number' => config('app.admin_whatsapp', ''),
            'ticket_assigned_staff_template' => WhatsappNotificationSetting::DEFAULT_TICKET_ASSIGNED_STAFF_TEMPLATE,
            'ticket_assigned_client_template' => WhatsappNotificationSetting::DEFAULT_TICKET_ASSIGNED_CLIENT_TEMPLATE,
            'ticket_resolved_client_template' => WhatsappNotificationSetting::DEFAULT_TICKET_RESOLVED_CLIENT_TEMPLATE,
            'task_assigned_staff_template' => WhatsappNotificationSetting::DEFAULT_TASK_ASSIGNED_TEMPLATE,
            'task_daily_pending_template' => WhatsappNotificationSetting::DEFAULT_TASK_DAILY_PENDING_TEMPLATE,
            'task_completed_template' => WhatsappNotificationSetting::DEFAULT_TASK_COMPLETED_TEMPLATE,
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
