<link rel="stylesheet" href="{{ asset('vendor/css/dropzone.min.css') }}">

<div class="modal-header">
    <h5 class="modal-title" id="modelHeading">Add Attachment</h5>
    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
            aria-hidden="true">Ã—</span></button>
</div>
<div class="modal-body">
    <div class="col-lg-12">
        <x-forms.file-multiple class="mr-0 mr-lg-2 mr-md-2" fieldLabel="Upload Quotation"
            fieldName="file" fieldId="lead-attachment-dropzone" :fieldRequired="true" />
    </div>
</div>
<div class="modal-footer">
    <x-forms.button-cancel data-dismiss="modal" class="border-0 mr-3">@lang('app.cancel')</x-forms.button-cancel>
    <x-forms.button-primary id="save-lead-attachments" disabled icon="check">@lang('app.save')</x-forms.button-primary>
</div>

<script>
    $(document).ready(function() {
        Dropzone.autoDiscover = false;

        leadAttachmentDropzone = new Dropzone("div#lead-attachment-dropzone", {
            dictDefaultMessage: "{{ __('app.dragDrop') }}",
            url: "{{ route('lead-contact.attachments.store') }}",
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            paramName: "file",
            maxFilesize: DROPZONE_MAX_FILESIZE,
            maxFiles: DROPZONE_MAX_FILES,
            autoProcessQueue: false,
            uploadMultiple: true,
            addRemoveLinks: true,
            parallelUploads: DROPZONE_MAX_FILES,
            acceptedFiles: DROPZONE_FILE_ALLOW,
            init: function() {
                leadAttachmentDropzone = this;
            }
        });

        leadAttachmentDropzone.on('sending', function(file, xhr, formData) {
            formData.append('lead_id', {{ $leadId }});
            $.easyBlockUI();
        });

        leadAttachmentDropzone.on('queuecomplete', function() {
            $.unblockUI();
            window.location.reload();
        });

        leadAttachmentDropzone.on('addedfile', function() {
            $('#save-lead-attachments').prop('disabled', false);
        });

        leadAttachmentDropzone.on('error', function(file, message) {
            leadAttachmentDropzone.removeFile(file);
            Swal.fire({
                icon: 'error',
                title: 'Upload failed',
                text: message
            });
        });
    });

    $('#save-lead-attachments').click(function() {
        leadAttachmentDropzone.processQueue();
    });
</script>
