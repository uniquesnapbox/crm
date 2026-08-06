<?php

namespace App\Http\Resources\Integration;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClientResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $details = $this->clientDetails;
        $lead = $this->convertedLead;

        $value = static function (mixed $primary, mixed $fallback = null): mixed {
            return $primary !== null && $primary !== '' ? $primary : $fallback;
        };

        return [
            'id' => $this->id,
            'name' => $value($this->name, $lead?->client_name),
            'email' => $value($this->email, $lead?->client_email),
            'mobile' => $value($this->mobile, $lead?->mobile),
            'status' => $this->status,
            'company_name' => $value($details?->company_name, $lead?->company_name),
            'website' => $value($details?->website, $lead?->website),
            'city' => $details?->city,
            'state' => $details?->state,
            'country' => $value($this->country?->name, $lead?->country),
            'address' => $value($details?->address, $lead?->address),
            'shipping_address' => $details?->shipping_address,
            'postal_code' => $details?->postal_code,
            'office' => $value($details?->office, $lead?->office),
            'cell' => $value($details?->cell, $lead?->cell),
            'note' => $value($details?->note, $lead?->note),
            'client_type' => $details?->client_type,
            'lead_source_id' => $value($details?->lead_source_id, $lead?->source_id),
            'lead_category_id' => $value($details?->lead_category_id, $lead?->category_id),
            'lead_status_id' => $value($details?->lead_status_id, $lead?->status_id),
            'lead_interest_level' => $value(
                $details?->lead_interest_level,
                $lead?->interest_level
            ),
            'lead_deal_size' => $value($details?->lead_deal_size, $lead?->deal_size),
            'lead_contact_status' => $value(
                $details?->lead_contact_status,
                $lead?->contact_status
            ),
            'lead_contact_status_reason' =>
                $value($details?->lead_contact_status_reason, $lead?->contact_status_reason),
            'products_services' => $value(
                $details?->products_services,
                $lead?->products_services
            ),
            'last_contact_date' => $details?->last_contact_date,
            'next_followup_date' => $details?->next_followup_date,
            'skype' => $details?->skype,
            'facebook' => $details?->facebook,
            'twitter' => $details?->twitter,
            'linkedin' => $details?->linkedin,
            'tax_name' => $details?->tax_name,
            'gst_number' => $details?->gst_number,
            'electronic_address' => $details?->electronic_address,
            'electronic_address_scheme' => $details?->electronic_address_scheme,
            'company_logo' => $details?->company_logo,
            'added_by' => $value($details?->addedBy?->name, $lead?->addedBy?->name),
            'profile_image' => $this->image_url,
        ];
    }
}
