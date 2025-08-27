<?php

namespace App\Http\Controllers;

use App\Http\Resources\EventResource;
use App\Models\Event;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EventController extends Controller
{
    public function index(Request $rq)
    {
        $q = \App\Models\Event::query()
            ->where('status', 'published');

        if ($s = trim($rq->get('search', ''))) {
            $q->where(function($qq) use ($s) {
                $qq->where('title','ilike',"%{$s}%")
                   ->orWhere('description','like',"%{$s}%");
            });
        }
        if ($loc = trim($rq->get('location', ''))) {
            $q->where('location','ilike',"%{$loc}%");
        }
        if ($cat = trim($rq->get('category', ''))) {
            $q->where('category',$cat);
        }

        // rendezés (alapértelmezés: starts_at ASC)
        $field = in_array($rq->get('field'), ['starts_at','title','location','category']) ? $rq->get('field') : 'starts_at';
        $order = $rq->get('order') === 'desc' ? 'desc' : 'asc';
        $q->orderBy($field, $order);

        $perPage = (int)($rq->get('perPage', 12));
        $perPage = max(5, min($perPage, 50));

        return response()->json(
            $q->paginate($perPage)->appends($rq->query())
        );
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
