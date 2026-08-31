<?php

use App\Models\Company;
use App\Models\TicketCustomForm;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $companies = Company::query()->select('id')->get();

        foreach ($companies as $company) {
            $emailField = TicketCustomForm::query()
                ->where('company_id', $company->id)
                ->whereNull('custom_fields_id')
                ->where('field_name', 'email')
                ->first();

            if ($emailField) {
                $emailField->update([
                    'status' => 'active',
                    'required' => 1,
                ]);

                continue;
            }

            TicketCustomForm::query()
                ->where('company_id', $company->id)
                ->whereNull('custom_fields_id')
                ->where('field_order', '>=', 2)
                ->increment('field_order');

            TicketCustomForm::create([
                'field_display_name' => 'Email',
                'field_name' => 'email',
                'field_type' => 'text',
                'field_order' => 2,
                'required' => 1,
                'status' => 'active',
                'company_id' => $company->id,
            ]);
        }
    }

    public function down(): void
    {
        TicketCustomForm::query()
            ->whereNull('custom_fields_id')
            ->where('field_name', 'email')
            ->update([
                'status' => 'inactive',
                'required' => 0,
            ]);
    }
};
