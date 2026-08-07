<?php

namespace App\Console\Commands;

use App\Jobs\SendDailyLeadFollowUpSummaryJob;
use App\Models\Company;
use Illuminate\Console\Command;

class SendDailyLeadFollowUpWhatsappSummary extends Command
{
    protected $signature = 'send-daily-lead-follow-up-whatsapp-summary';

    protected $description = 'Queue the daily WhatsApp summary for lead follow-ups.';

    public function handle(): int
    {
        Company::active()
            ->select('id')
            ->chunk(50, function ($companies) {
                foreach ($companies as $company) {
                    SendDailyLeadFollowUpSummaryJob::dispatch((int) $company->id)
                        ->onQueue('default');
                }
            });

        return Command::SUCCESS;
    }
}
