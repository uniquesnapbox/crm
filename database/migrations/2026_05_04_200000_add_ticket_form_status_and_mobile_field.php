<?php

use App\Models\Company;
use App\Models\TicketCustomForm;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('companies', 'ticket_form_status')) {
            Schema::table('companies', function (Blueprint $table) {
                $table->enum('ticket_form_status', ['active', 'inactive'])
                    ->default('active')
                    ->after('ticket_form_google_captcha');
            });
        }

        DB::table('companies')
            ->whereNull('ticket_form_status')
            ->update(['ticket_form_status' => 'active']);

        $companies = Company::query()->select('id')->get();

        foreach ($companies as $company) {
            $exists = TicketCustomForm::query()
                ->where('company_id', $company->id)
                ->where('field_name', 'mobile')
                ->exists();

            if ($exists) {
                continue;
            }

            $maxOrder = (int) (TicketCustomForm::query()
                ->where('company_id', $company->id)
                ->max('field_order') ?? 0);

            TicketCustomForm::create([
                'field_display_name' => 'Mobile Number',
                'field_name' => 'mobile',
                'field_type' => 'text',
                'field_order' => $maxOrder + 1,
                'required' => 0,
                'status' => 'active',
                'company_id' => $company->id,
            ]);
        }
    }

    public function down(): void
    {
        TicketCustomForm::query()
            ->where('field_name', 'mobile')
            ->whereNull('custom_fields_id')
            ->delete();

        if (Schema::hasColumn('companies', 'ticket_form_status')) {
            Schema::table('companies', function (Blueprint $table) {
                $table->dropColumn('ticket_form_status');
            });
        }
    }
};

