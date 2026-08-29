<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Services\LeadFollowUpWhatsAppReminderService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SendLeadFollowUpWhatsappReminders extends Command
{
    protected $signature = 'send-lead-followup-whatsapp-reminders {--backfill-hours=0 : Also scan missed reminders from the last N hours.}';

    protected $description = 'Send WhatsApp reminders for pending lead follow-ups with optional safe backfill.';

    public function __construct(private LeadFollowUpWhatsAppReminderService $reminderService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $backfillHours = max(0, (int) $this->option('backfill-hours'));

        if ($backfillHours > 0) {
            $this->info(sprintf('Running lead follow-up WhatsApp reminder backfill for the last %d hours.', $backfillHours));
        }

        Company::active()
            ->select('id')
            ->chunk(50, function ($companies) use ($backfillHours) {
                foreach ($companies as $company) {
                    try {
                        if ($backfillHours > 0) {
                            $this->reminderService->backfillMissedRemindersForCompany((int) $company->id, $backfillHours);
                            continue;
                        }

                        $this->reminderService->sendRemindersForCompany((int) $company->id);
                    } catch (\Throwable $exception) {
                        Log::warning('Lead follow-up WhatsApp reminder command failed.', [
                            'company_id' => $company->id,
                            'error' => $exception->getMessage(),
                        ]);
                    }
                }
            });

        return Command::SUCCESS;
    }
}
