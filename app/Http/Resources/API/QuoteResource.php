<?php

namespace App\Http\Resources\API;

use Illuminate\Http\Resources\Json\JsonResource;

class QuoteResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'                => $this->id,
            'booking_id'        => $this->booking_id,
            'provider_id'       => $this->provider_id,
            'provider_name'     => optional($this->provider)->display_name,
            'handyman_id'       => $this->handyman_id,
            'handyman_name'     => optional($this->handyman)->display_name,
            'price'             => (float) $this->price,
            'estimated_duration'=> $this->estimated_duration,
            'notes'             => $this->notes,
            'inspection_notes'  => $this->inspection_notes,
            'status'            => $this->status,
            'approved_at'       => $this->approved_at,
            'rejected_at'       => $this->rejected_at,
            'rejection_reason'  => $this->rejection_reason,
            'created_at'        => $this->created_at,
            'updated_at'        => $this->updated_at,
        ];
    }
}
