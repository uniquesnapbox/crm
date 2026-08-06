<?php

namespace App\Observers;

use App\Events\LeadEvent;
use App\Models\LeadCategory;
use App\Models\LeadHistory;
use App\Models\LeadSource;
use App\Models\LeadStatus;
use App\Models\Lead;
use App\Models\Notification;
use App\Models\UniversalSearch;
use App\Models\User;
use App\Services\LeadWhatsAppNotificationService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class LeadObserver
{

    public function saving(Lead $lead)
    {
        if (!isRunningInConsoleOrSeeding()) {
            $userID = (!is_null(user())) ? user()->id : null;
            $lead->last_updated_by = $userID;
        }

    }

    public function creating(Lead $leadContact)
    {
        $leadContact->hash = md5(microtime());

        if (!isRunningInConsoleOrSeeding()) {
            if (request()->has('added_by')) {
                $leadContact->added_by = request('added_by');

            }
            else {
                $userID = (!is_null(user())) ? user()->id : null;
                $leadContact->added_by = $userID;
            }
        }

        if (company()) {
            $leadContact->company_id = company()->id;
        }
    }

    public function created(Lead $leadContact)
    {
        if (!isRunningInConsoleOrSeeding()) {
            $this->logLeadCreated($leadContact);
            try {
                $leadWhatsAppService = app(LeadWhatsAppNotificationService::class);
                $leadWhatsAppService->sendLeadCreatedMessage($leadContact);

                if (filled((string) $leadContact->products_services)) {
                    $leadWhatsAppService->sendLeadProductInterestMessage($leadContact);
                }
            } catch (\Throwable $exception) {
                Log::warning('Lead WhatsApp notification failed after lead creation.', [
                    'lead_id' => $leadContact->id,
                    'company_id' => $leadContact->company_id,
                    'error' => $exception->getMessage(),
                ]);
            }

            try {
                event(new LeadEvent($leadContact, 'NewLeadCreated'));
            } catch (\Throwable $exception) {
                Log::warning('Lead email notification failed after lead creation.', [
                    'lead_id' => $leadContact->id,
                    'company_id' => $leadContact->company_id,
                    'error' => $exception->getMessage(),
                ]);
            }
        }
    }

    public function updated(Lead $lead): void
    {
        $this->logLeadFieldChanges($lead);

        if (!isRunningInConsoleOrSeeding() && $lead->wasChanged('products_services') && filled((string) $lead->products_services)) {
            app(LeadWhatsAppNotificationService::class)->sendLeadProductInterestMessage($lead);
        }
    }

    public function deleting(Lead $leadContact)
    {
        $notifyData = ['App\Notifications\LeadAgentAssigned', 'App\Notifications\NewDealCreated'];
        Notification::deleteNotification($notifyData, $leadContact->id);
    }

    public function deleted(Lead $leadContact)
    {
        UniversalSearch::where('searchable_id', $leadContact->id)->where('module_type', 'lead')->delete();
    }

    private function shouldLogHistory(): bool
    {
        return !isRunningInConsoleOrSeeding() && Schema::hasTable('lead_histories');
    }

    private function logLeadCreated(Lead $lead): void
    {
        if (!$this->shouldLogHistory()) {
            return;
        }

        $createdBy = $lead->added_by ?: (user()->id ?? null);

        LeadHistory::create([
            'company_id' => $lead->company_id ?: (company()->id ?? null),
            'lead_id' => $lead->id,
            'event_type' => 'lead_created',
            'title' => 'Lead Created',
            'description' => sprintf('%s was added as a lead.', $lead->client_name ?: 'Lead'),
            'created_by' => $createdBy,
            'event_at' => $lead->created_at ?: now(),
            'meta' => [
                'actor_name' => optional($lead->addedBy)->name,
            ],
        ]);
    }

    private function logLeadFieldChanges(Lead $lead): void
    {
        if (!$this->shouldLogHistory()) {
            return;
        }

        $changes = $lead->getChanges();
        unset($changes['updated_at'], $changes['last_updated_by'], $changes['hash']);

        if (empty($changes)) {
            return;
        }

        $trackedFields = [
            'client_name',
            'client_email',
            'source_id',
            'category_id',
            'status_id',
            'interest_level',
            'deal_size',
            'contact_status',
            'company_name',
            'website',
            'mobile',
            'office',
            'country',
            'address',
            'assigned_to',
            'next_follow_up',
            'contact_status_reason',
            'products_services',
        ];

        foreach ($changes as $field => $newValue) {
            if (!in_array($field, $trackedFields, true)) {
                continue;
            }

            $oldValue = $lead->getOriginal($field);
            if ((string) $oldValue === (string) $newValue) {
                continue;
            }

            $oldText = $this->formatFieldValue($field, $oldValue);
            $newText = $this->formatFieldValue($field, $newValue);
            $fieldLabel = $this->fieldLabel($field);

            LeadHistory::create([
                'company_id' => $lead->company_id ?: (company()->id ?? null),
                'lead_id' => $lead->id,
                'event_type' => 'lead_field_updated',
                'title' => 'Lead Updated',
                'description' => sprintf('%s changed from "%s" to "%s".', $fieldLabel, $oldText, $newText),
                'field_key' => $field,
                'old_value' => $oldText,
                'new_value' => $newText,
                'created_by' => $lead->last_updated_by ?: (user()->id ?? null),
                'event_at' => now(),
                'meta' => [
                    'field' => $field,
                    'old' => $oldText,
                    'new' => $newText,
                ],
            ]);
        }
    }

    private function fieldLabel(string $field): string
    {
        $labels = [
            'client_name' => 'Name',
            'client_email' => 'Email',
            'source_id' => 'Source',
            'category_id' => 'Category',
            'status_id' => 'Lead Status',
            'interest_level' => 'Interest Level',
            'deal_size' => 'Deal Size',
            'contact_status' => 'Contact Status',
            'company_name' => 'Company Name',
            'website' => 'Website',
            'mobile' => 'Mobile',
            'office' => 'Office Phone Number',
            'country' => 'Country',
            'address' => 'Address',
            'assigned_to' => 'Assigned To',
            'next_follow_up' => 'Next Follow-up',
            'contact_status_reason' => 'Contact Status Reason',
            'products_services' => 'Products/Services',
        ];

        return $labels[$field] ?? ucwords(str_replace('_', ' ', $field));
    }

    private function formatFieldValue(string $field, $value): string
    {
        if (is_null($value) || $value === '') {
            return '--';
        }

        if ($field === 'source_id') {
            return LeadSource::whereKey((int) $value)->value('type') ?: '--';
        }

        if ($field === 'category_id') {
            return LeadCategory::whereKey((int) $value)->value('category_name') ?: '--';
        }

        if ($field === 'status_id') {
            return LeadStatus::whereKey((int) $value)->value('type') ?: '--';
        }

        if ($field === 'assigned_to') {
            return User::withoutGlobalScopes()->whereKey((int) $value)->value('name') ?: '--';
        }

        if ($field === 'interest_level') {
            return ucwords(str_replace('_', ' ', (string) $value));
        }

        if ($field === 'contact_status') {
            return ucwords(str_replace('_', ' ', (string) $value));
        }

        return (string) $value;
    }

}
