<?php

namespace App\Http\Resources\Integration;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AttendanceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'date' => optional($this->date)->toDateString(),
            'status' => $this->status,
            'clock_in_time' => optional($this->clock_in_time)->toIso8601String(),
            'clock_out_time' => optional($this->clock_out_time)->toIso8601String(),
            'working_from' => $this->working_from,
            'work_from_type' => $this->work_from_type,
            'late' => $this->late,
            'half_day' => $this->half_day,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'employee' => $this->user ? [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'email' => $this->user->email,
                'status' => $this->user->status,
                'profile_image' => $this->user->image_url,
            ] : null,
            'location' => $this->location?->location,
            'shift' => $this->shift?->shift_name,
            'created_at' => optional($this->created_at)->toIso8601String(),
            'updated_at' => optional($this->updated_at)->toIso8601String(),
        ];
    }
}
