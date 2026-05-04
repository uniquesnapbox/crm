@extends('layouts.app')

@section('content')

    <!-- SETTINGS START -->
    <div class="w-100 d-flex ">

        <x-setting-sidebar :activeMenu="$activeSettingMenu" />

        <x-setting-card>

            <x-slot name="header">
                <div class="s-b-n-header" id="tabs">
                    <nav class="tabs px-4 border-bottom-grey">
                        <div class="nav" id="nav-tab" role="tablist">
                            <a class="nav-item nav-link f-15 active email-setting"
                                href="{{ route('notifications.index') }}" role="tab" aria-controls="nav-ticketAgents"
                                aria-selected="true">@lang('app.email')
                            </a>
                            <a class="nav-item nav-link f-15 slack-setting"
                                href="{{ route('notifications.index') }}?tab=slack-setting" role="tab"
                                aria-controls="nav-ticketTypes" aria-selected="true" ajax="false">@lang('app.slack') <i
                                class="fa fa-circle ml-1 {{ $slackSettings->status == 'active' ? 'text-light-green' : 'text-red' }}"></i>
                            </a>
                            <a class="nav-item nav-link f-15 push-notification-setting"
                                href="{{ route('notifications.index') }}?tab=push-notification-setting" role="tab"
                                aria-controls="nav-ticketTypes" aria-selected="true"
                                ajax="false">@lang('app.pushNotification')<i
                                class="fa fa-circle ml-1 {{ $pushSettings->status == 'active' ? 'text-light-green' : 'text-red' }}"></i>
                            </a>
                            <a class="nav-item nav-link f-15 pusher-setting"
                                href="{{ route('notifications.index') }}?tab=pusher-setting" role="tab"
                                aria-controls="nav-ticketTypes" aria-selected="true"
                                ajax="false">@lang('app.menu.pusherSettings')<i
                                id="pusher-setting-tab" class="fa fa-circle ml-1 {{ $pusherSettings->status == 1 ? 'text-light-green' : 'text-red' }}"></i>
                            </a>
                            <a class="nav-item nav-link f-15 whatsapp-setting"
                                href="{{ route('notifications.index') }}?tab=whatsapp-setting" role="tab"
                                aria-controls="nav-ticketTypes" aria-selected="true"
                                ajax="false">WhatsApp API<i
                                class="fa fa-circle ml-1 {{ $whatsappSettings->status == 'active' ? 'text-light-green' : 'text-red' }}"></i>
                            </a>
                        </div>
                    </nav>
                </div>
            </x-slot>

            {{-- include tabs here --}}
            @include($view)

        </x-setting-card>

    </div>
    <!-- SETTINGS END -->

@endsection

@push('scripts')
    <script>
        /* manage menu active class */
        $('.nav-item').removeClass('active');
        const activeTab = "{{ $activeTab }}";
        $('.' + activeTab).addClass('active');

        $("body").on("click", "#editSettings .nav a", function(event) {
            if (typeof $.easyAjax !== "function") {
                return true;
            }

            event.preventDefault();

            $('.nav-item').removeClass('active');
            $(this).addClass('active');

            const requestUrl = this.href;

            $.easyAjax({
                type: "GET",
                url: requestUrl,
                blockUI: true,
                container: "#nav-tabContent",
                historyPush: true,
                success: function(response) {
                    if (response.status === "success" && typeof response.html !== "undefined") {
                        const $contentWrap = $('#nav-tabContent .tab-pane > .d-flex.flex-wrap.justify-content-between');

                        if ($contentWrap.length) {
                            $contentWrap.html(response.html);
                        }
                        else {
                            $('#nav-tabContent .tab-pane').html(response.html);
                        }

                        if (typeof init === "function") {
                            init('#nav-tabContent');
                        }
                        return;
                    }

                    // Fallback: if AJAX payload is not in expected format, open tab URL normally.
                    window.location.href = requestUrl;
                },
                error: function() {
                    // Fallback: network/JSON issues should not leave settings panel blank.
                    window.location.href = requestUrl;
                }
            });
        });

        $(document).on('change', '#pusher_status', function() {
            $('.pusher_details').toggleClass('d-none');
        });

        $(document).on('change', '#slack_status', function() {
            $('.slack_details').toggleClass('d-none');
        });

        $(document).on('change', '#push_status', function() {
            $('.push_details').toggleClass('d-none');
        });

        $(document).on('change', '#whatsapp_status', function() {
            $('.whatsapp_details').toggleClass('d-none');
        });
    </script>
@endpush
