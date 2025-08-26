<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

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
     * Esemény részletei adminnak
     */
    public function show(Event $event)
    {
        $event->load('organizer:id,name')
              ->loadSum(['bookings as confirmed_quantity' => fn($q)=>$q->where('status','confirmed')],'quantity');

        return new EventResource($event);
    }
    
    public function cancel(\Illuminate\Http\Request $r, \App\Models\Event $event)
    {
        if ($event->status === 'cancelled') {
            return response()->json(['message' => 'Event already cancelled'], 409);
        }
        
        if ($event->starts_at && now()->greaterThanOrEqualTo($event->starts_at)) {
            return response()->json(['message' => 'Event already started'], 422);
        }
        
        $event->update(['status' => 'cancelled']);
        
        activity()
            ->causedBy($r->user())
            ->performedOn($event)
            ->event('admin.event.cancel')
            ->withProperties(['event_id' => $event->id])
            ->log('Event cancelled by admin');
        
        $event->load('organizer:id,name')
          ->loadSum(['bookings as confirmed_quantity' => fn($q)=>$q->where('status','confirmed')],'quantity');

        return new \App\Http\Resources\EventResource($event);
    }
}
