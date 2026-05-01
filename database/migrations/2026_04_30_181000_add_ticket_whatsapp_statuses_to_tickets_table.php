<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $columns = [
                'whatsapp_assigned_staff_sent_at' => fn(Blueprint $t) => $t->timestamp('whatsapp_assigned_staff_sent_at')->nullable()->after('close_date'),
                'whatsapp_assigned_staff_status' => fn(Blueprint $t) => $t->string('whatsapp_assigned_staff_status', 20)->nullable()->after('whatsapp_assigned_staff_sent_at'),
                'whatsapp_assigned_staff_error' => fn(Blueprint $t) => $t->text('whatsapp_assigned_staff_error')->nullable()->after('whatsapp_assigned_staff_status'),
                'whatsapp_assigned_client_sent_at' => fn(Blueprint $t) => $t->timestamp('whatsapp_assigned_client_sent_at')->nullable()->after('whatsapp_assigned_staff_error'),
                'whatsapp_assigned_client_status' => fn(Blueprint $t) => $t->string('whatsapp_assigned_client_status', 20)->nullable()->after('whatsapp_assigned_client_sent_at'),
                'whatsapp_assigned_client_error' => fn(Blueprint $t) => $t->text('whatsapp_assigned_client_error')->nullable()->after('whatsapp_assigned_client_status'),
                'whatsapp_resolved_client_sent_at' => fn(Blueprint $t) => $t->timestamp('whatsapp_resolved_client_sent_at')->nullable()->after('whatsapp_assigned_client_error'),
                'whatsapp_resolved_client_status' => fn(Blueprint $t) => $t->string('whatsapp_resolved_client_status', 20)->nullable()->after('whatsapp_resolved_client_sent_at'),
                'whatsapp_resolved_client_error' => fn(Blueprint $t) => $t->text('whatsapp_resolved_client_error')->nullable()->after('whatsapp_resolved_client_status'),
            ];

            foreach ($columns as $column => $callback) {
                if (!Schema::hasColumn('tickets', $column)) {
                    $callback($table);
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            foreach ([
                'whatsapp_resolved_client_error',
                'whatsapp_resolved_client_status',
                'whatsapp_resolved_client_sent_at',
                'whatsapp_assigned_client_error',
                'whatsapp_assigned_client_status',
                'whatsapp_assigned_client_sent_at',
                'whatsapp_assigned_staff_error',
                'whatsapp_assigned_staff_status',
                'whatsapp_assigned_staff_sent_at',
            ] as $column) {
                if (Schema::hasColumn('tickets', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
