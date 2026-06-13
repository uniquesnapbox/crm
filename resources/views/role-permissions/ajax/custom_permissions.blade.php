@php
    $customPermissions = $modulesData->customPermissions;
    $permissionLabelOverrides = [];

    if ($modulesData->module_name === 'leads') {
        $visiblePermissionNames = [
            'view_lead_follow_up',
            'add_lead_follow_up',
            'edit_lead_follow_up',
            'delete_lead_follow_up',
        ];

        $customPermissions = $customPermissions
            ->whereIn('name', $visiblePermissionNames)
            ->values();

        if (!empty($leadConvertPermission)) {
            $customPermissions->push($leadConvertPermission);
        }

        $permissionLabelOverrides = [
            'view_lead_follow_up' => 'View Follow-up',
            'add_lead_follow_up' => 'Add Follow-up',
            'edit_lead_follow_up' => 'Edit Follow-up',
            'delete_lead_follow_up' => 'Delete Follow-up',
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
