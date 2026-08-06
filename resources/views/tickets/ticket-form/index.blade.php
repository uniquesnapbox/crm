@extends('layouts.app')

@push('datatable-styles')
    <!-- for sortable content -->
    <link rel="stylesheet" href="{{ asset('vendor/css/jquery-ui.css') }}">

    <!-- to highlight html content -->
    <link rel="stylesheet" href="{{ asset('vendor/css/default.min.css') }}">

    <style>
        .ticket-form-builder {
            --card-border: #e7ecf3;
            --muted-text: #6b7280;
            --soft-bg: #f8fafc;
            --accent: #2563eb;
            --success: #16a34a;
            --inactive: #6b7280;
        }

        .ticket-form-builder .builder-panel {
            border: 1px solid var(--card-border);
            border-radius: 16px;
            overflow: hidden;
        }

        .ticket-form-builder .builder-panel .card-body {
            padding: 22px;
        }

        .ticket-form-builder .builder-title {
            font-size: 20px;
            font-weight: 700;
            color: #0f172a;
            margin: 0;
        }

        .ticket-form-builder .builder-subtitle {
            font-size: 13px;
            color: var(--muted-text);
            margin-top: 4px;
            margin-bottom: 0;
        }

        .ticket-form-builder .builder-toolbar {
            background: linear-gradient(180deg, #ffffff 0%, #f8fbff 100%);
            border: 1px solid var(--card-border);
            border-radius: 12px;
            padding: 14px 16px;
            margin-bottom: 16px;
        }

        .ticket-form-builder .builder-status-pill {
            font-size: 12px;
            border-radius: 999px;
            padding: 5px 10px;
        }

        .ticket-form-builder .builder-status-pill.active {
            background: #dcfce7;
            color: #166534;
        }

        .ticket-form-builder .builder-status-pill.inactive {
            background: #e5e7eb;
            color: #374151;
        }

        .ticket-form-builder .field-list-head {
            color: #334155;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: .04em;
            margin-bottom: 10px;
        }

        .ticket-form-builder .field-item {
            margin-bottom: 10px;
            border: 1px solid var(--card-border);
            border-radius: 12px;
            padding: 14px 12px;
            background: #fff;
            transition: box-shadow .2s ease, transform .2s ease;
        }

        .ticket-form-builder .field-item:hover {
            box-shadow: 0 8px 22px rgba(2, 6, 23, .08);
            transform: translateY(-1px);
        }

        .ticket-form-builder .drag-handle {
            width: 30px;
            height: 30px;
            border: 1px solid #dbe4ef;
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: #64748b;
            background: var(--soft-bg);
            cursor: move;
        }

        .ticket-form-builder .field-name {
            font-weight: 600;
            color: #0f172a;
        }

        .ticket-form-builder .fixed-label {
            color: var(--muted-text);
            font-weight: 600;
        }

        .ticket-form-builder .snippet-card {
            border: 1px solid var(--card-border);
            border-radius: 14px;
            padding: 14px 16px;
            background: #fff;
        }

        .ticket-form-builder .snippet-card code {
            display: block;
            background: #0f172a;
            color: #e2e8f0;
            border-radius: 10px;
            padding: 12px;
            white-space: normal;
        }

        .ticket-form-builder .preview-panel {
            border: 1px solid var(--card-border);
            border-radius: 16px;
            overflow: hidden;
            position: sticky;
            top: 20px;
            background: #fff;
        }

        .ticket-form-builder .preview-panel .preview-head {
            border-bottom: 1px solid var(--card-border);
            padding: 16px 18px;
            background: linear-gradient(180deg, #ffffff 0%, #f8fbff 100%);
        }

        .ticket-form-builder .preview-panel .preview-head h4 {
            margin: 0;
            font-size: 18px;
            font-weight: 700;
        }

        .ticket-form-builder .preview-panel .preview-body {
            padding: 14px;
            background: #f9fbfd;
        }

        .ticket-form-builder #previewIframe {
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            background: #fff;
        }
    </style>
@endpush

@section('content')
    <!-- CONTENT WRAPPER START -->
    <div class="content-wrapper ticket-form-builder">
        <div class="row">
            <div class="col-lg-6">
                <div class="row">
                    <div class="col-sm-12">
                        <div class="card bg-white border-0 b-shadow-4 builder-panel">
                            <div class="card-body ">
                                <div class="mb-4">
                                    <h2 class="builder-title">Ticket Form Builder</h2>
                                    <p class="builder-subtitle">Configure fields, enable/disable sections, and preview changes in real-time.</p>
                                </div>

                                <div class="col-md-12 mb-4">
                                    <div class="d-flex flex-wrap align-items-center justify-content-between builder-toolbar">
                                        <div class="d-flex align-items-center mb-2 mb-md-0">
                                            <span class="f-w-500 mr-3">Ticket Form</span>
                                            <div class="custom-control custom-switch">
                                                <input type="checkbox" class="custom-control-input" id="ticket_form_status_toggle"
                                                    @if(($ticketFormStatus ?? 'active') === 'active') checked @endif>
                                                <label class="custom-control-label f-14" for="ticket_form_status_toggle"></label>
                                            </div>
                                            <span class="ml-2 builder-status-pill {{ ($ticketFormStatus ?? 'active') === 'active' ? 'active' : 'inactive' }}" id="ticket-form-status-badge">
                                                {{ ($ticketFormStatus ?? 'active') === 'active' ? 'Active' : 'Inactive' }}
                                            </span>
                                        </div>
                                        @if (($manageCustomFieldPermission ?? 'none') === 'all' && !empty($ticketCustomFieldGroupId))
                                            <button type="button" id="add-ticket-custom-box" class="btn btn-outline-primary btn-sm">
                                                <i class="fa fa-plus mr-1"></i> Add New Box
                                            </button>
                                        @endif
                                    </div>
                                </div>

                                <div class="col-md-12 mb-2 field-list-head">
                                    <div class="row">
                                        <div class="col-md-3">Order</div>
                                        <div class="col-md-5">@lang('app.fields')</div>
                                        <div class="col-md-4">@lang('app.status')</div>
                                    </div>
                                </div>
                                <div class="col-md-12">
                                    <x-form id="editSettings" method="PUT">
                                        <div id="sortable">
                                            @foreach ($ticketFormFields as $item)
                                                <div class="row field-item">
                                                    <div class="col-md-3">
                                                        <span class="drag-handle"><i class="fa fa-grip-vertical"></i></span>
                                                        <input type="hidden" name="sort_order[]"
                                                            value="{{ $item->id }}">
                                                    </div>
                                                    <div class="col-md-5 field-name">{{ $item->field_display_name }}</div>
                                                    <div class="col-md-4">
                                                        @if (!in_array($item->field_name, ['name', 'email', 'ticket_subject', 'message', 'assign_group']))
                                                            <div class="custom-control custom-switch">
                                                                <input type="checkbox"
                                                                    class="custom-control-input change-setting"
                                                                    data-setting-id="{{ $item->id }}"
                                                                    @if ($item->status == 'active') checked @endif id="{{ $item->id }}">
                                                                <label class="custom-control-label f-14"
                                                                    for="{{ $item->id }}"></label>
                                                            </div>
                                                        @else
                                                            <span class="fixed-label">Always On</span>
                                                        @endif
                                                    </div>
                                                </div>
                                            @endforeach

                                        </div>
                                    </x-form>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-12 mt-4">
                        <div class="snippet-card mb-3">
                            <p class="f-w-500">@lang('modules.lead.iframeSnippet')</p>
                            <code>
                                &lt;iframe src="{{ route('front.ticket_form',company()->hash) }}"  frameborder="0" scrolling="yes"  style="display:block; width:100%; height:60vh;">&lt;/iframe&gt;
                            </code>
                        </div>

                        <div class="snippet-card">
                            <p class="f-w-500">Share Direct link</p>
                            <p class="f-12"><a href="{{ route('front.ticket_form', [company()->hash]).'?styled=1' }}" target="_blank">{{ route('front.ticket_form', [company()->hash]).'?styled=1' }}</a></p>
                            <p class="f-12"><a href="{{ route('front.ticket_form', [company()->hash]).'?styled=1&with_logo=1' }}" target="_blank">{{ route('front.ticket_form', [company()->hash]).'?styled=1&with_logo=1' }}</a></p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="preview-panel">
                    <div class="preview-head">
                        <h4>@lang('app.preview')</h4>
                    </div>
                    <div class="preview-body">
                        <iframe src="{{ route('front.ticket_form', company()->hash) }}" id="previewIframe" width="100%"
                            onload="resizeIframe(this)" frameborder="0"></iframe>
                    </div>
                </div>
            </div>
        </div>

    </div>
    <!-- CONTENT WRAPPER END -->
@endsection

@push('scripts')
    <!-- for sortable content -->
    <script src="{{ asset('vendor/jquery/jquery-ui.min.js') }}"></script>

    <!-- to highlight html content -->
    <script src="{{ asset('vendor/jquery/highlight.min.js') }}"></script>

    <script>
        $(function() {
            $("#sortable").sortable({
                update: function(event, ui) {
                    var sortedValues = new Array();
                    $('input[name="sort_order[]"]').each(function(index, value) {
                        sortedValues[index] = $(this).val();
                    });
                    $.easyAjax({
                        url: "{{ route('ticket-form.sort_fields') }}",
                        type: "POST",
                        blockUI: true,
                        data: {
                            'sortedValues': sortedValues,
                            '_token': '{{ csrf_token() }}'
                        },
                        success: function(response) {
                            var iframe = document.getElementById('previewIframe');
                            iframe.src = iframe.src;
                        }
                    })
                }
            });
        });

        $('.change-setting').change(function() {
            var id = $(this).data('setting-id');
            var sendEmail = $(this).is(':checked') ? 'active' : 'inactive';

            var url = "{{ route('ticket-form.update', ':id') }}";
            url = url.replace(':id', id);
            $.easyAjax({
                url: url,
                type: "POST",
                blockUI: true,
                data: {
                    'id': id,
                    'status': sendEmail,
                    '_method': 'PUT',
                    '_token': '{{ csrf_token() }}'
                },
                success: function(response) {
                    var iframe = document.getElementById('previewIframe');
                    iframe.src = iframe.src;
                }
            })
        });

        $('#ticket_form_status_toggle').change(function() {
            var formStatus = $(this).is(':checked') ? 'active' : 'inactive';

            $.easyAjax({
                url: "{{ route('ticket-form.update_form_status') }}",
                type: "POST",
                blockUI: true,
                data: {
                    status: formStatus,
                    _token: '{{ csrf_token() }}'
                },
                success: function() {
                    var $badge = $('#ticket-form-status-badge');
                    if (formStatus === 'active') {
                        $badge.removeClass('inactive').addClass('active').text('Active');
                    } else {
                        $badge.removeClass('active').addClass('inactive').text('Inactive');
                    }
                    var iframe = document.getElementById('previewIframe');
                    iframe.src = iframe.src;
                }
            });
        });

        $('body').on('click', '#add-ticket-custom-box', function() {
            var url = "{{ route('custom-fields.create') }}";
            url += "?module_id={{ $ticketCustomFieldGroupId ?? 0 }}&lock_module=1";
            $.ajaxModal(MODAL_LG, url);
        });

        function resizeIframe(obj) {
            obj.style.height = obj.contentWindow.document.documentElement.scrollHeight + 50 + 'px';
        }
    </script>
@endpush
