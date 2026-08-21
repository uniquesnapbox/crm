@props([
    'fieldId' => '',
    'fieldLabel' => '',
    'fieldRequired' => false,
    'fieldPlaceholder' => '',
    'fieldValue' => '',
    'fieldReadOnly' => false,
    'fieldHelp' => null,
    'fieldLabelInside' => false,
    'popover' => null,
])

<div {{ $attributes->merge(['class' => 'form-group my-3']) }}>
    @if (!($fieldLabelInside ?? false))
        <x-forms.label :fieldId="$fieldId" :fieldLabel="$fieldLabel" :fieldRequired="$fieldRequired" :popover="$popover"></x-forms.label>
    @endif

    @if ($fieldLabelInside ?? false)
        <div class="input-group">
            <div class="input-group-prepend">
                <span class="input-group-text bg-white text-dark-grey font-weight-semibold"
                    style="min-width: 82px; justify-content: center; font-size: 12px; border-radius: 8px 0 0 8px;">
                    {{ $fieldLabel }}
                    @if ($fieldRequired)
                        <sup class="text-danger ml-1">*</sup>
                    @endif
                </span>
            </div>
            <input type="text" class="form-control height-35 f-14" placeholder="{{ $fieldPlaceholder }}"
                value="{{ $fieldValue }}" name="{{ $fieldName }}" id="{{ $fieldId }}" @if ($fieldReadOnly == 'true') readonly @endif >
        </div>
    @else
        <input type="text" class="form-control height-35 f-14" placeholder="{{ $fieldPlaceholder }}"
            value="{{ $fieldValue }}" name="{{ $fieldName }}" id="{{ $fieldId }}" @if ($fieldReadOnly == 'true') readonly @endif >
    @endif

    @if ($fieldHelp)
        <small id="{{ $fieldId }}Help" class="form-text text-muted">{{ $fieldHelp }}</small>
    @endif
</div>
