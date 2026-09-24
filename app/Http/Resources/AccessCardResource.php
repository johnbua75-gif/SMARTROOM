<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AccessCardResource extends JsonResource
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
            'user_id' => $this->user_id,
            'classroom_id' => $this->classroom_id,
            'card_number' => $this->card_number,
            'rfid_uid' => $this->rfid_uid,
            'status' => $this->status,
            'expires_at' => $this->expires_at,
            'last_accessed_at' => $this->last_accessed_at,
            'access_count' => $this->access_count,
            'user' => $this->whenLoaded('user', fn () => [
                'id' => $this->user?->id,
                'name' => $this->user?->name,
                'email' => $this->user?->email,
                'department' => $this->user?->department,
            ]),
            'classroom' => $this->whenLoaded('classroom', fn () => [
                'id' => $this->classroom?->id,
                'name' => $this->classroom?->name,
                'building' => $this->classroom?->building,
            ]),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
