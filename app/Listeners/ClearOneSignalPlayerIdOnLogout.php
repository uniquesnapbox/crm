<?php

namespace App\Listeners;

use App\Models\User;
use Illuminate\Auth\Events\Logout;

class ClearOneSignalPlayerIdOnLogout
{
    public function handle(Logout $event): void
    {
        if (!$event->user instanceof User || blank($event->user->onesignal_player_id)) {
            return;
        }

        $event->user->forceFill([
            'onesignal_player_id' => null,
            'onesignal_mobile_subscription_id' => null,
        ])->saveQuietly();
    }
}
