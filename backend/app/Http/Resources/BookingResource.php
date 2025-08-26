<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookingResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray($request = null): array
    {
        return [
            'id'            => $this->id,
            'event'         => $this->whenLoaded('event', fn() => [
                'id'        => $this->event->id,
                'title'     => $this->event->title,
                'starts_at' => optional($this->event->starts_at)->toISOString(),
                'location'  => $this->event->location,
            ]),
            'user'       => $this->whenLoaded('user', fn() => [
                'id'     => $this->user->id,
                'name'   => $this->user->name,
                'email'  => $this->user->email,
            ]),
            'quantity'   => (int) $this->quantity,
            'status'     => $this->status,
            'unit_price' => (int) $this->unit_price,
            'total'      => (int) $this->unit_price * (int) $this->quantity,
            'created_at' => optional($this->created_at)->toISOString(),
        ];
    }
}
