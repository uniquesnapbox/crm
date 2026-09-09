@csrf
<div class="row">
    <div class="col-md-6">
        <x-forms.text fieldId="partner_name" fieldName="partner_name" fieldLabel="Partner Name" fieldRequired="true"
            :fieldValue="old('partner_name', $partner->partner_name ?? '')" />
    </div>
    <div class="col-md-6">
        <x-forms.text fieldId="company_name" fieldName="company_name" fieldLabel="Company/Agency Name"
            :fieldValue="old('company_name', $partner->company_name ?? '')" />
    </div>
    <div class="col-md-6">
        <x-forms.text fieldId="mobile" fieldName="mobile" fieldLabel="Mobile"
            :fieldValue="old('mobile', $partner->mobile ?? '')" />
    </div>
    <div class="col-md-6">
        <x-forms.email fieldId="email" fieldName="email" fieldLabel="Email"
            :fieldValue="old('email', $partner->email ?? '')" />
    </div>
    <div class="col-md-12">
        <x-forms.textarea fieldId="address" fieldName="address" fieldLabel="Address">{{ old('address', $partner->address ?? '') }}</x-forms.textarea>
    </div>
    <div class="col-md-12">
        <x-forms.textarea fieldId="notes" fieldName="notes" fieldLabel="Notes">{{ old('notes', $partner->notes ?? '') }}</x-forms.textarea>
    </div>
    <div class="col-md-6">
        <x-forms.select fieldId="status" fieldName="status" fieldLabel="Status" fieldRequired="true">
            <option value="active" @selected(old('status', $partner->status ?? 'active') === 'active')>Active</option>
            <option value="inactive" @selected(old('status', $partner->status ?? 'active') === 'inactive')>Inactive</option>
        </x-forms.select>
    </div>
</div>
<div class="mt-3">
    <button class="btn btn-primary" type="submit">Save Partner</button>
    <a href="{{ route('partners.index') }}" class="btn btn-light">Cancel</a>
</div>
