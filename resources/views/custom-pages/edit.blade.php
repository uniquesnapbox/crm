<x-form id="editCustomPage">
    <div class="modal-header">
        <h5 class="modal-title" id="modelHeading">Edit Custom Page</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button>
    </div>

    <div class="modal-body">
        @method('PUT')
        <div class="row">
            <div class="col-md-6">
                <x-forms.text fieldId="page_title" fieldName="page_title" fieldRequired="true"
                    fieldLabel="Page Title" fieldPlaceholder="Enter page title" :fieldValue="$customPage->page_title" />
            </div>
            <div class="col-md-6">
                <x-forms.text fieldId="slug" fieldName="slug"
                    fieldLabel="Slug" fieldPlaceholder="leave blank to auto-generate from title" :fieldValue="$customPage->slug" />
            </div>
            <div class="col-md-12">
                <x-forms.textarea fieldId="content" fieldName="content"
                    fieldLabel="Page Content" fieldPlaceholder="Write your page content here" fieldRequired="true" :fieldValue="$customPage->content" />
            </div>
            <div class="col-md-6">
                <x-forms.select fieldId="status" fieldName="status" fieldLabel="Status" search="true">
                    <option value="active" @selected($customPage->status === 'active')>Active</option>
                    <option value="inactive" @selected($customPage->status === 'inactive')>Inactive</option>
                </x-forms.select>
            </div>
        </div>
    </div>

    <div class="modal-footer">
        <x-forms.button-cancel data-dismiss="modal" class="border-0">Cancel</x-forms.button-cancel>
        <x-forms.button-primary id="save-form" class="mr-3" icon="check">Save</x-forms.button-primary>
    </div>
</x-form>

<script>
    $('#page_title').on('keyup blur', function () {
        if ($('#slug').val().trim() === '') {
            $('#slug').val($(this).val().toLowerCase().trim().replace(/[^a-z0-9]+/g, '-').replace(/(^-|-$)/g, ''));
        }
    });

    $('#save-form').click(function () {
        $.easyAjax({
            url: "{{ route('custom-pages.update', [$customPage->id]) }}",
            container: '#editCustomPage',
            type: 'POST',
            blockUI: true,
            disableButton: true,
            buttonSelector: '#save-form',
            data: $('#editCustomPage').serialize(),
            success: function (response) {
                if (response.status === 'success') {
                    window.location.reload();
                }
            }
        });
    });
</script>
