@extends('layouts.app')

@push('styles')
    <style>
        #live-tracking-map {
            min-height: 520px;
            border-radius: 12px;
        }

        .live-tracking-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1rem;
        }
    </style>
@endpush

@section('content')
    <div class="content-wrapper px-4">
        <x-cards.data class="mt-3">
            <div class="live-tracking-meta">
                <div>
                    <h4 class="mb-1">@lang('app.menu.liveTracking')</h4>
                    <p class="text-lightest mb-0">Latest employee locations refresh every 30 seconds.</p>
                </div>
                <span class="badge badge-secondary f-12" id="last-refresh-label">Waiting for first sync</span>
            </div>

            @if (is_null(global_setting()->google_map_key))
                <div class="alert alert-warning mb-0">
                    @lang('messages.googleMapMessage')
                    <a href="{{ route('app-settings.index') }}?tab=google-map-setting">@lang('app.googleMapSettings')</a>
                </div>
            @else
                <div id="live-tracking-map"></div>
            @endif
        </x-cards.data>
    </div>
@endsection

@if (!is_null(global_setting()->google_map_key))
    @push('scripts')
        <script>
            let trackingMap;
            let trackingBounds;
            let infoWindow;
            let markers = [];

            function escapeHtml(value) {
                return String(value ?? '')
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;')
                    .replace(/'/g, '&#039;');
            }

            function initMap() {
                trackingMap = new google.maps.Map(document.getElementById('live-tracking-map'), {
                    zoom: 12,
                    center: {
                        lat: Number(@json(company()->latitude ?: 0)),
                        lng: Number(@json(company()->longitude ?: 0))
                    },
                    mapTypeId: google.maps.MapTypeId.ROADMAP
                });

                infoWindow = new google.maps.InfoWindow();
                fetchLiveTracking(false);
                setInterval(fetchLiveTracking, 30000);
            }

            function clearMarkers() {
                markers.forEach((marker) => marker.setMap(null));
                markers = [];
            }

            function fetchLiveTracking(showLoader = true) {
                $.easyAjax({
                    url: "{{ route('admin.live-tracking') }}",
                    type: 'GET',
                    blockUI: showLoader,
                    container: '.content-wrapper',
                    success: function (response) {
                        renderMarkers(response.locations || []);
                        $('#last-refresh-label').text('Last refreshed: ' + moment().format('DD MMM YYYY hh:mm:ss A'));
                    }
                });
            }

            function renderMarkers(locations) {
                clearMarkers();
                trackingBounds = new google.maps.LatLngBounds();

                if (!locations.length) {
                    trackingMap.setCenter({
                        lat: Number(@json(company()->latitude ?: 0)),
                        lng: Number(@json(company()->longitude ?: 0))
                    });
                    return;
                }

                locations.forEach(function (location) {
                    const position = {
                        lat: Number(location.latitude),
                        lng: Number(location.longitude)
                    };

                    const marker = new google.maps.Marker({
                        map: trackingMap,
                        position: position,
                        title: location.employee_name || 'Employee'
                    });

                    marker.addListener('click', function () {
                        infoWindow.setContent(
                            '<div class="p-2">' +
                            '<div class="font-weight-bold mb-1">' + escapeHtml(location.employee_name || 'Employee') + '</div>' +
                            '<div class="text-muted">Last update: ' + escapeHtml(location.last_update_time || '-') + '</div>' +
                            '</div>'
                        );
                        infoWindow.open(trackingMap, marker);
                    });

                    markers.push(marker);
                    trackingBounds.extend(position);
                });

                if (locations.length === 1) {
                    trackingMap.setCenter(trackingBounds.getCenter());
                    trackingMap.setZoom(15);
                    return;
                }

                trackingMap.fitBounds(trackingBounds);
            }
        </script>
        <script src="https://maps.googleapis.com/maps/api/js?key={{ global_setting()->google_map_key }}&callback=initMap" async defer></script>
    @endpush
@endif
