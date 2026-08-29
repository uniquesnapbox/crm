<form action="" {{ $attributes->merge(['class' => 'align-self-center d-none']) }} id="quick-action-form">
    @csrf
    <div class="d-flex align-items-center" id="quick-actions">
        {{ $slot }}
        <div class="select-status">
            <x-forms.button-primary id="quick-action-apply" disabled>@lang('app.apply')</x-forms.button-primary>
        </div>
    </div>

</form>
