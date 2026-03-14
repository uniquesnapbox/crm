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
            $apiUrl   = config('services.whatsapp.api_url', 'https://bhashsms.com/api/sendmsgutil.php');
            $user     = config('services.whatsapp.user');
            $password = config('services.whatsapp.password');
            $sender   = config('services.whatsapp.sender');
            $template = config('services.whatsapp.otp_template', 'auth_uniq');
            $timeout  = (int) config('services.whatsapp.timeout', 25);
            $timeout  = max(10, min(60, $timeout));

            $phone = $this->formatMobile($mobile);

            $params = [
                'user'     => $user,
                'pass'     => $password,
                'sender'   => $sender,
                'phone'    => $phone,
                'text'     => $template,
                'priority' => 'wa',
                'stype'    => 'auth',
                'Params'   => $otp,
            ];

            Log::info('BhashSMS Sending', ['phone' => $phone, 'otp' => $otp, 'template' => $template]);

            $http = Http::withoutVerifying()->timeout($timeout)->connectTimeout(15);
            $response = null;

            for ($attempt = 1; $attempt <= 2; $attempt++) {
                try {
                    $response = $http->get($apiUrl, $params);
                    break;
                } catch (\Exception $e) {
                    $isTimeout = str_contains($e->getMessage(), 'timed out')
                              || str_contains($e->getMessage(), 'cURL error 28');
                    if ($attempt === 1 && $isTimeout) {
                        Log::warning('BhashSMS first attempt timed out, retrying once.', ['mobile' => $phone]);
                        continue;
                    }
                    throw $e;
                }
            }

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

    private function formatMobile(string $mobile): string
    {
        $cleaned = preg_replace('/[^0-9]/', '', $mobile);

        if ($cleaned !== $mobile) {
            Log::info('Mobile number formatted', ['original' => $mobile, 'formatted' => $cleaned]);
        }

        return $cleaned;
    }
}
