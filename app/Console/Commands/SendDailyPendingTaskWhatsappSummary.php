<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Services\EmployeeDailyWhatsAppSummaryService;
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
    protected $description = 'Send daily morning WhatsApp summary to employees.';

    public function __construct(private EmployeeDailyWhatsAppSummaryService $summaryService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        Company::active()->select('id')->chunk(50, function ($companies) {
            foreach ($companies as $company) {
                try {
                    $this->summaryService->sendDailySummaryForCompany((int) $company->id);
                } catch (\Throwable $exception) {
                    Log::warning('Daily employee WhatsApp summary failed for company.', [
                        'company_id' => $company->id,
                        'error' => $exception->getMessage(),
                    ]);
                }
            }
        });

        return Command::SUCCESS;
    }
}
