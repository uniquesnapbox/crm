@extends('layouts.app')

@section('content')

    <!-- SETTINGS START -->
    <div class="w-100 d-flex ">

        <x-setting-sidebar :activeMenu="$activeSettingMenu" />

        <x-setting-card>

            <x-slot name="header">
                @php
                    $whatsappSessionKey = $whatsappSettings->resolved_whatsapp_session_key ?? $whatsappSettings->resolved_lead_created_sender_number ?? '';
                    $whatsappStatus = (string) ($whatsappSettings->status ?? 'inactive');
                    $whatsappError = trim((string) ($whatsappSettings->last_error_message ?? ''));
                    $whatsappStatusClass = $whatsappStatus === 'active' ? 'text-light-green' : 'text-warning';
                @endphp
                <div class="px-4 pt-4">
                    <div class="card border-grey rounded mb-3">
                        <div class="card-body d-flex flex-wrap justify-content-between align-items-center">
                            <div class="mr-3 mb-2 mb-lg-0">
                                <div class="d-flex align-items-center mb-1">
                                    <h5 class="text-dark-grey f-15 mb-0 mr-2">WhatsApp QR Setup</h5>
                                    <span class="badge badge-light {{ $whatsappStatusClass }}">
                                        {{ ucfirst($whatsappStatus) }}
                                    </span>
                                </div>
                                <div class="text-muted f-12">
                                    Session: <code>{{ $whatsappSessionKey ?: 'default' }}</code>
                                    @if($whatsappError !== '')
                                        | Last error: {{ $whatsappError }}
                                    @endif
                                </div>
                            </div>

                            <div class="d-flex flex-wrap align-items-center">
                                <a href="{{ route('notifications.index') }}?tab=whatsapp-setting"
                                    class="btn btn-primary btn-sm mr-2 mb-2 mb-lg-0">
                                    Open WhatsApp QR setup
                                </a>
                                <a href="{{ route('whatsapp-settings.connection-status') }}{{ $whatsappSessionKey !== '' ? '?sessionKey=' . $whatsappSessionKey : '' }}"
                                    class="btn btn-outline-secondary btn-sm mb-2 mb-lg-0" target="_blank" rel="noopener">
                                    Check bridge status
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="s-b-n-header" id="tabs">
                    <nav class="tabs px-4 border-bottom-grey">
                        <div class="nav" id="nav-tab" role="tablist">
                            <a class="nav-item nav-link f-15 active email-setting"
                                href="{{ route('notifications.index') }}" role="tab" aria-controls="nav-ticketAgents"
                                aria-selected="true">@lang('app.email')
                            </a>
                            <a class="nav-item nav-link f-15 whatsapp-setting"
                                href="{{ route('notifications.index') }}?tab=whatsapp-setting" role="tab"
                                aria-controls="nav-ticketTypes" aria-selected="true"
                                ajax="false">WhatsApp API / QR<i
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

        $(document).on('change', '#whatsapp_status', function() {
            $('.whatsapp_details').toggleClass('d-none');
        });
    </script>
@endpush
