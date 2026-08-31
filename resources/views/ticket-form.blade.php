<!DOCTYPE html>

<html lang="en">

<head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="{{ asset('vendor/css/all.min.css') }}">

    <!-- Template CSS -->
    <link type="text/css" rel="stylesheet" media="all" href="{{ asset('css/main.css') }}">

    <!-- DatePicker CSS -->
    <link rel="stylesheet" href="{{ asset('vendor/css/datepicker.min.css') }}">

    <title>@lang($pageTitle)</title>
    <meta name="msapplication-TileColor" content="#ffffff">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ $company->favicon_url ?? '' }}">
    <meta name="msapplication-TileImage" content="{{ $company->favicon ?? '' }}">

    <meta name="theme-color" content="#ffffff">

    @include('sections.theme_css')

    @isset($activeSettingMenu)
        <style>
            .preloader-container {
                margin-left: 510px;
                width: calc(100% - 510px)
            }

        </style>
    @endisset

    @stack('styles')

    <style>
        body {
            overflow-x: hidden;
            background:
                radial-gradient(circle at top right, rgba(37, 99, 235, 0.08), transparent 38%),
                radial-gradient(circle at bottom left, rgba(14, 165, 233, 0.08), transparent 42%),
                #f4f7fb;
            min-height: 100vh;
        }

        .ticket-public-wrap {
            max-width: 920px;
            margin: 26px auto;
            padding: 0 14px;
        }

        .ticket-public-wrap.ticket-public-wrap--narrow {
            max-width: 720px;
        }

        .ticket-public-card {
            background: #fff;
            border: 1px solid #e6ecf4;
            border-radius: 18px;
            box-shadow: 0 20px 60px rgba(15, 23, 42, .08);
            padding: 26px;
        }

        .ticket-public-head {
            text-align: center;
            margin-bottom: 16px;
        }

        .ticket-public-logo {
            height: 52px;
            max-width: 220px;
            object-fit: contain;
            margin-bottom: 12px;
        }

        .ticket-public-title {
            font-size: 24px;
            font-weight: 700;
            letter-spacing: -0.02em;
            color: #0f172a;
            margin: 0;
        }

        .ticket-public-subtitle {
            color: #64748b;
            margin-top: 6px;
            margin-bottom: 0;
        }

        .ticket-public-card .form-group label {
            font-weight: 600;
            color: #334155;
        }

        .ticket-public-card .form-control {
            border-radius: 10px;
            border: 1px solid #dbe4ef;
            min-height: 42px;
            background: #fff;
            transition: border-color .2s ease, box-shadow .2s ease;
        }

        .ticket-public-card textarea.form-control {
            min-height: 110px;
        }

        .ticket-public-card .form-control:focus {
            border-color: #60a5fa;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, .12);
        }

        .ticket-public-card .bootstrap-select .dropdown-toggle {
            min-height: 42px;
            border-radius: 10px !important;
            border: 1px solid #dbe4ef !important;
        }

        .ticket-public-card .form-actions {
            border-top: 1px solid #e6ecf4;
            padding-top: 16px;
            margin-top: 6px !important;
            margin-bottom: 0 !important;
        }

        .ticket-public-card .btn-primary {
            border-radius: 10px;
            padding: 9px 18px;
            font-weight: 600;
            background: #2563eb;
            border-color: #2563eb;
        }

        .ticket-public-card .btn-secondary {
            border-radius: 10px;
            padding: 9px 18px;
            font-weight: 600;
        }

        @media (max-width: 767.98px) {
            .ticket-public-card {
                padding: 18px;
                border-radius: 14px;
            }

            .ticket-public-title {
                font-size: 20px;
            }
        }
    </style>

</head>

<!-- HTML5 Shim and Respond.js IE8 support of HTML5 elements and media queries -->
<!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
<!--[if lt IE 9]>
<script src="https://oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js"></script>
<script src="https://oss.maxcdn.com/libs/respond.js/1.4.2/respond.min.js"></script>
<![endif]-->


<body>
<!-- change dark theme class according to application dark theme setting -->
<div class="ticket-public-wrap @if($styled==1) ticket-public-wrap--narrow @endif">
    <div class="ticket-public-card">
        <div class="ticket-public-head">
        @if($withLogo==1)
            <img src="{{ $company->logo_url }}" alt="{{ $company->company_name }}" class="ticket-public-logo"/>
        @endif
            <h1 class="ticket-public-title">Support Ticket Form</h1>
            <p class="ticket-public-subtitle">Fill in details below and our team will get back to you soon.</p>
        </div>

        @if (!($isTicketFormActive ?? true))
            <div class="alert alert-warning mt-4 mb-4">
                Ticket form is currently disabled. Please contact support team.
            </div>
        @else
        <x-form id="createTicket" method="POST">
            <div class="form-body">
                <div class="row">
                    @php
                        $hasEmailField = $ticketFormFields->contains('field_name', 'email');
                    @endphp
                    @foreach ($ticketFormFields as $item)
                        @php
                            $defaultLabelKey = 'modules.tickets.' . $item->field_name;
                            $resolvedLabel = __($defaultLabelKey);
                            $fieldLabel = $resolvedLabel === $defaultLabelKey ? $item->field_display_name : $resolvedLabel;
                        @endphp
                        @if ($item->custom_fields_id === null)
                            @if ($item->field_type == 'textarea')
                                <div class="col-lg-12">
                                    <x-forms.textarea :fieldId="$item->field_name"
                                                      :fieldLabel="$fieldLabel"
                                                      :fieldName="$item->field_name"
                                                      :fieldRequired="$item->required == 1">
                                    </x-forms.textarea>
                                </div>
                            @elseif($item->field_type == 'select')
                                @if ($item->field_name == 'type')
                                    <div class="col-lg-12">
                                        <x-forms.select :fieldId="$item->field_name"
                                                        :fieldLabel="$fieldLabel"
                                                        :fieldName="$item->field_name" search="true" alignRight="true"
                                                        :fieldRequired="$item->required == 1">
                                            @forelse($types as $type)
                                                <option value="{{ $type->id }}">{{ $type->type }}
                                                </option>
                                            @empty
                                                <option value="">@lang('messages.noTicketTypeAdded')</option>
                                            @endforelse
                                        </x-forms.select>
                                    </div>
                                @elseif ($item->field_name == 'priority')
                                    <div class="col-lg-12">
                                        <x-forms.select :fieldId="$item->field_name"
                                                        :fieldLabel="$fieldLabel"
                                                        :fieldName="$item->field_name" search="true" alignRight="true"
                                                        :fieldRequired="$item->required == 1">
                                            <option value="low">@lang('app.low')</option>
                                            <option value="medium">@lang('app.medium')</option>
                                            <option value="high">@lang('app.high')</option>
                                            <option value="urgent">@lang('app.urgent')</option>
                                        </x-forms.select>
                                    </div>
                                @else
                                    <div class="col-lg-12">
                                        <x-forms.select :fieldId="$item->field_name"
                                                        :fieldLabel="$fieldLabel"
                                                        :fieldName="$item->field_name" search="true" alignRight="true"
                                                        :fieldRequired="$item->required == 1">
                                            @foreach($groups as $group)
                                                <option value="{{ $group->id }}">{{ $group->group_name }}</option>
                                            @endforeach
                                        </x-forms.select>
                                    </div>
                                @endif
                            @else
                                <div class="col-md-12">
                                    @if ($item->field_name === 'mobile')
                                        <div class="form-group my-3">
                                            <x-forms.label :fieldId="$item->field_name"
                                                           :fieldLabel="$fieldLabel"
                                                           :fieldRequired="$item->required == 1"></x-forms.label>
                                            <input type="hidden" name="country_phonecode" value="91">
                                            <div class="input-group">
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text">+91</span>
                                                </div>
                                                <input type="tel"
                                                       class="form-control"
                                                       name="mobile"
                                                       id="{{ $item->field_name }}"
                                                       value="{{ old('mobile') }}"
                                                       placeholder="WhatsApp number"
                                                       @if ($item->required == 1) required @endif>
                                            </div>
                                        </div>
                                    @else
                                        <x-forms.text :fieldId="$item->field_name"
                                                      :fieldLabel="$fieldLabel"
                                                      :fieldName="$item->field_name" fieldPlaceholder=""
                                                      :fieldRequired="$item->required == 1">
                                        </x-forms.text>
                                    @endif

                                    @if ($item->field_name === 'name' && ! $hasEmailField)
                                        <x-forms.email fieldId="email" :fieldLabel="__('app.email')"
                                                       fieldName="email" fieldRequired="true"
                                                       :fieldPlaceholder="__('placeholders.email')"></x-forms.email>
                                    @endif
                                </div>
                            @endif
                        @else
                            @if($item->field_type == 'text')
                                <div class="col-md-6">
                                    <x-forms.text
                                        fieldId="custom_fields_data[{{ $item->field_name . '_' . $item->customField->id }}]"
                                        :fieldLabel="$item->field_display_name"
                                        fieldName="custom_fields_data[{{ $item->field_name . '_' . $item->customField->id }}]"
                                        :fieldRequired="($item->required === 1) ? true : false">>
                                    </x-forms.text>
                                </div>
                            @elseif($item->field_type == 'password')
                                <div class="col-md-6">
                                    <x-forms.password
                                        fieldId="custom_fields_data[{{ $item->field_name . '_' . $item->customField->id }}]"
                                        :fieldLabel="$item->field_display_name"
                                        fieldName="custom_fields_data[{{ $item->name . '_' . $item->id }}]"
                                        :fieldPlaceholder="$item->label"
                                        :fieldRequired="($item->required === 1) ? true : false">
                                    </x-forms.password>
                                </div>
                            @elseif($item->field_type == 'number')
                                <div class="col-md-6">
                                    <x-forms.number
                                        fieldId="custom_fields_data[{{ $item->field_name . '_' . $item->customField->id }}]"
                                        :fieldLabel="$item->field_display_name"
                                        fieldName="custom_fields_data[{{ $item->name . '_' . $item->id }}]"
                                        :fieldPlaceholder="$item->label"
                                        :fieldRequired="($item->required === 1) ? true : false">
                                    </x-forms.number>
                                </div>
                            @elseif($item->field_type == 'textarea')
                                <div class="col-md-6">
                                    <x-forms.textarea
                                        :fieldLabel="$item->field_display_name"
                                        fieldName="custom_fields_data[{{ $item->name . '_' . $item->id }}]"
                                        fieldId="custom_fields_data[{{ $item->field_name . '_' . $item->customField->id }}]"
                                        :fieldRequired="($item->required === 1) ? true : false"
                                        :fieldPlaceholder="$item->label">
                                    </x-forms.textarea>
                                </div>
                            @elseif($item->field_type == 'radio')
                                <div class="col-md-6">
                                    <div class="form-group my-3">
                                        <x-forms.label
                                            fieldId="custom_fields_data[{{ $item->field_name . '_' . $item->customField->id }}]"
                                            :fieldLabel="$item->field_display_name"
                                            :fieldRequired="($item->required === 1) ? true : false">
                                        </x-forms.label>
                                        <div class="d-flex">
                                            @foreach (json_decode($item->customField->values) as $key => $value)
                                                <x-forms.radio
                                                    fieldId="optionsRadios{{ $key . $item->customField->id }}"
                                                    :fieldLabel="$value"
                                                    fieldName="custom_fields_data[{{ $item->field_name . '_' . $item->customField->id }}]"
                                                    :fieldValue="$value" :checked="($key == 0) ? true : false"/>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            @elseif($item->field_type == 'select')
                                <div class="col-md-6">
                                    <div class="form-group my-3">
                                        <x-forms.select
                                            fieldId="custom_fields_data[{{ $item->field_name . '_' . $item->customField->id }}]"
                                            :fieldLabel="$item->field_display_name"
                                            fieldName="custom_fields_data[{{ $item->field_name . '_' . $item->customField->id }}]"
                                            :fieldRequired="$item->required == 1"
                                            search="true">
                                            <option value="">--</option>
                                            @foreach(json_decode($item->customField->values) as $key => $item)
                                                <option value="{{ $key }}">{{ $item }}</option>
                                            @endforeach
                                        </x-forms.select>
                                    </div>
                                </div>
                            @elseif($item->field_type == 'date')
                                <div class="col-md-6">
                                    <x-forms.datepicker custom="true"
                                                        fieldId="custom_fields_data[{{ $item->field_name . '_' . $item->customField->id }}]"
                                                        :fieldRequired="($item->required === 1) ? true : false"
                                                        :fieldLabel="$item->field_display_name"
                                                        fieldName="custom_fields_data[{{ $item->field_name . '_' . $item->customField->id }}]"
                                                        :fieldValue="now()->timezone($company->timezone)->format($company->date_format)"
                                                        :fieldPlaceholder="$item->label"/>
                                </div>
                            @elseif($item->field_type == 'checkbox')
                                <div class="col-md-6">
                                    <div class="form-group my-3">
                                        <x-forms.label
                                            fieldId="custom_fields_data[{{ $item->field_name . '_' . $item->customField->id }}]"
                                            :fieldLabel="$item->field_display_name"
                                            :fieldRequired="($item->required === 1) ? true : false">
                                        </x-forms.label>
                                        <div class="d-flex checkbox-{{$item->id}}">
                                            <input type="hidden"
                                                   name="custom_fields_data[{{$item->name.'_'.$item->id}}]"
                                                   id="{{$item->name.'_'.$item->id}}">
                                            @foreach (json_decode($item->customField->values) as $key => $value)
                                                <x-forms.checkbox fieldId="optionsRadios{{ $key . $item->id }}"
                                                                  :fieldLabel="$value"
                                                                  fieldName="$item->field_name.'_'.$item->customField->id.'[]'"
                                                                  :fieldValue="$value"
                                                                  onchange="checkboxChange('checkbox-{{$item->customField->id}}', '{{$item->field_name.'_'.$item->customField->id}}')"
                                                                  :fieldRequired="($item->required === 1) ? true : false"/>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            @elseif ($item->customField->type == 'file')
                                <div class="col-md-6">
                                    <input type="hidden"
                                           name="custom_fields_data[{{$item->field_name.'_'.$item->customField->id}}]">
                                    <x-forms.file
                                        :fieldLabel="$item->field_display_name"
                                        :fieldRequired="($item->required === 1) ? true : false"
                                        :fieldName="'custom_fields_data[' . $item->field_name . '_' . $item->customField->id . ']'"
                                        :fieldId="'custom_fields_data[' . $item->field_name . '_' . $item->customField->id . ']'"
                                        fieldValue=""
                                    />
                                </div>
                            @endif
                        @endif
                    @endforeach

                    @if (global_setting()->google_recaptcha_status == 'active' && global_setting()->google_recaptcha_v2_status == 'active')
                        <div class="col-md-12 col-lg-12 mt-2" id="captcha_container"></div>
                    @endif

                    {{-- This is used for google captcha v3 --}}
                    <input type="hidden" id="g_recaptcha" name="g_recaptcha">

                    @if ($errors->has('g-recaptcha-response'))
                        <div class="help-block with-errors">{{ $errors->first('g-recaptcha-response') }}</div>
                    @endif


                </div>
            </div>
            <input type="hidden" name="company_id" value="{{ $company->id }}">
            <div class="form-actions mt-4 mb-4">
                <button type="button" id="save-form" class="btn btn-primary mr-3"><i class="fa fa-check"></i>
                    @lang('app.save')</button>
                <button type="reset" class="btn btn-secondary">@lang('app.reset')</button>
            </div>
        </x-form>
        @endif

        <div class="row">
            <div class="col-sm-12">
                <div class="alert alert-success" id="success-message" style="display:none"></div>
            </div>
        </div>

    </div>
</div>
</body>


<!-- jQuery -->
<script src="{{ asset('vendor/jquery/jquery.min.js') }}"></script>

<!-- Global Required Javascript -->
<script src="{{ asset('vendor/bootstrap/javascript/bootstrap-native.js') }}"></script>

<!-- Font Awesome -->
<script src="{{ asset('vendor/jquery/all.min.js') }}"></script>

<!-- Template JS -->
<script src="{{ asset('js/main.js') }}"></script>
<script src="{{ asset('vendor/froiden-helper/helper.js') }}"></script>

<script>
    const MODAL_LG = '#myModal';
    const MODAL_XL = '#myModalXl';
    document.loading = '@lang('app.loading')';
    const dropifyMessages = {
        default: "@lang('app.dragDrop')",
        replace: "@lang('app.dragDropReplace')",
        remove: "@lang('app.remove')",
        error: "@lang('messages.errorOccured')",
    };

    $(window).on('load', function () {
        // Animate loader off screen
        init();
        $(".preloader-container").fadeOut("slow", function () {
            $(this).removeClass("d-flex");
        });
    });

    const datepickerConfig = {
        formatter: (input, date, instance) => {
            input.value = moment(date).format('{{ $company->moment_format }}')
        },
        showAllDates: true,
        customDays: {!!  json_encode(\App\Models\GlobalSetting::getDaysOfWeek())!!},
        customMonths: {!!  json_encode(\App\Models\GlobalSetting::getMonthsOfYear())!!},
        customOverlayMonths: {!!  json_encode(\App\Models\GlobalSetting::getMonthsOfYear())!!},
        overlayButton: "@lang('app.submit')",
        overlayPlaceholder: "@lang('app.enterYear')",
        startDay: parseInt("{{ attendance_setting()->week_start_from }}")
    };
</script>

<script>

    $('.custom-date-picker').each(function (ind, el) {
        datepicker(el, {
            position: 'bl',
            ...datepickerConfig
        });
    });
    $(".select-picker").selectpicker();

    $('#save-form').click(function () {
        $.easyAjax({
            url: "{{ route('front.ticket_store') }}",
            container: '#createTicket',
            type: "POST",
            redirect: true,
            disableButton: true,
            blockUI: true,
            file: true,
            data: $('#createTicket').serialize(),
            success: function (response) {
                if (response.status == "success") {
                    $('#createTicket')[0].reset();
                    $('#createTicket').hide();
                    $('#success-message').html(response.message);
                    $('#success-message').show();
                }
            }
        })
    });
</script>

@if (global_setting()->google_recaptcha_status == 'active' && global_setting()->google_recaptcha_v2_status == 'active')
    <script src="https://www.google.com/recaptcha/api.js?onload=onloadCallback&render=explicit" async defer></script>
    <script>
        var gcv3;
        var onloadCallback = function () {
            // Renders the HTML element with id 'captcha_container' as a reCAPTCHA widget.
            // The id of the reCAPTCHA widget is assigned to 'gcv3'.
            gcv3 = grecaptcha.render('captcha_container', {
                'sitekey': '{{ global_setting()->google_recaptcha_v2_site_key }}',
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

@if (global_setting()->google_recaptcha_status == 'active' && global_setting()->google_recaptcha_v3_status == 'active')
    <script
        src="https://www.google.com/recaptcha/api.js?render={{ global_setting()->google_recaptcha_v3_site_key }}"></script>
    <script>
        grecaptcha.ready(function () {
            grecaptcha.execute('{{ global_setting()->google_recaptcha_v3_site_key }}').then(function (token) {
                // Add your logic to submit to your backend server here.
                $('#g_recaptcha').val(token);
            });
        });
    </script>
@endif

</html>
