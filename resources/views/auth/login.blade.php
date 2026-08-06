<x-auth>
    @push('styles')
        <style>
            .auth-login-page .login_section {
                padding-top: 2.5rem;
                padding-bottom: 2.5rem;
            }

            .auth-login-page .login_box.login-page-card {
                max-width: 470px;
                border-radius: 22px !important;
                box-shadow: 0 22px 60px rgba(16, 24, 40, 0.18);
                padding: 30px 28px;
                border: 1px solid #e8ecf4;
                backdrop-filter: blur(2px);
            }

            .auth-login-page .login_box h3 {
                margin-bottom: 0.35rem !important;
                font-size: 1.65rem;
                font-weight: 700;
                color: #111827;
            }

            .auth-login-page .login-subtitle {
                font-size: 0.9rem;
                color: #6b7280;
                margin-bottom: 1.25rem;
            }

            .auth-login-page .primary-badge {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                font-size: 0.73rem;
                color: #1d4ed8;
                background: #dbeafe;
                border: 1px solid #bfdbfe;
                border-radius: 999px;
                padding: 4px 10px;
                margin-bottom: 12px;
                font-weight: 600;
                letter-spacing: 0.02em;
            }

            .auth-login-page .form-control.height-50 {
                border-radius: 12px;
                border: 1px solid #d5dbea;
                box-shadow: inset 0 1px 1px rgba(17, 24, 39, 0.03);
            }

            .auth-login-page .form-control.height-50:focus {
                border-color: #3b82f6;
                box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.14);
            }

            .auth-login-page .login-primary-btn {
                background: linear-gradient(135deg, #ff5a1f, #ff3b00);
                border: 0;
                border-radius: 14px;
                box-shadow: 0 10px 24px rgba(255, 75, 28, 0.36);
                transition: transform 0.2s ease, box-shadow 0.2s ease, filter 0.2s ease;
            }

            .auth-login-page .login-primary-btn:hover,
            .auth-login-page .login-primary-btn:focus {
                transform: translateY(-1px);
                box-shadow: 0 14px 26px rgba(255, 75, 28, 0.42);
                filter: brightness(1.02);
            }

            .auth-login-page .wa-login-panel {
                margin-top: 1.25rem;
                padding: 16px;
                border: 1px solid #e5e7eb;
                border-radius: 16px;
                background: #f9fafb;
                text-align: left;
            }

            .auth-login-page .wa-panel-title {
                font-size: 0.85rem;
                font-weight: 600;
                color: #047857;
                margin-bottom: 0.75rem;
                display: inline-flex;
                align-items: center;
                gap: 6px;
            }

            .auth-login-page .wa-secondary-btn {
                border-radius: 12px;
                background: #16a34a;
                border: 0;
                box-shadow: 0 8px 18px rgba(22, 163, 74, 0.26);
                transition: transform 0.2s ease, box-shadow 0.2s ease;
            }

            .auth-login-page .wa-secondary-btn:hover,
            .auth-login-page .wa-secondary-btn:focus {
                transform: translateY(-1px);
                box-shadow: 0 12px 22px rgba(22, 163, 74, 0.32);
            }

            .auth-login-page .wa-verify-btn {
                border-radius: 12px;
            }

            .auth-login-page .login_or_separator {
                margin: 1.2rem 0 0.95rem;
            }

            @media (max-width: 767.98px) {
                .auth-login-page .login_section {
                    padding-top: 1.5rem;
                    padding-bottom: 1.5rem;
                }

                .auth-login-page .login_box.login-page-card {
                    border-radius: 16px !important;
                    padding: 22px 16px;
                    box-shadow: 0 14px 34px rgba(16, 24, 40, 0.14);
                }

                .auth-login-page .login_box h3 {
                    font-size: 1.45rem;
                }
            }
        </style>
    @endpush

    <form id="login-form" action="{{ route('login') }}" class="ajax-form" method="POST">
        {{ csrf_field() }}
        <span class="primary-badge">Primary Login</span>
        <h3 class="text-capitalize mb-4 f-w-500">@lang('app.login')</h3>
        <p class="login-subtitle">Use email and password for fastest access</p>

        <script>
            const facebook = "{{ route('social_login', 'facebook') }}";
            const google = "{{ route('social_login', 'google') }}";
            const twitter = "{{ route('social_login', 'twitter') }}";
            const linkedin = "{{ route('social_login', 'linkedin') }}";
        </script>

        @if ($socialAuthSettings->google_status == 'enable')
            <a class="mb-3 height_50 rounded f-w-500" onclick="window.location.href = google;">
                <span><img src="{{ asset('img/google.png') }}" alt="Google"/></span>
                @lang('auth.signInGoogle')</a>
        @endif
        @if ($socialAuthSettings->facebook_status == 'enable')
            <a class="mb-3 height_50 rounded f-w-500" onclick="window.location.href = facebook;">
                <span><img src="{{ asset('img/fb.png') }}" alt="Google"/></span>
                @lang('auth.signInFacebook')
            </a>
        @endif
        @if ($socialAuthSettings->twitter_status == 'enable')
            <a class="mb-3 height_50 rounded f-w-500" onclick="window.location.href = twitter;">
                <span><img src="{{ asset('img/twitter.png') }}" alt="Google"/></span>
                @lang('auth.signInTwitter')
            </a>
        @endif
        @if ($socialAuthSettings->linkedin_status == 'enable')
            <a class="mb-3 height_50 rounded f-w-500" onclick="window.location.href = linkedin;">
                <span><img src="{{ asset('img/linkedin.png') }}" alt="Google"/></span>
                @lang('auth.signInLinkedin')
            </a>
        @endif

        @if ($socialAuthSettings->social_auth_enable)
            <p class="position-relative my-4">@lang('auth.useEmail')</p>
        @endif

        <div class="form-group text-left">
            <label for="email">@lang('auth.email')</label>
            <input tabindex="1" type="email" name="email"
                   class="form-control height-50 f-15 light_text @error('email') is-invalid @enderror"
                   autofocus
                   value="{{request()->old('email')}}"
                   placeholder="@lang('auth.email')" id="email">
            @if ($errors->has('email'))
                <div class="invalid-feedback">{{ $errors->first('email') }}</div>
            @endif
            @if ($socialAuthSettings->social_auth_enable_count>1)
                <div class="forgot_pswd mt-2" id="forget-pass-email-section">
                    <a href="{{ url('forgot-password') }}">@lang('app.forgotPassword')</a>
                </div>
            @endif
        </div>

        <div id="password-section">
            <div class="form-group text-left">
                <label for="password">@lang('app.password')</label>
                <x-forms.input-group>
                    <input type="password" name="password" id="password"
                           placeholder="@lang('placeholders.password')" tabindex="3"
                           class="form-control height-50 f-15 light_text @error('password') is-invalid @enderror">

                    <x-slot name="append">
                        <button type="button" data-toggle="tooltip"
                                data-original-title="@lang('app.viewPassword')"
                                class="btn btn-outline-secondary border-grey height-50 toggle-password">
                            <i
                                class="fa fa-eye"></i></button>
                    </x-slot>

                </x-forms.input-group>
                @if ($errors->has('password'))
                    <div class="invalid-feedback d-block">{{ $errors->first('password') }}</div>
                @endif
            </div>
            <div class="forgot_pswd mb-3">
                <a href="{{ url('forgot-password') }}">@lang('app.forgotPassword')</a>
            </div>

            <div class="form-group text-left ">
                <input id="checkbox-signup" class="cursor-pointer" type="checkbox" name="remember">
                <label for="checkbox-signup" class="cursor-pointer">@lang('app.rememberMe')</label>
            </div>

            @if ($globalSetting->google_recaptcha_status == 'active')
                <div class="form-group" id="captcha_container"></div>
            @endif

            <input type="hidden" id="g_recaptcha" name="g_recaptcha">

            @if ($errors->has('g-recaptcha-response'))
                <div
                    class="invalid-feedback  d-block text-left">{{ $errors->first('g-recaptcha-response') }}
                </div>
            @endif

            <button type="submit" id="submit-login"
                    class="btn-primary login-primary-btn f-w-500 rounded w-100 height-50 f-18">
                @lang('app.login') <i class="fa fa-arrow-right pl-1"></i>
            </button>

            @if ($company->allow_client_signup)
                <a href="{{ route('register') }}"
                   class="btn-secondary f-w-500 rounded w-100 height-50 f-15 mt-3">
                    @lang('app.signUpAsClient')
                </a>
            @endif
        </div>


    </form>




    {{-- ============ WhatsApp OTP Login Section ============ --}}
<div class="position-relative my-4 login_or_separator">
    <hr/>
    <span class="position-absolute bg-white px-3 text-muted"
          style="top:-12px; left:50%; transform:translateX(-50%); font-size:13px;">
        OR
    </span>
</div>

<div class="wa-login-panel">
    <div class="wa-panel-title"><i class="fab fa-whatsapp"></i> Secondary Option: WhatsApp Login</div>
{{-- Step 1: Enter WhatsApp Number --}}
<div id="wa-number-step">
    <div class="form-group text-left">
        <label class="f-15 mb-2">Login with WhatsApp Number</label>
        <input type="tel"
               id="wa_mobile"
               class="form-control height-50 f-15 light_text"
               placeholder="e.g. 923001234567 (with country code)"/>
        <small class="text-muted">Enter number with country code, no + or spaces</small>
    </div>
    <div id="wa-send-error" class="text-danger f-13 mb-2 d-none"></div>
    <button type="button"
            id="wa-send-btn"
            onclick="waSendOtp()"
            class="btn wa-secondary-btn text-white f-w-500 rounded w-100 height-50 f-15">
        <i class="fab fa-whatsapp mr-2"></i> Send OTP via WhatsApp
    </button>
</div>

{{-- Step 2: Enter OTP --}}
<div id="wa-otp-step" class="d-none">
    <div class="form-group text-left">
        <label class="f-15">
            <i class="fab fa-whatsapp text-success"></i>
            Enter OTP sent to your WhatsApp
        </label>
        <input type="text"
               id="wa_otp"
               class="form-control height-50 f-15 light_text text-center f-20 letter-spacing-5"
               placeholder="_ _ _ _ _ _"
               maxlength="6"
               style="letter-spacing: 8px; font-size: 22px;"/>
        <small class="text-muted">OTP expires in 5 minutes</small>
    </div>
    <div id="wa-otp-error" class="text-danger f-13 mb-2 d-none"></div>
    <button type="button"
            id="wa-verify-btn"
            onclick="waVerifyOtp()"
            class="btn btn-primary wa-verify-btn f-w-500 rounded w-100 height-50 f-15">
        Verify OTP &amp; Login
    </button>
    <button type="button"
            onclick="waReset()"
            class="btn btn-link w-100 mt-2 f-13 text-muted">
        &larr; Use different number
    </button>
</div>
{{-- ============ End WhatsApp Section ============ --}}
</div>


    <x-slot name="scripts">


        @if ($globalSetting->google_recaptcha_status == 'active' && $globalSetting->google_recaptcha_v2_status == 'active')
            <script src="https://www.google.com/recaptcha/api.js?onload=onloadCallback&render=explicit" async
                    defer></script>
            <script>
                var gcv3;
                var onloadCallback = function () {
                    // Renders the HTML element with id 'captcha_container' as a reCAPTCHA widget.
                    // The id of the reCAPTCHA widget is assigned to 'gcv3'.
                    gcv3 = grecaptcha.render('captcha_container', {
                        'sitekey': '{{ $globalSetting->google_recaptcha_v2_site_key }}',
                        'theme': 'light',
                        'callback': function (response) {
                            if (response) {
                                $('#g_recaptcha').val(response);
                            }
                        },
                    });
                };
            </script>
        @endif
        @if ($globalSetting->google_recaptcha_status == 'active' && $globalSetting->google_recaptcha_v3_status == 'active')
            <script
                src="https://www.google.com/recaptcha/api.js?render={{ $globalSetting->google_recaptcha_v3_site_key }}"></script>
            <script>
                grecaptcha.ready(function () {
                    grecaptcha.execute('{{ $globalSetting->google_recaptcha_v3_site_key }}').then(function (token) {
                        // Add your logic to submit to your backend server here.
                        $('#g_recaptcha').val(token);
                    });
                });
            </script>
        @endif

        <script>

            $(document).ready(function () {

                $("form#login-form").submit(function () {
                    const button = $('form#login-form').find('#submit-login');

                    const text = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> {{__('app.loading')}}';

                    button.prop("disabled", true);
                    button.html(text);
                });

                @if (session('message'))
                Swal.fire({
                    icon: 'error',
                    text: '{{ session('message') }}',
                    showConfirmButton: true,
                    customClass: {
                        confirmButton: 'btn btn-primary',
                    },
                    showClass: {
                        popup: 'swal2-noanimation',
                        backdrop: 'swal2-noanimation'
                    },
                })
                @endif

            });
        </script>





<script>
function waSendOtp() {
    const mobile = document.getElementById('wa_mobile').value.trim();
    // const errDiv = document.getElementById('wa-number-error');
      const errDiv = document.getElementById('wa-send-error')
    const btn    = document.getElementById('wa-send-btn');
    errDiv.classList.add('d-none');
    if (!mobile) { errDiv.textContent = 'Please enter your WhatsApp number.'; errDiv.classList.remove('d-none'); return; }
    btn.disabled = true;
    btn.textContent = 'Sending...';
    fetch('{{ route("whatsapp.send_otp") }}', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
        body: JSON.stringify({ mobile: mobile })
    })
    .then(r => r.json())
    .then(data => {
        btn.disabled = false; btn.textContent = 'Send OTP via WhatsApp';
        if (data.status === 'success') {
            document.getElementById('wa-number-step').classList.add('d-none');
            document.getElementById('wa-otp-step').classList.remove('d-none');
        } else { errDiv.textContent = data.message; errDiv.classList.remove('d-none'); }
    })
    .catch(() => { btn.disabled = false; btn.textContent = 'Send OTP via WhatsApp'; errDiv.textContent = 'Something went wrong.'; errDiv.classList.remove('d-none'); });
}

function waVerifyOtp() {
    const mobile = document.getElementById('wa_mobile').value.trim();
    const otp    = document.getElementById('wa_otp').value.trim();
    const errDiv = document.getElementById('wa-otp-error');
    const btn    = document.getElementById('wa-verify-btn');
    errDiv.classList.add('d-none');
    if (!otp || otp.length !== 6) { errDiv.textContent = 'Please enter the 6-digit OTP.'; errDiv.classList.remove('d-none'); return; }
    btn.disabled = true; btn.textContent = 'Verifying...';
    fetch('{{ route("whatsapp.verify_otp") }}', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
        body: JSON.stringify({ mobile: mobile, otp: otp })
    })
    .then(r => r.json())
    .then(data => {
        if (data.status === 'success') { window.location.href = data.redirect; }
        else { btn.disabled = false; btn.textContent = 'Verify OTP & Login'; errDiv.textContent = data.message; errDiv.classList.remove('d-none'); }
    })
    .catch(() => { btn.disabled = false; btn.textContent = 'Verify OTP & Login'; errDiv.textContent = 'Something went wrong.'; errDiv.classList.remove('d-none'); });
}

function waReset() {
    document.getElementById('wa-number-step').classList.remove('d-none');
    document.getElementById('wa-otp-step').classList.add('d-none');
    document.getElementById('wa_otp').value = '';
}
</script>
    </x-slot>

</x-auth>
