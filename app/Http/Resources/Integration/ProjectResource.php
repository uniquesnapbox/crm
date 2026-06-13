<?php

namespace App\Http\Resources\Integration;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProjectResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'project_name' => $this->project_name,
            'project_summary' => $this->project_summary,
            'status' => $this->status,
            'completion_percent' => $this->completion_percent,
            'project_budget' => $this->project_budget,
            'hours_allocated' => $this->hours_allocated,
            'start_date' => optional($this->start_date)->toDateString(),
            'deadline' => optional($this->deadline)->toDateString(),
            'client' => $this->client ? [
                'id' => $this->client->id,
                'name' => $this->client->name,
                'email' => $this->client->email,
            ] : null,
            'project_members' => $this->members->map(fn($member) => [
                'id' => $member->user?->id,
                'name' => $member->user?->name,
                'email' => $member->user?->email,
            ])->filter(fn($member) => !empty($member['id']))->values(),
            'created_at' => optional($this->created_at)->toIso8601String(),
            'updated_at' => optional($this->updated_at)->toIso8601String(),
        ];
    }
}
