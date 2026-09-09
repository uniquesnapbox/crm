<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private array $permissions = [
        ['name' => 'add_partner', 'display_name' => 'Add Partner', 'is_custom' => 0, 'allowed_permissions' => '{"all":4,"added":1,"none":5}'],
        ['name' => 'view_partner', 'display_name' => 'View Partners', 'is_custom' => 0, 'allowed_permissions' => '{"all":4,"added":1,"owned":2,"both":3,"none":5}'],
        ['name' => 'edit_partner', 'display_name' => 'Edit Partner', 'is_custom' => 0, 'allowed_permissions' => '{"all":4,"added":1,"owned":2,"both":3,"none":5}'],
        ['name' => 'delete_partner', 'display_name' => 'Delete Partner', 'is_custom' => 0, 'allowed_permissions' => '{"all":4,"added":1,"owned":2,"both":3,"none":5}'],
        ['name' => 'view_partner_commission', 'display_name' => 'View Partner Commission', 'is_custom' => 1, 'allowed_permissions' => '{"all":4,"none":5}'],
        ['name' => 'add_partner_sale', 'display_name' => 'Record Partner Sale', 'is_custom' => 1, 'allowed_permissions' => '{"all":4,"added":1,"none":5}'],
        ['name' => 'add_partner_commission_payment', 'display_name' => 'Record Partner Commission Payment', 'is_custom' => 1, 'allowed_permissions' => '{"all":4,"none":5}'],
        ['name' => 'view_partner_report', 'display_name' => 'View Partner Reports', 'is_custom' => 1, 'allowed_permissions' => '{"all":4,"none":5}'],
    ];

    public function up(): void
    {
        $moduleId = DB::table('modules')->where('module_name', 'partners')->value('id');

        if (!$moduleId) {
            $moduleId = DB::table('modules')->insertGetId([
                'module_name' => 'partners',
                'description' => 'Partner sales and commission management',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        foreach ($this->permissions as $permission) {
            DB::table('permissions')->updateOrInsert(
                ['name' => $permission['name']],
                $permission + ['module_id' => $moduleId, 'updated_at' => now(), 'created_at' => now()]
            );
        }

        $companies = DB::table('companies')->pluck('id');
        foreach ($companies as $companyId) {
            foreach (['admin', 'employee'] as $type) {
                DB::table('module_settings')->updateOrInsert(
                    ['company_id' => $companyId, 'module_name' => 'partners', 'type' => $type],
                    ['status' => 'active', 'updated_at' => now(), 'created_at' => now()]
                );
            }

            $permissionIds = DB::table('permissions')->where('module_id', $moduleId)->pluck('id');
            $roles = DB::table('roles')->where('company_id', $companyId)->get(['id', 'name']);
            foreach ($roles as $role) {
                foreach ($permissionIds as $permissionId) {
                    DB::table('permission_role')->updateOrInsert(
                        ['permission_id' => $permissionId, 'role_id' => $role->id],
                        ['permission_type_id' => $role->name === 'admin' ? 4 : 5]
                    );
                }
            }
        }
    }

    public function down(): void
    {
        $moduleId = DB::table('modules')->where('module_name', 'partners')->value('id');
        if (!$moduleId) {
            return;
        }

        $permissionIds = DB::table('permissions')->where('module_id', $moduleId)->pluck('id');
        DB::table('permission_role')->whereIn('permission_id', $permissionIds)->delete();
        DB::table('user_permissions')->whereIn('permission_id', $permissionIds)->delete();
        DB::table('permissions')->whereIn('id', $permissionIds)->delete();
        DB::table('module_settings')->where('module_name', 'partners')->delete();
        DB::table('modules')->where('id', $moduleId)->delete();
    }
};
