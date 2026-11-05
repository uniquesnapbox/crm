@extends('layouts.app')

@section('content')

    <!-- SETTINGS START -->
    <div class="w-100 d-flex ">

        @include('sections.setting-sidebar')

        <x-setting-card>
            <x-slot name="header">
                <div class="s-b-n-header" id="tabs">
                    <h2 class="mb-0 p-20 f-21 font-weight-normal text-capitalize border-bottom-grey">
                        @lang($pageTitle)</h2>
                </div>
            </x-slot>

            <div class="col-lg-12 col-md-12 ntfcn-tab-content-left w-100 p-4 ">
                @method('PUT')
                <div class="row">
                    <div class="col-lg-6">
                        <x-forms.text class="mr-0 mr-lg-2 mr-md-2"
                                      :fieldLabel="__('modules.accountSettings.companyName')"
                                      :fieldPlaceholder="__('placeholders.company')" fieldRequired="true"
                                      fieldName="company_name"
                                      :popover="__('messages.companyNameTooltip')"
                                      fieldId="company_name" :fieldValue="company()->company_name"/>
                    </div>
                    <div class="col-lg-6">
                        <x-forms.text class="mr-0 mr-lg-2 mr-md-2"
                                      :fieldLabel="__('modules.accountSettings.companyEmail')"
                                      :fieldPlaceholder="__('placeholders.email')" fieldRequired="true"
                                      fieldName="company_email"
                                      fieldId="company_email" :fieldValue="company()->company_email"/>

                    </div>
                </div>

                <div class="row">
                    <div class="col-lg-6">
                        <x-forms.text class="mr-0 mr-lg-2 mr-md-2"
                                      :fieldLabel="__('modules.accountSettings.companyPhone')"
                                      :fieldPlaceholder="__('placeholders.mobileWithPlus')" fieldRequired="true" fieldName="company_phone"
                                      fieldId="company_phone" :fieldValue="company()->company_phone"/>
                    </div>
                    <div class="col-lg-6">
                        <x-forms.text class="mr-0 mr-lg-2 mr-md-2"
                                      :fieldLabel="__('modules.accountSettings.companyWebsite')"
                                      :fieldPlaceholder="__('placeholders.website')" fieldRequired="false"
                                      fieldName="website"
                                      fieldId="website" :fieldValue="company()->website"/>
                    </div>
                </div>

                <hr class="my-4">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <h5 class="mb-0"><i class="fa fa-google-drive mr-2"></i>Google Drive Automated Backup</h5>
                    @if($integration->refresh_token)
                        <span class="badge badge-success">Connected{{ $integration->google_email ? ': '.$integration->google_email : '' }}</span>
                    @else
                        <span class="badge badge-secondary">Not connected</span>
                    @endif
                </div>
                <div class="alert alert-info">Backup ZIP me database, uploaded documents aur safe configuration files honge. <strong>.env, passwords, private keys aur logs include nahi honge.</strong></div>
                <div class="row">
                    <div class="col-lg-6">
                        <label class="f-14 text-dark-grey mb-12 w-100">OAuth Client ID</label>
                        <input type="text" class="form-control height-35 f-14 mb-3" id="drive_client_id" value="{{ $integration->client_id }}">
                        <label class="f-14 text-dark-grey mb-12 w-100">OAuth Client Secret</label>
                        <input type="password" class="form-control height-35 f-14 mb-3" id="drive_client_secret" placeholder="Blank chhodein to existing secret rahega" autocomplete="new-password">
                        <label class="f-14 text-dark-grey mb-12 w-100">Google Drive Folder ID (optional)</label>
                        <input type="text" class="form-control height-35 f-14" id="drive_folder_id" value="{{ $integration->drive_folder_id }}">
                        <small class="text-muted">Blank chhodne par first backup me <strong>CRM Backup</strong> folder automatically banega.</small>
                    </div>
                    <div class="col-lg-6">
                        <label class="f-14 text-dark-grey mb-12 w-100">Backup Schedule</label>
                        <select class="form-control height-35 f-14 mb-3" id="drive_schedule">
                            <option value="manual" @selected($integration->schedule === 'manual')>Manual only</option>
                            <option value="daily" @selected($integration->schedule === 'daily')>Daily</option>
                            <option value="weekly" @selected($integration->schedule === 'weekly')>Weekly</option>
                        </select>
                        <label class="f-14 text-dark-grey mb-12 w-100">Retention</label>
                        <select class="form-control height-35 f-14 mb-3" id="drive_retention_days">
                            @foreach([7,15,30,90] as $days)<option value="{{ $days }}" @selected((int)$integration->retention_days === $days)>{{ $days }} days</option>@endforeach
                        </select>
                        <label class="d-block mb-2"><input type="checkbox" id="drive_include_uploads" @checked($integration->include_uploads)> Include uploaded files/documents</label>
                        <label class="d-block"><input type="checkbox" id="drive_include_configuration" @checked($integration->include_configuration)> Include safe configuration/settings</label>
                    </div>
                </div>
                <div class="mt-3">
                    <small class="text-muted d-block mb-2">OAuth callback URL: <code>{{ $driveCallbackUrl }}</code></small>
                    <button type="button" id="save-drive-settings" class="btn btn-primary mr-2">Save Drive Settings</button>
                    <a href="{{ route('google-drive-backup.connect') }}" class="btn btn-info mr-2">Connect / Reconnect</a>
                    @if($integration->refresh_token)
                        <button type="button" id="verify-drive" class="btn btn-secondary mr-2">Verify</button>
                        <button type="button" id="disconnect-drive" class="btn btn-outline-danger">Disconnect</button>
                    @endif
                </div>

                <hr class="my-4">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <h5 class="mb-0">Google Drive Backup History</h5>
                    <button type="button" id="backup-now-drive" class="btn btn-primary" @disabled(!$integration->refresh_token)>Backup Now</button>
                </div>
                <div class="table-responsive">
                    <table class="table table-bordered table-sm">
                        <thead><tr><th>File name</th><th>Date/time</th><th>Size</th><th>Status</th><th>Drive link</th><th>Error</th></tr></thead>
                        <tbody>
                        @forelse($driveBackupHistories as $history)
                            <tr>
                                <td>{{ $history->file_name }}</td>
                                <td>{{ $history->created_at?->timezone(company()->timezone)->format('d-m-Y H:i') }}</td>
                                <td>{{ $history->file_size ? \App\Http\Controllers\DatabaseBackupSettingController::humanFileSize($history->file_size) : '-' }}</td>
                                <td><span class="badge badge-{{ $history->status === 'success' ? 'success' : ($history->status === 'failed' ? 'danger' : 'warning') }}">{{ ucfirst($history->status) }}</span></td>
                                <td>@if($history->google_drive_url)<a href="{{ $history->google_drive_url }}" target="_blank">Open Drive</a>@else - @endif</td>
                                <td>{{ $history->error_message ?: '-' }}</td>
                            </tr>
                        @empty <tr><td colspan="6" class="text-center">No Google Drive backups yet.</td></tr> @endforelse
                        </tbody>
                    </table>
                </div>

            </div>

            <x-slot name="action">
                <!-- Buttons Start -->
                <div class="w-100 border-top-grey">
                    <x-setting-form-actions>
                        <x-forms.button-secondary id="take-database-backup" class="mr-3" icon="database">
                            Take Database Backup
                        </x-forms.button-secondary>
                        <x-forms.button-primary id="save-form" class="mr-3" icon="check">@lang('app.save')
                        </x-forms.button-primary>
                        </x-setting-form-actions>
                </div>
                <!-- Buttons End -->
            </x-slot>

        </x-setting-card>

    </div>
    <!-- SETTINGS END -->
@endsection

@push('scripts')
    <script>
        $('#save-form').click(function () {
            var url = "{{ route('company-settings.update', company()->id) }}";

            $.easyAjax({
                url: url,
                container: '#editSettings',
                type: "POST",
                disableButton: true,
                blockUI: true,
                buttonSelector: "#save-form",
                data: $('#editSettings').serialize(),
            })
        });

        $('#take-database-backup').click(function () {
            Swal.fire({
                title: 'Create database backup?',
                text: 'A backup of the current database will be created and saved locally.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Create Backup',
                cancelButtonText: 'Cancel',
                customClass: {
                    confirmButton: 'btn btn-primary mr-2',
                    cancelButton: 'btn btn-light'
                },
                buttonsStyling: false
            }).then(function (result) {
                if (!result.isConfirmed) {
                    return;
                }

                window.location.href = "{{ route('database-backup-settings.create_backup_download') }}";
            });
        });

        function drivePost(url, data) {
            $.easyAjax({
                url: url,
                type: 'POST',
                data: Object.assign({_token: '{{ csrf_token() }}'}, data || {}),
                blockUI: true,
                success: function () { window.location.reload(); }
            });
        }

        $('#save-drive-settings').click(function () {
            drivePost('{{ route('google-drive-backup.save') }}', {
                client_id: $('#drive_client_id').val(),
                client_secret: $('#drive_client_secret').val(),
                drive_folder_id: $('#drive_folder_id').val(),
                schedule: $('#drive_schedule').val(),
                retention_days: $('#drive_retention_days').val(),
                include_uploads: $('#drive_include_uploads').is(':checked') ? 1 : 0,
                include_configuration: $('#drive_include_configuration').is(':checked') ? 1 : 0
            });
        });
        $('#verify-drive').click(function () { drivePost('{{ route('google-drive-backup.verify') }}'); });
        $('#disconnect-drive').click(function () { drivePost('{{ route('google-drive-backup.disconnect') }}'); });
        $('#backup-now-drive').click(function () { drivePost('{{ route('google-drive-backup.now') }}'); });
    </script>
@endpush
