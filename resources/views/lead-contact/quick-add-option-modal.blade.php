@php
    $config = [
        'source' => ['title' => 'Add Source', 'label' => 'Source Name'],
        'category' => ['title' => 'Add Lead Category', 'label' => 'Category Name'],
        'status' => ['title' => 'Add Lead Status', 'label' => 'Status Name'],
        'product' => ['title' => 'Add Product / Service', 'label' => 'Product / Service Name'],
    ][$quickAddType];
@endphp

<x-form id="lead-quick-add-form" method="POST" class="ajax-form">
    <div class="modal-header">
        <h5 class="modal-title">{{ $config['title'] }}</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>

    <div class="modal-body">
        <x-forms.text fieldId="quick_add_name" :fieldLabel="$config['label']" fieldName="name"
            fieldRequired="true" />

        @if ($quickAddType === 'product')
            <div class="form-group my-3">
                <x-forms.label fieldId="quick_add_price" fieldLabel="Price" fieldRequired="true" />
                <input type="number" min="0" step="0.01" class="form-control height-35 f-14"
                    id="quick_add_price" name="price" value="0" />
            </div>
        @endif

        @if ($quickAddType === 'status')
            <div class="form-group my-3">
                <x-forms.label fieldId="quick_add_color" fieldLabel="Label Color" fieldRequired="true" />
                <input type="color" class="form-control height-35" id="quick_add_color"
                    name="label_color" value="#16813D" />
            </div>
        @endif
    </div>

    <div class="modal-footer">
        <x-forms.button-cancel data-dismiss="modal" class="border-0 mr-3">@lang('app.close')</x-forms.button-cancel>
        <x-forms.button-primary id="save-lead-quick-option" icon="check">@lang('app.save')</x-forms.button-primary>
    </div>
</x-form>

<script>
    $('#save-lead-quick-option').off('click').on('click', function() {
        $.easyAjax({
            url: "{{ route('lead-contact.quick_add_store', $quickAddType) }}",
            container: '#lead-quick-add-form',
            type: 'POST',
            blockUI: true,
            disableButton: true,
            buttonSelector: '#save-lead-quick-option',
            data: $('#lead-quick-add-form').serialize(),
            success: function(response) {
                if (response.status !== 'success') {
                    return;
                }

                const option = response.option;
                const $select = $('.js-lead-inline-field[data-field="' + response.field + '"]').first();

                if (!$select.length) {
                    $(MODAL_LG).modal('hide');
                    return;
                }

                const optionExists = $select.find('option').filter(function() {
                    return $(this).val().toString() === option.value.toString();
                }).length > 0;

                if (!optionExists) {
                    $select.append(new Option(option.label, option.value, true, true));
                }

                if ($select.prop('multiple')) {
                    const values = $select.val() || [];
                    if (values.map(String).indexOf(option.value.toString()) === -1) {
                        values.push(option.value.toString());
                    }
                    $select.val(values);
                } else {
                    $select.val(option.value.toString());
                }

                if (typeof $select.selectpicker === 'function') {
                    $select.selectpicker('refresh');
                }

                $select.trigger('change');
                $(MODAL_LG).modal('hide');
            }
        });
    });
</script>
