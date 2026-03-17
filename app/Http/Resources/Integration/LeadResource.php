<?php

namespace App\Http\Resources\Integration;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LeadResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'client_name' => $this->client_name,
            'client_email' => $this->client_email,
            'company_name' => $this->company_name,
            'mobile' => $this->mobile,
            'website' => $this->website,
            'address' => $this->address,
            'country' => $this->country,
            'status_id' => $this->status_id,
            'source' => $this->leadSource?->type,
            'category' => $this->category?->category_name,
            'added_by' => $this->addedBy ? [
                'id' => $this->addedBy->id,
                'name' => $this->addedBy->name,
                'email' => $this->addedBy->email,
            ] : null,
            'assigned_to' => $this->assignedTo ? [
                'id' => $this->assignedTo->id,
                'name' => $this->assignedTo->name,
                'email' => $this->assignedTo->email,
            ] : null,
            'is_converted' => $this->is_converted,
            'created_at' => optional($this->created_at)->toIso8601String(),
            'updated_at' => optional($this->updated_at)->toIso8601String(),
            'latest_follow_up' => $this->latestFollowUp ? [
                'remark' => $this->latestFollowUp->remark,
                'next_follow_up_date' => optional($this->latestFollowUp->next_follow_up_date)->toIso8601String(),
                'status' => $this->latestFollowUp->status,
                'latitude' => $this->latestFollowUp->latitude,
                'longitude' => $this->latestFollowUp->longitude,
            ] : null,
        ];
    }
}
