<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $permissionIds = DB::table('permissions')
            ->whereIn('name', [
                'add_partner',
                'view_partner',
                'edit_partner',
                'delete_partner',
                'view_partner_commission',
                'add_partner_sale',
                'add_partner_commission_payment',
                'view_partner_report',
            ])
            ->pluck('id');

        $adminUserIds = DB::table('role_user')
            ->join('roles', 'roles.id', '=', 'role_user.role_id')
            ->where('roles.name', 'admin')
            ->pluck('role_user.user_id')
            ->unique();

        foreach ($adminUserIds as $userId) {
            foreach ($permissionIds as $permissionId) {
                DB::table('user_permissions')->updateOrInsert(
                    ['user_id' => $userId, 'permission_id' => $permissionId],
                    ['permission_type_id' => 4]
                );
            }
        }
    }

    public function down(): void
    {
        // Do not remove permission rows on rollback; these may have been edited after deployment.
    }
};
