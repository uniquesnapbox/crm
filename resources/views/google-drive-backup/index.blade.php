@extends('layouts.app')

@section('content')
<div class="w-100 d-flex">
    @include('sections.setting-sidebar')
    <x-setting-card id="google-drive-backup-box">
        <x-slot name="header">
            <h2 class="mb-0 p-20 f-21 font-weight-normal border-bottom-grey">Google Drive Automated Backup</h2>
        </x-slot>
        <div class="p-20">
            @if(session('success')) <div class="alert alert-success">{{ session('success') }}</div> @endif
            <div class="alert alert-info">Backup ZIP me database, uploaded documents aur safe configuration files honge. <strong>.env, passwords, private keys aur logs kabhi include nahi honge.</strong></div>

            <x-form id="google-drive-settings-form">
                <div class="row">
                    <div class="col-md-6">
                        <h5>Google OAuth Connection</h5>
                        <p class="text-muted">Google Cloud Console me isi exact callback URL ko add karein:</p>
                        <code class="d-block mb-3">{{ $callbackUrl }}</code>
                        <x-forms.text fieldId="client_id" fieldName="client_id" fieldLabel="OAuth Client ID" :fieldValue="$integration->client_id" />
                        <x-forms.password fieldId="client_secret" fieldName="client_secret" fieldLabel="OAuth Client Secret" fieldPlaceholder="Blank chhodein to existing secret rahega" />
                        <x-forms.text fieldId="drive_folder_id" fieldName="drive_folder_id" fieldLabel="Google Drive Folder ID (optional)" :fieldValue="$integration->drive_folder_id" />
                        <p>Status: @if($integration->refresh_token)<span class="badge badge-success">Connected{{ $integration->google_email ? ': '.$integration->google_email : '' }}</span>@else<span class="badge badge-secondary">Not connected</span>@endif</p>
                        <button type="button" id="save-google-drive" class="btn btn-primary mr-2">Save Settings</button>
                        <a href="{{ route('google-drive-backup.connect') }}" class="btn btn-info mr-2">Connect / Reconnect</a>
                        @if($integration->refresh_token)
                            <button type="button" id="verify-google-drive" class="btn btn-secondary mr-2">Verify</button>
                            <button type="button" id="disconnect-google-drive" class="btn btn-outline-danger">Disconnect</button>
                        @endif
                    </div>
                    <div class="col-md-6">
                        <h5>Backup Schedule & Content</h5>
                        <x-forms.select fieldId="schedule" fieldName="schedule" fieldLabel="Schedule">
                            <option value="manual" @selected($integration->schedule === 'manual')>Manual only</option>
                            <option value="daily" @selected($integration->schedule === 'daily')>Daily</option>
                            <option value="weekly" @selected($integration->schedule === 'weekly')>Weekly</option>
                        </x-forms.select>
                        <x-forms.select fieldId="retention_days" fieldName="retention_days" fieldLabel="Keep backups for">
                            @foreach([7,15,30,90] as $days)<option value="{{ $days }}" @selected((int)$integration->retention_days === $days)>{{ $days }} days</option>@endforeach
                        </x-forms.select>
                        <x-forms.checkbox fieldId="include_uploads" fieldName="include_uploads" fieldLabel="Include uploaded files/documents" :checked="$integration->include_uploads" />
                        <x-forms.checkbox fieldId="include_configuration" fieldName="include_configuration" fieldLabel="Include safe configuration/settings" :checked="$integration->include_configuration" />
                        <div class="alert alert-light border mt-3">File name format: <code>CRM-Database-YYYY-MM-DD-HHMMSS.zip</code></div>
                    </div>
                </div>
            </x-form>

            <hr>
            <div class="d-flex align-items-center justify-content-between mb-3">
                <h5 class="mb-0">Backup History</h5>
                <button type="button" id="backup-now" class="btn btn-primary" @disabled(!$integration->refresh_token)>Backup Now</button>
            </div>
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead><tr><th>File name</th><th>Date/time</th><th>Size</th><th>Status</th><th>Drive link</th><th>Error / Action</th></tr></thead>
                    <tbody>
                    @forelse($histories as $history)
                        <tr>
                            <td>{{ $history->file_name }}</td>
                            <td>{{ $history->created_at?->timezone(company()->timezone)->format('d-m-Y H:i') }}</td>
                            <td>{{ $history->file_size ? \App\Http\Controllers\DatabaseBackupSettingController::humanFileSize($history->file_size) : '-' }}</td>
                            <td><span class="badge badge-{{ $history->status === 'success' ? 'success' : ($history->status === 'failed' ? 'danger' : 'warning') }}">{{ ucfirst($history->status) }}</span></td>
                            <td>@if($history->google_drive_url)<a href="{{ $history->google_drive_url }}" target="_blank">Open Drive</a>@else - @endif</td>
                            <td>{{ $history->error_message ?: '-' }} @if($history->status === 'failed')<button data-url="{{ route('google-drive-backup.retry', $history) }}" class="btn btn-sm btn-outline-secondary retry-backup">Retry</button>@endif</td>
                        </tr>
                    @empty <tr><td colspan="6" class="text-center">No backups yet.</td></tr> @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </x-setting-card>
</div>
@endsection

@push('scripts')
<script>
const gdPost = (url, data = {}) => $.easyAjax({url, type:'POST', data:{...data, _token:'{{ csrf_token() }}'}, blockUI:true, success:()=>window.location.reload()});
$('#save-google-drive').click(()=>gdPost('{{ route('google-drive-backup.save') }}', $('#google-drive-settings-form').serialize()));
$('#verify-google-drive').click(()=>gdPost('{{ route('google-drive-backup.verify') }}'));
$('#disconnect-google-drive').click(()=>gdPost('{{ route('google-drive-backup.disconnect') }}'));
$('#backup-now').click(()=>gdPost('{{ route('google-drive-backup.now') }}'));
$('.retry-backup').click(function(){gdPost($(this).data('url'));});
</script>
@endpush
