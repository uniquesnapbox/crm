<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('lead_assignees')) {
            Schema::create('lead_assignees', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedInteger('lead_id');
                $table->unsignedInteger('user_id');
                $table->unsignedInteger('assigned_by')->nullable();
                $table->timestamps();

                $table->unique(['lead_id', 'user_id'], 'lead_assignees_lead_user_unique');
                $table->index(['user_id', 'lead_id'], 'lead_assignees_user_lead_idx');
                $table->foreign('lead_id')->references('id')->on('leads')->cascadeOnDelete();
                $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
                $table->foreign('assigned_by')->references('id')->on('users')->nullOnDelete();
            });
        }

        // Preserve every existing primary assignment as a multi-assignee record.
        $now = now();
        DB::table('leads')
            ->select('id', 'assigned_to')
            ->whereNotNull('assigned_to')
            ->orderBy('id')
            ->chunkById(1000, function ($leads) use ($now) {
                $rows = $leads->map(fn ($lead) => [
                    'lead_id' => $lead->id,
                    'user_id' => $lead->assigned_to,
                    'assigned_by' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ])->all();

                if ($rows) {
                    DB::table('lead_assignees')->insertOrIgnore($rows);
                }
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('lead_assignees');
    }
};
