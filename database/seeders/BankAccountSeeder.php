<?php

namespace Database\Seeders;

use App\Models\BankAccount;
use App\Models\BankTransaction;
use App\Models\Currency;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BankAccountSeeder extends Seeder
{

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run($companyId)
    {
        $currencyId = Currency::where('company_id', $companyId)->first()->id;;

        DB::beginTransaction();

        $bankAccounts = ['Primary Account', 'Secondary Account'];

        foreach ($bankAccounts as $key => $bankAccount) {
            $account = new BankAccount();
            $account->company_id    = $companyId;
            $account->type    = 'bank';
            $account->account_name    = 'Account ' . ($key + 1);
            $account->account_type  = 'current';
            $account->currency_id     = $currencyId;
            $account->contact_number  = '90000000' . ($key + 1);
            $account->opening_balance = rand(10000, 99999);
            $account->status          = 1;
            $account->bank_name    = $bankAccount;
            /** @phpstan-ignore-next-line */
            $account->account_number  = (string) rand(1000000000, 9999999999);
            $account->save();
        }

        DB::commit();

    }

}
