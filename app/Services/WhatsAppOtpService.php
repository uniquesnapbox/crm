<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppOtpService
{
    public function sendOtp(string $mobile, string $otp): bool
    {
        if (config('services.whatsapp.driver') === 'log') {
            Log::info('==== WhatsApp OTP [DEV MODE] ====');
            Log::info('Mobile : ' . $mobile);
            Log::info('OTP    : ' . $otp);
            Log::info('=================================');
            return true;
        }

        return $this->sendViaBhashSMS($mobile, $otp);
    }

    private function sendViaBhashSMS(string $mobile, string $otp): bool
    {
        try {
            $user     = config('services.whatsapp.user');
            $password = config('services.whatsapp.password');
            $sender   = config('services.whatsapp.sender');

            // Keep last 10 digits only (remove 91/92 country code)
            $phone = substr(preg_replace('/^(91|92)/', '', $mobile), -10);

            Log::info('BhashSMS Sending | Phone: ' . $phone . ' | OTP: ' . $otp);

            $response = Http::get('http://bhashsms.com/api/sendmsg.php', [
                'user'     => $user,
                'pass'     => $password,   // ← 'pass' not 'password'
                'sender'   => $sender,
                'phone'    => $phone,      // ← 10 digits only
                'text'     => 'auth_uniq', // ← template name
                'priority' => 'wa',
                'stype'    => 'auth',      // ← auth not normal
                'Params'   => $otp,        // ← OTP value
            ]);

            Log::info('BhashSMS Response: ' . $response->body());

            if (
                $response->successful()
                && !str_contains($response->body(), 'Error')
                && !str_contains($response->body(), 'Credits')
                && !str_contains($response->body(), 'Incorrect')
            ) {
                return true;
            }

            Log::error('BhashSMS Failed: ' . $response->body());
            return false;
        } catch (\Exception $e) {
            Log::error('BhashSMS Exception: ' . $e->getMessage());
            return false;
        }
    }
}
