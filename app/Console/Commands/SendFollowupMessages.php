<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Models\DealFollowUp;
use App\Modules\WhatsApp\WhatsAppService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class SendFollowupMessages extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'send-followup-messages';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send WhatsApp follow-up messages for today followups';

    public function handle(): int
    {
        Company::active()->chunk(50, function ($companies) {
            foreach ($companies as $company) {
                $this->sendForCompany($company->id, $company->timezone);
            }
        });

        return Command::SUCCESS;
    }

    private function sendForCompany(int $companyId, string $timezone): void
    {
        $today = now($timezone)->toDateString();
        $hasSentAtColumn = Schema::hasColumn('lead_follow_up', 'whatsapp_sent_at');

        $followups = DealFollowUp::with('lead.contact')
            ->whereDate('next_follow_up_date', $today)
            ->whereHas('lead', function ($query) use ($companyId) {
                $query->where('company_id', $companyId);
            })
            ->where('status', 'pending')
            ->when($hasSentAtColumn, function ($query) {
                $query->whereNull('whatsapp_sent_at');
            })
            ->get();

        $whatsApp = app(WhatsAppService::class);

        foreach ($followups as $followup) {
            $contact = optional($followup->lead)->contact;

            if (!$contact || empty($contact->mobile)) {
                continue;
            }

            $cacheKey = 'whatsapp_followup_sent_' . $followup->id . '_' . $today;

            if (!$hasSentAtColumn && Cache::has($cacheKey)) {
                continue;
            }

            $response = $whatsApp->sendMessage(
                $contact->mobile,
                "Hello {$contact->client_name}, following up regarding your inquiry."
            );

            $status = $response['status'] ?? false;
            $isSuccess = $status === true || (is_string($status) && strtolower($status) === 'success');

            if ($isSuccess) {
                if ($hasSentAtColumn) {
                    $followup->whatsapp_sent_at = now();
                    $followup->save();
                } else {
                    Cache::put($cacheKey, true, now()->addDays(2));
                }
            }
        }
    }
}

