<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const PERMISSIONS = [
        'view_bulk_whatsapp' => 'View Bulk WhatsApp',
        'send_bulk_whatsapp' => 'Send Bulk WhatsApp Campaign',
    ];

    public function up(): void
    {
        $moduleId = DB::table('modules')->where('module_name', 'leads')->value('id');
        if (!$moduleId) {
            return;
        }

        $allTypeId = DB::table('permission_types')->where('name', 'all')->value('id');
        $noneTypeId = DB::table('permission_types')->where('name', 'none')->value('id');
        if (!$allTypeId || !$noneTypeId) {
            return;
        }

        $permissionIds = [];
        foreach (self::PERMISSIONS as $name => $displayName) {
            DB::table('permissions')->updateOrInsert(
                ['name' => $name],
                [
                    'display_name' => $displayName,
                    'description' => null,
                    'module_id' => $moduleId,
                    'is_custom' => 1,
                    'allowed_permissions' => '{"all":4, "none":5}',
                    'updated_at' => now(),
                ]
            );

            $permissionIds[] = DB::table('permissions')->where('name', $name)->value('id');
        }

        $roles = DB::table('roles')->select('id', 'name')->get();
        foreach ($roles as $role) {
            $permissionTypeId = $role->name === 'admin' ? $allTypeId : $noneTypeId;

            foreach ($permissionIds as $permissionId) {
                DB::table('permission_role')->updateOrInsert(
                    ['permission_id' => $permissionId, 'role_id' => $role->id],
                    ['permission_type_id' => $permissionTypeId]
                );

                $userIds = DB::table('role_user')->where('role_id', $role->id)->pluck('user_id');
                foreach ($userIds as $userId) {
                    // Keep an employee's individually customised permission unchanged.
                    if (!DB::table('user_permissions')
                        ->where('permission_id', $permissionId)
                        ->where('user_id', $userId)
                        ->exists()) {
                        DB::table('user_permissions')->insert([
                            'permission_id' => $permissionId,
                            'user_id' => $userId,
                            'permission_type_id' => $permissionTypeId,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }
            }
        }
    }

    public function down(): void
    {
        $permissionIds = DB::table('permissions')
            ->whereIn('name', array_keys(self::PERMISSIONS))
            ->pluck('id');

        if ($permissionIds->isEmpty()) {
            return;
        }

        DB::table('user_permissions')->whereIn('permission_id', $permissionIds)->delete();
        DB::table('permission_role')->whereIn('permission_id', $permissionIds)->delete();
        DB::table('permissions')->whereIn('id', $permissionIds)->delete();
    }
};
