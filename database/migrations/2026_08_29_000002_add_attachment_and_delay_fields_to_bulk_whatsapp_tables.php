<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('bulk_whatsapp_templates')) {
            Schema::table('bulk_whatsapp_templates', function (Blueprint $table) {
                if (!Schema::hasColumn('bulk_whatsapp_templates', 'attachment_path')) {
                    $table->string('attachment_path', 500)->nullable()->after('message');
                }

                if (!Schema::hasColumn('bulk_whatsapp_templates', 'attachment_name')) {
                    $table->string('attachment_name', 255)->nullable()->after('attachment_path');
                }

                if (!Schema::hasColumn('bulk_whatsapp_templates', 'attachment_mime')) {
                    $table->string('attachment_mime', 100)->nullable()->after('attachment_name');
                }

                if (!Schema::hasColumn('bulk_whatsapp_templates', 'attachment_size')) {
                    $table->unsignedInteger('attachment_size')->nullable()->after('attachment_mime');
                }
            });
        }

        if (Schema::hasTable('bulk_whatsapp_campaigns')) {
            Schema::table('bulk_whatsapp_campaigns', function (Blueprint $table) {
                if (!Schema::hasColumn('bulk_whatsapp_campaigns', 'attachment_path')) {
                    $table->string('attachment_path', 500)->nullable()->after('message_body');
                }

                if (!Schema::hasColumn('bulk_whatsapp_campaigns', 'attachment_name')) {
                    $table->string('attachment_name', 255)->nullable()->after('attachment_path');
                }

                if (!Schema::hasColumn('bulk_whatsapp_campaigns', 'attachment_mime')) {
                    $table->string('attachment_mime', 100)->nullable()->after('attachment_name');
                }

                if (!Schema::hasColumn('bulk_whatsapp_campaigns', 'attachment_size')) {
                    $table->unsignedInteger('attachment_size')->nullable()->after('attachment_mime');
                }

                if (!Schema::hasColumn('bulk_whatsapp_campaigns', 'delay_min_seconds')) {
                    $table->unsignedSmallInteger('delay_min_seconds')->default(8)->after('attachment_size');
                }

                if (!Schema::hasColumn('bulk_whatsapp_campaigns', 'delay_max_seconds')) {
                    $table->unsignedSmallInteger('delay_max_seconds')->default(20)->after('delay_min_seconds');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('bulk_whatsapp_campaigns')) {
            Schema::table('bulk_whatsapp_campaigns', function (Blueprint $table) {
                if (Schema::hasColumn('bulk_whatsapp_campaigns', 'delay_max_seconds')) {
                    $table->dropColumn('delay_max_seconds');
                }

                if (Schema::hasColumn('bulk_whatsapp_campaigns', 'delay_min_seconds')) {
                    $table->dropColumn('delay_min_seconds');
                }

                if (Schema::hasColumn('bulk_whatsapp_campaigns', 'attachment_size')) {
                    $table->dropColumn('attachment_size');
                }

                if (Schema::hasColumn('bulk_whatsapp_campaigns', 'attachment_mime')) {
                    $table->dropColumn('attachment_mime');
                }

                if (Schema::hasColumn('bulk_whatsapp_campaigns', 'attachment_name')) {
                    $table->dropColumn('attachment_name');
                }

                if (Schema::hasColumn('bulk_whatsapp_campaigns', 'attachment_path')) {
                    $table->dropColumn('attachment_path');
                }
            });
        }

        if (Schema::hasTable('bulk_whatsapp_templates')) {
            Schema::table('bulk_whatsapp_templates', function (Blueprint $table) {
                if (Schema::hasColumn('bulk_whatsapp_templates', 'attachment_size')) {
                    $table->dropColumn('attachment_size');
                }

                if (Schema::hasColumn('bulk_whatsapp_templates', 'attachment_mime')) {
                    $table->dropColumn('attachment_mime');
                }

                if (Schema::hasColumn('bulk_whatsapp_templates', 'attachment_name')) {
                    $table->dropColumn('attachment_name');
                }

                if (Schema::hasColumn('bulk_whatsapp_templates', 'attachment_path')) {
                    $table->dropColumn('attachment_path');
                }
            });
        }
    }
};
