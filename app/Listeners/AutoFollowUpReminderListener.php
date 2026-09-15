<?php

namespace App\Listeners;

use App\Events\AutoFollowUpReminderEvent;
use App\Models\User;
use App\Notifications\AutoFollowUpReminder;
use Illuminate\Support\Facades\Notification;

class AutoFollowUpReminderListener
{

    /**
     * Handle the event.
     *
     * @param AutoFollowUpReminderEvent $event
     * @return void
     */

    public function handle(AutoFollowUpReminderEvent $event)
    {
        $companyId = $event->followup->lead->company_id;

        $lead = $event->followup->lead;
        $notifyUser = $lead?->assignedTo
            ?: $lead?->addedBy
            ?: $event->followup->addedBy;

        if (!$notifyUser && $lead?->leadAgent?->user) {
            $notifyUser = $lead->leadAgent->user;
        }

        if (!$notifyUser) {
            $notifyUser = User::whereIn('id', User::allAdmins($companyId)->pluck('id'))->get();
        }

        if ($notifyUser) {
            Notification::send($notifyUser, new AutoFollowUpReminder($event->followup));
        }

    }

}
