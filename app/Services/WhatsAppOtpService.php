<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppOtpService
{
    private ?string $lastError = null;

    public function getLastError(): ?string
    {
        return $this->lastError;
    }

    /**
     * Send OTP to WhatsApp number
     *
     * @param string $mobile
     * @param string $otp
     * @return bool
     */
    public function sendOtp(string $mobile, string $otp): bool
    {
        // DEV mode: just log OTP
        if (config('services.whatsapp.driver') === 'log') {
            Log::info('==== WhatsApp OTP [DEV MODE] ====');
            Log::info('Mobile : ' . $mobile);
            Log::info('OTP    : ' . $otp);
            Log::info('=================================');
            $this->lastError = null;
            return true;
        }

        // PRODUCTION: send via BhashSMS
        return $this->sendViaBhashSMS($mobile, $otp);
    }

    /**
     * Send a plain WhatsApp message (non-OTP) via BhashSMS.
     *
     * @param string $mobile number without country prefix 91 (BhashSMS requirement)
     * @param string $message text or template name depending on your account setup
     */
    public function sendMessage(string $mobile, string $message): bool
    {
        try {
            $apiUrl   = config('services.whatsapp.api_url', 'http://bhashsms.com/api/sendmsg.php');
            $user     = config('services.whatsapp.user');
            $password = config('services.whatsapp.password');
            $sender   = config('services.whatsapp.sender');
            $timeout  = (int) config('services.whatsapp.timeout', 25);
            $timeout  = max(10, min(60, $timeout));

            $phone = $this->formatMobile($mobile);

            $params = [
                'user'     => $user,
                'pass'     => $password,
                'sender'   => $sender,
                'phone'    => $phone,
                'text'     => $message,
                'priority' => 'wa',
                'stype'    => 'normal',
            ];

            $http = Http::withoutVerifying()->timeout($timeout)->connectTimeout(15);
            $response = $http->get($apiUrl, $params);

            $body = $response->body();
            if (
                $response->successful()
                && !str_contains($body, 'Error')
                && !str_contains($body, 'Credits')
                && !str_contains($body, 'Incorrect')
            ) {
                $this->lastError = null;
                return true;
            }

            $this->lastError = $body;
            Log::error('BhashSMS sendMessage failed: ' . $body);
            return false;
        } catch (\Exception $e) {
            $this->lastError = $e->getMessage();
            Log::error('BhashSMS sendMessage exception: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Send OTP via BhashSMS API
     *
     * @param string $mobile
     * @param string $otp
     * @return bool
     */
    private function sendViaBhashSMS(string $mobile, string $otp): bool
    {
        try {
            $apiUrl   = config('services.whatsapp.api_url', 'http://bhashsms.com/api/sendmsg.php');
            $user     = config('services.whatsapp.user');
            $password = config('services.whatsapp.password');
            $sender   = config('services.whatsapp.sender');
            $template = config('services.whatsapp.otp_template', 'auth_uniq'); // template name on BhashSMS panel
            $timeout  = (int) config('services.whatsapp.timeout', 25);
            $timeout  = max(10, min(60, $timeout));

            $phone = $this->formatMobile($mobile);

            // BhashSMS WhatsApp OTP (template-based)
            $params = [
                'user'     => $user,
                'pass'     => $password,
                'sender'   => $sender,
                'phone'    => $phone,            // per docs: number without country prefix 91
                'text'     => $template,         // template name configured at BhashSMS
                'priority' => 'wa',
                'stype'    => 'auth',            // authentication template per BhashSMS doc
                'Params'   => $otp,              // template variable
            ];

            Log::info('BhashSMS Sending', ['phone' => $phone, 'otp' => $otp, 'template' => $template]);

            $http = Http::withoutVerifying()->timeout($timeout)->connectTimeout(15);
            $response = null;

            // Retry once if timeout
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

            Log::info('BhashSMS Response: ' . ($response ? $response->body() : 'No response'));

            // Check if response is successful
            if (
                $response
                && $response->successful()
                && !str_contains($response->body(), 'Error')
                && !str_contains($response->body(), 'Credits')
                && !str_contains($response->body(), 'Incorrect')
            ) {
                $this->lastError = null;
                return true;
            }

            $this->lastError = $response ? $response->body() : 'No response';
            Log::error('BhashSMS Failed: ' . $this->lastError);
            return false;

        } catch (\Exception $e) {
            $this->lastError = $e->getMessage();
            Log::error('BhashSMS Exception: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Format mobile number - digits only
     *
     * @param string $mobile
     * @return string
     */
    private function formatMobile(string $mobile): string
    {
        $cleaned = preg_replace('/[^0-9]/', '', $mobile);

        if ($cleaned !== $mobile) {
            Log::info('Mobile number formatted', ['original' => $mobile, 'formatted' => $cleaned]);
        }

        return $cleaned;
    }
}
