@props([
    'fieldId' => '',
    'fieldLabel' => '',
    'fieldRequired' => false,
    'fieldName' => '',
    'fieldPlaceholder' => '',
    'fieldValue' => '',
    'fieldHelp' => null,
    'multiple' => false,
    'search' => false,
    'alignRight' => false,
    'popover' => null,
    'fieldLabelInside' => false,
])

<div {{ $attributes->merge(['class' => 'form-group mb-0']) }}>
    @if (!($fieldLabelInside ?? false))
        <x-forms.label :fieldId="$fieldId" :fieldLabel="$fieldLabel" :fieldRequired="$fieldRequired" :popover="$popover"
            class="mt-3"></x-forms.label>

        <select name="{{ $fieldName }}" id="{{ $fieldId }}" @if ($multiple) multiple @endif @if ($search)
            data-live-search="true"
            @endif

            class="form-control select-picker" data-size="8"
            @if ($alignRight) data-dropdown-align-right="true" @endif
            >
            {!! $slot !!}
        </select>
    @else
        <div class="input-group">
            <div class="input-group-prepend">
                <span class="input-group-text bg-white text-dark-grey font-weight-semibold"
                    style="min-width: 96px; justify-content: center; font-size: 12px; border-radius: 8px 0 0 8px; border-right: 0;">
                    {{ $fieldLabel }}
                    @if ($fieldRequired)
                        <sup class="text-danger ml-1">*</sup>
                    @endif
                    @if (!is_null($popover))
                        <i class="fa fa-question-circle ml-1" data-toggle="popover" data-placement="top"
                            data-content="{{ $popover }}" data-html="true" data-trigger="hover"></i>
                    @endif
                </span>
            </div>

            <select name="{{ $fieldName }}" id="{{ $fieldId }}" @if ($multiple) multiple @endif @if ($search)
                data-live-search="true"
                @endif
                class="form-control select-picker" data-size="8"
                style="border-radius: 0 8px 8px 0;"
                @if ($alignRight) data-dropdown-align-right="true" @endif
                >
                {!! $slot !!}
            </select>

            @if (isset($append) && trim((string) $append) !== '')
                <div class="input-group-append">
                    {!! $append !!}
                </div>
            @endif
        </div>
    @endif

</div>
