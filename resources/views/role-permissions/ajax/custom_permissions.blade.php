@php
    $customPermissions = $modulesData->customPermissions;
    $permissionLabelOverrides = [];

    if ($modulesData->module_name === 'leads') {
        $visiblePermissionNames = [
            'view_bulk_whatsapp',
            'send_bulk_whatsapp',
            'view_lead_follow_up',
            'add_lead_follow_up',
            'edit_lead_follow_up',
            'delete_lead_follow_up',
            'view_lead_sources',
            'add_lead_sources',
            'edit_lead_sources',
            'delete_lead_sources',
            'view_lead_category',
            'add_lead_category',
            'edit_lead_category',
            'delete_lead_category',
        ];

        $customPermissions = $customPermissions
            ->whereIn('name', $visiblePermissionNames)
            ->sortBy(function ($permission) use ($visiblePermissionNames) {
                return array_search($permission->name, $visiblePermissionNames, true);
            })
            ->values();

        if (!empty($leadConvertPermission)) {
            $customPermissions->push($leadConvertPermission);
        }

        $permissionLabelOverrides = [
            'view_bulk_whatsapp' => 'View Bulk WhatsApp',
            'send_bulk_whatsapp' => 'Send Bulk WhatsApp Campaign',
            'view_lead_follow_up' => 'View Follow-up',
            'add_lead_follow_up' => 'Add Follow-up',
            'edit_lead_follow_up' => 'Edit Follow-up',
            'delete_lead_follow_up' => 'Delete Follow-up',
            'view_lead_sources' => 'View Lead Sources',
            'add_lead_sources' => 'Add Lead Sources',
            'edit_lead_sources' => 'Edit Lead Sources',
            'delete_lead_sources' => 'Delete Lead Sources',
            'view_lead_category' => 'View Lead Category',
            'add_lead_category' => 'Add Lead Category',
            'edit_lead_category' => 'Edit Lead Category',
            'delete_lead_category' => 'Delete Lead Category',
            'add_clients' => 'Convert to Client',
        ];
    }
@endphp

<tr class="custom-permissions" id="module-custom-permission-{{ $modulesData->id }}">
    <td></td>
    <td colspan="4">
        <table class="table table-bordered rounded">
            @foreach ($customPermissions as $permission)
                <tr>
                    <td>
                        <h6 class="heading-h6">{{ $permissionLabelOverrides[$permission->name] ?? __('permissions.'.$permission->name) }}</h6>
                    </td>
                    @php
                        $permissionType = $role->permissionType($permission->id);
                        if (!($permissionType)) {
                            $permissionType = 5;
                        }
                        $allowedPermissions = json_decode($permission->allowed_permissions);
                    @endphp
                    <td>
                        <select class="select-picker role-permission-select border-0"
                            data-permission-id="{{ $permission->id }}" data-role-id="{{ $role->id }}">
                            @if (!is_null($allowedPermissions))
                                @foreach ($allowedPermissions as $key => $item)
                                    <option @if ($permissionType == $item) selected @endif value="{{ $item }}">@lang('app.'.$key)</option>
                                @endforeach
                            @endif
                        </select>
                    </td>
                </tr>
            @endforeach

        </table>
    </td>
    <td></td>
</tr>
