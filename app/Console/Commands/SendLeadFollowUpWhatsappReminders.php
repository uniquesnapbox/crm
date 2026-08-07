<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Services\LeadFollowUpWhatsAppReminderService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SendLeadFollowUpWhatsappReminders extends Command
{
    protected $signature = 'send-lead-followup-whatsapp-reminders';

    protected $description = 'Send WhatsApp reminders exactly 10 minutes before pending lead follow-ups.';

    public function __construct(private LeadFollowUpWhatsAppReminderService $reminderService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        Company::active()
            ->select('id')
            ->chunk(50, function ($companies) {
                foreach ($companies as $company) {
                    try {
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
