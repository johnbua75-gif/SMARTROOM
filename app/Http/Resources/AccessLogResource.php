<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AccessLogResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'access_card_id' => $this->access_card_id,
            'classroom_id' => $this->classroom_id,
            'user_id' => $this->user_id,
            'direction' => $this->direction,
            'result' => $this->result,
            'reason' => $this->reason,
            'accessed_at' => $this->accessed_at,
            'metadata' => $this->metadata,
            'card' => $this->whenLoaded('accessCard', fn () => [
                'id' => $this->accessCard?->id,
                'card_number' => $this->accessCard?->card_number,
                'rfid_uid' => $this->accessCard?->rfid_uid,
            ]),
            'classroom' => $this->whenLoaded('classroom', fn () => [
                'id' => $this->classroom?->id,
                'name' => $this->classroom?->name,
            ]),
            'user' => $this->whenLoaded('user', fn () => [
                'id' => $this->user?->id,
                'name' => $this->user?->name,
                'email' => $this->user?->email,
            ]),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
