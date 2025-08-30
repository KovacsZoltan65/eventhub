<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EventResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray($request = null): array
    {
        
        $user = $request?->user();

        $canUpdate  = $user ? $user->can('update',  $this->resource) : false;
        $canDelete  = $user ? $user->can('delete',  $this->resource) : false;
        $canPublish = $user ? $user->can('publish', $this->resource) : false;
        $canBook    = (bool) ($user && !$user->is_blocked && $this->status === 'published' && $user->can('booking.create'));

        $result = [
            'id'              => $this->id,
            'title'           => $this->title,
            'description'     => $this->description,
            'starts_at'       => $this->iso($this->starts_at),
            'location'        => $this->location,
            'capacity'        => (int) $this->capacity,
            'remaining_seats' => $this->remaining_seats,
            'category'        => $this->category,
            'status'          => $this->status,
            'organizer'       => $this->whenLoaded('organizer', fn () => [
                'id' => $this->organizer->id, 'name' => $this->organizer->name,
            ]),
            'capabilities'    => [
                'can_update'  => $canUpdate,
                'can_delete'  => $canDelete,
                'can_publish' => $canPublish,
                'can_book'    => $canBook,
            ],
            'created_at'      => $this->iso($this->created_at),
            'updated_at'      => $this->iso($this->updated_at),
        ];
        
        return $result;
    }
    
    private function iso($value): ?string
    {
        // Biztosítsuk, hogy akár stringből is ISO8601 legyen, ne dobjon hibát
        if ($value instanceof \Carbon\CarbonInterface) {
            return $value->toISOString();
        }
        if (empty($value)) return null;
        try {
            return \Illuminate\Support\Carbon::parse($value)->toISOString();
        } catch (\Throwable $e) {
            return null;
        }
    }
}
