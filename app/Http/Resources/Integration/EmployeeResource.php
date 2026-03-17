<?php

namespace App\Http\Resources\Integration;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmployeeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $role = $this->roles->first();

        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'role' => $role?->display_name ?: $role?->name ?: 'Employee',
            'status' => $this->status,
            'profile_image' => $this->image_url,
        ];
    }
}