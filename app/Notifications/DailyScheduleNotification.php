<?php

namespace App\Notifications;

use App\Models\User;
use App\Models\ModuleSetting;
use Illuminate\Bus\Queueable;
use App\Models\EmailNotificationSetting;
use App\Models\RoleUser;
use App\Models\PushNotificationSetting;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use NotificationChannels\OneSignal\OneSignalChannel;
use NotificationChannels\OneSignal\OneSignalMessage;

class DailyScheduleNotification extends BaseNotification
{
    use Queueable;


    /**
     * Create a new notification instance.
     */

     private $userData;
     private $userId;
     private $userModules;
     protected $company;

    public function __construct($userData, $userId)
    {
        $this->userData = $userData;
        $this->userId = $userId;
        $this->company = $this->userData['user'][$this->userId]->company;
        $this->userModules = $this->userModules($userId);
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via($notifiable)
    {
        $setting = EmailNotificationSetting::where('slug', 'daily-schedule-notification')->first();

        $moduleEnabled = in_array('tasks', $this->userModules) || in_array('events', $this->userModules)
        || in_array('holidays', $this->userModules) || in_array('leaves', $this->userModules)
        || in_array('recruit', $this->userModules);

        $via = [];
        if ($setting?->send_email === 'yes' && $moduleEnabled) {
            $via[] = 'mail';
        }

        $pushSetting = PushNotificationSetting::query()
            ->where('status', 'active')
            ->first();
        $appId = trim((string) ($pushSetting?->onesignal_app_id ?? ''));
        $restKey = trim((string) ($pushSetting?->onesignal_rest_api_key ?? ''));
        if ($setting?->send_push === 'yes'
            && $appId !== ''
            && $restKey !== ''
            && !str_contains(strtolower($appId), 'your-')
            && !str_contains(strtolower($restKey), 'your-')) {
            $via[] = OneSignalChannel::class;
        }

        return $via;
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail($notifiable)
    {
        $build = parent::build();
        $taskUrl = getDomainSpecificUrl(route('dashboard'), null);
        $eventUrl = getDomainSpecificUrl(route('dashboard'), null);
        $holidayUrl = getDomainSpecificUrl(route('dashboard'), null);
        $leaveUrl = getDomainSpecificUrl(route('dashboard'), null);

        $content = __('email.dailyScheduleReminder.content') . ':<br>';
        $content .= '<br>Pending Calls: ' . $this->pendingCalls();
        $content .= '<br>Pending Tasks: ' . ($this->userData['tasks'][$this->userId] ?? 0);
        $content .= '<br>Today\'s Follow-ups: ' . $this->todayFollowUps();
        $content .= '<br>Meetings: ' . $this->meetings();
        $content .= '<br>Demos: ' . $this->demos();

        if(in_array('tasks', $this->userModules))
        {
            $content .= '<br>'.__('email.dailyScheduleReminder.taskText') .': <a class="text-dark-grey text-decoration-none" href='.$taskUrl.'> '. $this->userData['tasks'][$this->userId] .'</a>';
        }

        if(in_array('events', $this->userModules))
        {
            $content .= '<br>'. __('email.dailyScheduleReminder.eventText') .': <a class="text-dark-grey" href='.$eventUrl.'> '. $this->userData['events'][$this->userId] .'</a>';
        }

        if(in_array('holidays', $this->userModules))
        {
            $content .= '<br>'. __('email.dailyScheduleReminder.holidayText') .': <a class="text-dark-grey" href='.$holidayUrl.'> '. $this->userData['holidays'][$this->userId] .'</a>';
        }

        if(in_array('leaves', $this->userModules))
        {
            $content .= '<br>'. __('email.dailyScheduleReminder.leavesText') .': <a class="text-dark-grey text-decoration-none" href='.$leaveUrl.'> '. $this->userData['leaves'][$this->userId] .'</a>';
        }


        if(module_enabled('Recruit') && in_array('recruit', $this->userModules))
        {
            $interviewUrl = getDomainSpecificUrl(route('dashboard'), null);

            $content .= '<br>'. __('email.dailyScheduleReminder.interviewText') .': <a class="text-dark-grey text-decoration-none" href='.$interviewUrl.'> '. $this->userData['interview'][$this->userId] .'</a>';
        }

        return $build
            ->subject(__('email.dailyScheduleReminder.subject', ['date' => now()->format($this->company->date_format)]))
            ->markdown('mail.email', [
                'notifiableName' => $this->userData['user'][$this->userId]->name,
                'content' => $content
            ]);
    }

    public function toOneSignal($notifiable): OneSignalMessage
    {
        $body = 'Calls: ' . $this->pendingCalls()
            . ' | Tasks: ' . ($this->userData['tasks'][$this->userId] ?? 0)
            . ' | Follow-ups: ' . $this->todayFollowUps()
            . ' | Meetings: ' . $this->meetings()
            . ' | Demos: ' . $this->demos()
            . ' | Calendar: ' . ($this->userData['events'][$this->userId] ?? 0)
            . ' | Interviews: ' . ($this->userData['interview'][$this->userId] ?? 0);

        return OneSignalMessage::create()
            ->setSubject('Today\'s CRM schedule')
            ->setBody($body)
            ->setData('type', 'daily_schedule_summary')
            ->setData('pending_calls', $this->pendingCalls())
            ->setData('pending_tasks', (int) ($this->userData['tasks'][$this->userId] ?? 0))
            ->setData('today_followups', $this->todayFollowUps())
            ->setData('meetings', $this->meetings())
            ->setData('demos', $this->demos())
            ->setData('calendar_events', (int) ($this->userData['events'][$this->userId] ?? 0))
            ->setData('interviews', (int) ($this->userData['interview'][$this->userId] ?? 0))
            ->setData('leaves', (int) ($this->userData['leaves'][$this->userId] ?? 0))
            ->setData('holidays', (int) ($this->userData['holidays'][$this->userId] ?? 0));
    }

    private function todayFollowUps(): int
    {
        return (int) ($this->userData['today_followups'][$this->userId] ?? 0);
    }

    private function pendingCalls(): int
    {
        return (int) ($this->userData['pending_calls'][$this->userId] ?? 0);
    }

    private function meetings(): int
    {
        return (int) ($this->userData['meetings'][$this->userId] ?? 0);
    }

    private function demos(): int
    {
        return (int) ($this->userData['demos'][$this->userId] ?? 0);
    }

    public function userModules($userId)
    {
        $userData = User::find($userId);
        $roles = $userData->roles;
        $userRoles = $roles->pluck('name')->toArray();

        $module = new \App\Models\ModuleSetting();

        if (in_array('admin', $userRoles)) {
            $module = $module->where('type', 'admin');

        }
        elseif (in_array('employee', $userRoles)) {
            $module = $module->where('type', 'employee');
        }

        $module = $module->where('status', 'active');
        $module->select('module_name');

        $module = $module->get();
        $moduleArray = [];

        foreach ($module->toArray() as $item) {
            $moduleArray[] = array_values($item)[0];
        }

        return $moduleArray;

    }

}
