<?php

namespace App\Http\Controllers;

use App\Http\Resources\EventResource;
use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminEventController extends Controller
{
    /**
     * Események listája adminnak (bármely státusz, bármely szervező)
     * Szűrés: search, organizer_id, status, category, date_from, date_to, location
     */
    public function index(Request $request)
    {
        $v = $request->validate([
            'search'       => 'sometimes|string|max:255',
            'organizer_id' => 'sometimes|integer|exists:users,id',
            'status'       => 'sometimes|in:draft,published,cancelled',
            'category'     => 'sometimes|string|max:100',
            'location'     => 'sometimes|string|max:255',
            'date_from'    => 'sometimes|date',
            'date_to'      => 'sometimes|date|after_or_equal:date_from',
            'field'        => 'sometimes|in:starts_at,created_at,title,location,status',
            'order'        => 'sometimes|in:asc,desc',
            'per_page'     => 'sometimes|integer|min:1|max:100',
            'page'         => 'sometimes|integer|min:1',
        ]);

        $q = Event::query()
            ->with('organizer:id,name')
            ->withSum(['bookings as confirmed_quantity' => function ($q) {
                $q->where('status', 'confirmed');
            }], 'quantity');

        if (!empty($v['search'])) {
            $s = $v['search'];
            $q->where(fn($w)=>$w
                ->where('title','like',"%{$s}%")
                ->orWhere('description','like',"%{$s}%"));
        }
        if (!empty($v['organizer_id'])) $q->where('organizer_id', (int)$v['organizer_id']);
        if (!empty($v['status']))       $q->where('status', $v['status']);
        if (!empty($v['category']))     $q->where('category', $v['category']);
        if (!empty($v['location']))     $q->where('location', 'like', "%{$v['location']}%");
        if (!empty($v['date_from']))    $q->where('starts_at', '>=', $v['date_from']);
        if (!empty($v['date_to']))      $q->where('starts_at', '<=', $v['date_to']);

        $field = $v['field'] ?? 'starts_at';
        $order = $v['order'] ?? 'desc';
        $q->orderBy($field, $order);

        return EventResource::collection(
            $q->paginate((int)($v['per_page'] ?? 20))
        );
    }

    /**
     * Esemény részletei adminisztrátorként
     *
     * @param Event $event
     * @return EventResource
     */
    public function show(Event $event): EventResource
    {
        // Az esemény részleteiért felelős metódus.
        // Visszatér egy EventResource objektumot, amely tartalmazza
        // az esemény adatait, és a kapcsolódó szervező adatait is.
        $event->load('organizer:id,name')
              ->loadSum(['bookings as confirmed_quantity' => fn($q)=>$q->where('status','confirmed')],'quantity');

        return new EventResource($event);
    }

    /**
     * Esemény lemondása adminisztrátorként
     *
     * A lemondás nem lehetséges, ha az esemény már lemondva van.
     * A lemondás nem lehetséges, ha az esemény már elkezdődött.
     *
     * @param Request $r
     * @param Event $event
     */
    public function cancel(Request $r, Event $event)
    {
        // Ellenőrizzük, hogy az esemény nem lett-e már lemondva
        if ($event->status === 'cancelled') {
            return response()->json(['message' => 'Event already cancelled'], 409);
        }

        // Ellenőrizzük, hogy az esemény nem kezdődött-e már el
        if ($event->starts_at && now()->greaterThanOrEqualTo($event->starts_at)) {
            return response()->json(['message' => 'Event already started'], 422);
        }

        // Frissítsük az eseményt
        $event->update(['status' => 'cancelled']);

        // Naplózzuk a lemondást
        activity()
            ->causedBy($r->user())
            ->performedOn($event)
            ->event('admin.event.cancel')
            ->withProperties(['event_id' => $event->id])
            ->log('Event cancelled by admin');

        // Visszaküldjük a lemondott eseményt
        $event->load('organizer:id,name')
          ->loadSum(['bookings as confirmed_quantity' => fn($q)=>$q->where('status','confirmed')],'quantity');

        return new EventResource($event);
    }
}
