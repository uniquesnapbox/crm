<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Services\TaskWhatsAppNotificationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SendDailyPendingTaskWhatsappSummary extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'send-daily-pending-task-whatsapp-summary';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send daily morning WhatsApp summary of pending tasks to assigned members.';

    public function __construct(private TaskWhatsAppNotificationService $taskWhatsAppService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        Company::active()->select('id')->chunk(50, function ($companies) {
            foreach ($companies as $company) {
                try {
                    $this->taskWhatsAppService->sendDailyPendingSummaryForCompany((int) $company->id);
                } catch (\Throwable $exception) {
                    Log::warning('Daily pending-task WhatsApp summary failed for company.', [
                        'company_id' => $company->id,
                        'error' => $exception->getMessage(),
                    ]);
                }
            }
        });

        return Command::SUCCESS;
    }
}
