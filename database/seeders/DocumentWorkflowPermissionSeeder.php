<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Seeder;

class DocumentWorkflowPermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'view_documents',
            'add_documents',
            'edit_documents',
            'delete_documents',
            'send_documents',
            'approve_documents',
            'sign_documents',
            'manage_document_templates',
            'view_document_audit',
            'manage_document_settings',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(
                ['name' => $permission],
                ['is_custom' => 0, 'allowed_permissions' => 'all,added,owned,both,none']
            );
        }
    }
}
