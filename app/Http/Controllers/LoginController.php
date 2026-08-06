<?php

namespace App\Http\Controllers;

use Exception;
use App\Models\User;
use App\Helper\Reply;
use App\Models\Social;
use Illuminate\Http\Request;
use Laravel\Fortify\Fortify;
use App\Events\TwoFactorCodeEvent;
use App\Traits\SocialAuthSettings;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\LoginRequest;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use \Illuminate\Validation\ValidationException;



use Carbon\Carbon;
use App\Models\WhatsappOtp;
use App\Services\WhatsAppOtpService;

class LoginController extends Controller
{

    use SocialAuthSettings;

    protected $redirectTo = 'account/dashboard';

    public function checkEmail(LoginRequest $request)
    {
        $user = User::where('email', $request->email)
            ->select('id')
            ->where('status', 'active')
            ->where('login', 'enable')
            ->first();

        if (is_null($user)) {
            throw ValidationException::withMessages([
                Fortify::username() => __('messages.invalidOrInactiveAccount'),
            ]);
        }

        return response([
            'status' => 'success'
        ]);
    }

    public function checkCode(Request $request)
    {
        $request->validate([
            'code' => 'required',
        ]);

        $user = User::findOrFail($request->user_id);

        if ($request->code == $user->two_factor_code) {

            // Reset codes and expire_at after verification
            $user->resetTwoFactorCode();

            // Attempt login
            Auth::login($user);

            return redirect()->route('dashboard');
        }

        // Reset codes and expire_at after failure
        $user->resetTwoFactorCode();

        return redirect()->back()->withErrors(['two_factor_code' => __('messages.codeNotMatch')]);
    }

    public function resendCode(Request $request)
    {
        $user = User::findOrFail($request->user_id);
        $user->generateTwoFactorCode();
        event(new TwoFactorCodeEvent($user));

        return Reply::success(__('messages.codeSent'));
    }

    public function sendWhatsappOtp(Request $request)
    {
        $request->validate([
            'mobile' => 'required|string|min:7|max:20',
        ]);

        // Clean the number - digits only
        $mobile  = preg_replace('/[^0-9]/', '', $request->mobile);
        // India only as per gateway note (expects number without 91)
        $country = '91';

        // Find active user with this mobile number
        $user = User::where('mobile', $mobile)
            ->where('status', 'active')
            ->where('login', 'enable')
            ->first();

        if (!$user) {
            return response()->json([
                'status'  => 'error',
                'message' => 'No active account found with this WhatsApp number.',
            ], 422);
        }

        // Delete old unused OTPs for this number
        WhatsappOtp::where('mobile', $mobile)
            ->where('used', false)
            ->delete();

        // Generate 6-digit OTP
        $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        // Save OTP to database
        WhatsappOtp::create([
            'mobile'     => $mobile,
            'otp'        => $otp,
            'expires_at' => Carbon::now()->addMinutes(5),
            'used'       => false,
        ]);

        // Send OTP via WhatsApp
        $service = new WhatsAppOtpService();
        $sent    = $service->sendOtp($mobile, $otp); // gateway expects number without 91

        if (!$sent) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to send OTP. ' . ($service->getLastError() ?? 'Please try again.'),
            ], 500);
        }

        return response()->json([
            'status'  => 'success',
            'message' => 'OTP sent to your WhatsApp number. Check your messages.',
        ]);
    }

    public function verifyWhatsappOtp(Request $request)
    {
        $request->validate([
            'mobile' => 'required|string',
            'otp'    => 'required|string|size:6',
            'device_name' => ['nullable', 'string', 'max:255'],
        ]);

        $mobile  = preg_replace('/[^0-9]/', '', $request->mobile);

        // Find the OTP record
        $otpRecord = WhatsappOtp::where('mobile', $mobile)
            ->where('otp', $request->otp)
            ->where('used', false)
            ->latest()
            ->first();

        // Check OTP exists and is not expired
        if (!$otpRecord || $otpRecord->isExpired()) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Invalid or expired OTP. Please try again.',
            ], 422);
        }

        // Mark OTP as used
        $otpRecord->update(['used' => true]);

        // Find the user with employee details
        $user = User::withoutGlobalScopes()
            ->with(['roles:id,name', 'employeeDetail:user_id,id'])
            ->where('mobile', $mobile)
            ->where('status', 'active')
            ->where('login', 'enable')
            ->first();

        if (!$user) {
            return response()->json([
                'status'  => 'error',
                'message' => __('messages.invalidOrInactiveAccount'),
            ], 422);
        }

        // Log in the user
        Auth::login($user);

        // Generate API token for mobile app
        $token = $user->createToken($request->device_name ?? 'flutter_crm_app')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'roles' => $user->roles->pluck('name')->values(),
            ],
            'employee_id' => $user->employeeDetail?->id ?? $user->id,
            'has_employee_profile' => $user->employeeDetail !== null,
        ]);
    }






    public function redirect($provider)
    {
        $this->setSocailAuthConfigs();

        return Socialite::driver($provider)->redirect();
    }

    public function callback(Request $request, $provider)
    {

        $this->setSocailAuthConfigs();

        try {
            try {
                if ($provider != 'twitter') {
                    $data = Socialite::driver($provider)->stateless()->user(); /* @phpstan-ignore-line */
                } else {
                    $data = Socialite::driver($provider)->user();
                }
            } catch (Exception $e) {

                return redirect()->route('login')->with(['message' => $e->getMessage()]);
            }

            $user = User::where(['email' => $data->email])->first();

            if (!$user) {
                return redirect()->route('login')->with(['message' => __('messages.unAuthorisedUser')]);
            }

            if ($user->status === 'deactive') {
                return redirect()->route('login')->with(['message' => __('auth.failedBlocked')]);
            }

            if ($user->login === 'disable') {
                return redirect()->route('login')->with(['message' => __('auth.failedLoginDisabled')]);
            }

            // User found
            DB::beginTransaction();

            Social::updateOrCreate(['user_id' => $user->id], [
                'social_id' => $data->id,
                'social_service' => $provider,
            ]);

            DB::commit();

            Auth::login($user, true);

            return redirect()->intended($this->redirectPath());
        } catch (Exception $e) {

            return redirect()->route('login')->with(['message' => $e->getMessage()]);
        }
    }

    public function redirectPath()
    {
        if (method_exists($this, 'redirectTo')) {
            return $this->redirectTo();
        }

        return property_exists($this, 'redirectTo') ? $this->redirectTo : '/login';
    }

    public function username()
    {
        return 'email';
    }
}
