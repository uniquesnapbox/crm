<?php

namespace App\Http\Resources\Integration;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClientResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'mobile' => $this->mobile,
            'status' => $this->status,
            'company_name' => $this->clientDetails?->company_name,
            'website' => $this->clientDetails?->website,
            'city' => $this->clientDetails?->city,
            'state' => $this->clientDetails?->state,
            'profile_image' => $this->image_url,
        ];
    }
}
