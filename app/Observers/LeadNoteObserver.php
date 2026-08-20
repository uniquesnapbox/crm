<?php

namespace App\Observers;

use App\Models\LeadHistory;
use App\Models\LeadNote;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class LeadNoteObserver
{

    /**
     * @param LeadNote $leadNote
     */
    public function saving(LeadNote $leadNote)
    {
        if (!isRunningInConsoleOrSeeding()) {
            if (user()) {
                $leadNote->last_updated_by = user()->id;
            }
        }
    }

    public function creating(LeadNote $leadNote)
    {
        if (!isRunningInConsoleOrSeeding()) {
            if (user()) {
                $leadNote->added_by = user()->id;
            }
        }
    }

    public function created(LeadNote $leadNote): void
    {
        $this->pushHistory($leadNote, 'note_created', 'Note Added', $this->noteSummary($leadNote));
    }

    public function updated(LeadNote $leadNote): void
    {
        $this->pushHistory($leadNote, 'note_updated', 'Note Updated', $this->noteSummary($leadNote));
    }

    public function deleted(LeadNote $leadNote): void
    {
        $this->pushHistory($leadNote, 'note_deleted', 'Note Deleted', $leadNote->title ?: 'A note was deleted.');
    }

    private function pushHistory(LeadNote $leadNote, string $eventType, string $title, string $description): void
    {
        if (isRunningInConsoleOrSeeding() || !Schema::hasTable('lead_histories') || empty($leadNote->lead_id)) {
            return;
        }

        try {
            LeadHistory::create([
                'company_id' => company()?->id,
                'lead_id' => $leadNote->lead_id,
                'event_type' => $eventType,
                'title' => $title,
                'description' => $description,
                'created_by' => user()?->id ?? $leadNote->last_updated_by ?? $leadNote->added_by,
                'event_at' => now(),
                'meta' => [
                    'note_id' => $leadNote->id,
                ],
            ]);
        } catch (\Throwable $exception) {
            Log::warning('Lead note history could not be saved.', [
                'lead_note_id' => $leadNote->id,
                'lead_id' => $leadNote->lead_id,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    private function noteSummary(LeadNote $leadNote): string
    {
        $title = trim((string) $leadNote->title);
        if ($title !== '') {
            return $title;
        }

        return Str::limit(trim(strip_tags((string) $leadNote->details)), 120, '...') ?: 'Lead note updated.';
    }

}
