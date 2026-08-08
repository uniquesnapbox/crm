<?php

namespace App\Console\Commands;

use App\Jobs\SendDailyLeadFollowUpSummaryJob;
use App\Models\Company;
use Illuminate\Console\Command;

class SendDailyLeadFollowUpWhatsappSummary extends Command
{
    protected $signature = 'send-daily-lead-follow-up-whatsapp-summary
        {--force : Send immediately without the configured time or daily duplicate guard}';

    protected $description = 'Queue the daily WhatsApp summary for lead follow-ups.';

    public function handle(): int
    {
        $force = (bool) $this->option('force');
        $queued = 0;

        Company::active()
            ->select('id')
            ->chunk(50, function ($companies) use ($force, &$queued) {
                foreach ($companies as $company) {
                    SendDailyLeadFollowUpSummaryJob::dispatch((int) $company->id, $force)
                        ->onQueue('default');
                    $queued++;
                }
            });

        $this->info("Queued lead follow-up summaries for {$queued} active companies.");

        return Command::SUCCESS;
    }
}
