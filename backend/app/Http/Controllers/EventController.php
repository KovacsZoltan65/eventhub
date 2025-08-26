<?php

namespace App\Http\Controllers;

use App\Http\Resources\EventResource;
use App\Models\Event;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EventController extends Controller
{
    /**
     * Publikus eseménylista (csak published).
     * Szűrés: search (title/description), location (like), category (eq)
     * Rendezés: field (starts_at|title|location), order (asc|desc) – default: starts_at asc
     * Pagináció: per_page (1..100), page
     */
    public function events(Request $request)
    {
        $validated = $request->validate([
            'search'   => 'sometimes|nullable|string|max:255',
            'location' => 'sometimes|nullable|string|max:255',
            'category' => 'sometimes|nullable|string|max:100',
            'field'    => 'sometimes|in:starts_at,title,location',
            'order'    => 'sometimes|in:asc,desc',
            'per_page' => 'sometimes|integer|min:1|max:100',
            'page'     => 'sometimes|integer|min:1',
        ]);
        
        $q = Event::query()
            ->where('status', 'published')
            ->with('organizer:id,name')
            ->withSum(['bookings as confirmed_quantity' => function ($q) {
                $q->where('status', 'confirmed');
            }], 'quantity');

        if (!empty($validated['search'])) {
            $s = $validated['search'];
            $q->where(function ($w) use ($s) {
                $w->where('title', 'like', "%{$s}%")
                    ->orWhere('description', 'like', "%{$s}%");
            });
        }
        
        if (!empty($validated['location'])) {
            $q->where('location', 'like', "%{$validated['location']}%");
        }

        if (!empty($validated['category'])) {
            $q->where('category', $validated['category']);
        }
        
        $field = $validated['field'] ?? 'starts_at';
        $order = $validated['order'] ?? 'asc';
        $q->orderBy($field, $order);
        
        $perPage = (int)($validated['per_page'] ?? 10);
        
        $result = EventResource::collection(
            $q->paginate($perPage)
        );

        return $result;
    }
    
    /**
     * Publikus esemény részletei (csak published).
     */
    
    public function event(Event $event)
    {
        abort_unless(
            $event->status === 'published', 
            Response::HTTP_NOT_FOUND, 
            'Event not published'
        );

        $event->load('organizer:id,name')
              ->loadSum(['bookings as confirmed_quantity' => fn ($q) => $q->where('status','confirmed')], 'quantity');

        return new EventResource($event);
    }
    
    
    /*
    public function event($id)
    {
        $event = Event::find($id);
        
        // Csak published esemény megtekinthető publikus végponton.
        abort_if(
            $event['status'] !== 'published', 
            Response::HTTP_NOT_FOUND, 
            'Event not published'
        );
        
        $event->load('organizer:id,name')
            ->loadSum(['bookings as confirmed_quantity' => fn ($q) => $q->where('status','confirmed')], 'quantity');
        
        return new EventResource($event);
    }
    */
}
