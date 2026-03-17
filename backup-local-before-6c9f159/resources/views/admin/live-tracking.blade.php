@extends('layouts.app')

@push('styles')
    <style>
        #live-tracking-map {
            height: 72vh;
            min-height: 520px;
            border-radius: 12px;
        }
    </style>
@endpush

@section('content')
    <div class="content-wrapper px-4 py-3">
        <x-cards.data class="mb-3">
            <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between">
                <div>
                    <h4 class="mb-1">Live Employee Tracking</h4>
                    <p class="text-dark-grey mb-0">Latest employee GPS pings refresh every 30 seconds.</p>
                </div>
                <div class="text-dark-grey mt-3 mt-lg-0">
                    Last refresh: <span id="last-refresh">--</span>
                </div>
            </div>
        </x-cards.data>

        <x-cards.data>
            <div id="live-tracking-map"></div>
        </x-cards.data>
    </div>
@endsection

@push('scripts')
    <script src="https://maps.googleapis.com/maps/api/js?key={{ global_setting()->google_map_key }}&callback=initLiveTrackingMap" async></script>
    <script>
        let liveTrackingMap;
        let liveTrackingInfoWindow;
        let trackingMarkers = [];

        function clearTrackingMarkers() {
            trackingMarkers.forEach(marker => marker.setMap(null));
            trackingMarkers = [];
        }

        function initLiveTrackingMap() {
            liveTrackingMap = new google.maps.Map(document.getElementById('live-tracking-map'), {
                zoom: 11,
                center: {
                    lat: parseFloat('{{ company()->defaultAddress?->latitude ?: company()->latitude }}'),
                    lng: parseFloat('{{ company()->defaultAddress?->longitude ?: company()->longitude }}')
                }
            });

            liveTrackingInfoWindow = new google.maps.InfoWindow();
            loadLiveTrackingMarkers();
            setInterval(loadLiveTrackingMarkers, 30000);
        }

        function loadLiveTrackingMarkers() {
            $.easyAjax({
                url: "{{ route('admin.live_tracking') }}",
                type: 'GET',
                data: { format: 'json' },
                success: function (response) {
                    clearTrackingMarkers();

                    if (response.office) {
                        liveTrackingMap.setCenter({
                            lat: response.office.latitude,
                            lng: response.office.longitude
                        });
                    }

                    response.data.forEach(function (employee) {
                        const marker = new google.maps.Marker({
                            map: liveTrackingMap,
                            position: {
                                lat: employee.latitude,
                                lng: employee.longitude
                            },
                            title: employee.employee_name
                        });

                        marker.addListener('click', function () {
                            liveTrackingInfoWindow.setContent(`
                                <div style="min-width:220px">
                                    <div style="font-weight:700;">${employee.employee_name}</div>
                                    <div>${employee.designation_name ? employee.designation_name : '--'}</div>
                                    <div style="margin-top:6px;">Last update: ${employee.timestamp ? employee.timestamp : '--'}</div>
                                </div>
                            `);
                            liveTrackingInfoWindow.open(liveTrackingMap, marker);
                        });

                        trackingMarkers.push(marker);
                    });

                    $('#last-refresh').text(new Date().toLocaleString());
                }
            });
        }
    </script>
@endpush
