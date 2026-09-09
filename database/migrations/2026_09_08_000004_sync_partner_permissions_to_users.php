<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $moduleId = DB::table('modules')->where('module_name', 'partners')->value('id');
        if (!$moduleId) {
            return;
        }

        $permissionIds = DB::table('permissions')->where('module_id', $moduleId)->pluck('id');
        $rows = DB::table('role_user')
            ->join('permission_role', 'permission_role.role_id', '=', 'role_user.role_id')
            ->join('roles', 'roles.id', '=', 'role_user.role_id')
            ->whereIn('permission_role.permission_id', $permissionIds)
            ->select('role_user.user_id', 'permission_role.permission_id', 'permission_role.permission_type_id')
            ->get();

        foreach ($rows as $row) {
            DB::table('user_permissions')->updateOrInsert(
                ['user_id' => $row->user_id, 'permission_id' => $row->permission_id],
                ['permission_type_id' => $row->permission_type_id]
            );
        }
    }

    public function down(): void
    {
        $moduleId = DB::table('modules')->where('module_name', 'partners')->value('id');
        if ($moduleId) {
            DB::table('user_permissions')->whereIn('permission_id', DB::table('permissions')->where('module_id', $moduleId)->pluck('id'))->delete();
        }
    }
};
