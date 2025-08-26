<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEventRequest;
use App\Http\Requests\UpdateEventRequest;
use App\Models\Event;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class OrganizerEventController extends Controller
{
    public function show(Request $r, Event $event)
    {
        $this->authorize('view', $event);

        $event->load('organizer:id,name')
              ->loadSum(['bookings as confirmed_quantity' => fn($q)=>$q->where('status','confirmed')],'quantity');

        return new EventResource($event);
    }
    
    public function getEvents(Request $r)
    {
        // Csak a saját események listája (admin mindent lát)
        $q = Event::query()
            ->when(!$r->user()->hasRole('admin'), fn($q)=>$q->where('organizer_id', $r->user()->id))
            ->withSum(['bookings as confirmed_quantity' => function($q){
                $q->where('status','confirmed');
            }], 'quantity');
            
        if ($s = $r->string('search')->toString()) {
            $q->where(fn($w)=>$w->where('title','like',"%$s%")->orWhere('description','like',"%$s%"));
        }
        
        if ($cat = $r->string('category')->toString()) { $q->where('category',$cat); }
        if ($st = $r->string('status')->toString())   { $q->where('status',$st); }
        
        $q->orderBy('starts_at','desc');
        
        // Policy: listanézet engedélye
        $this->authorize('viewAny', Event::class);
        
        return $q->paginate($r->integer('per_page', 10));
    }
    
    public function store(StoreEventRequest $r)
    {
        $this->authorize('create', Event::class);
        
        $data = $r->validated();
        $data['organizer_id'] = $r->user()->id;
        $data['status'] = $data['status'] ?? 'draft';
        
        $event = Event::create($data);
        
        return response()->json($event->loadSum(
            ['bookings as confirmed_quantity' => fn($q)=>$q->where('status','confirmed')], 'quantity'
        ), Response::HTTP_CREATED);
    }
    
    public function update(UpdateEventRequest $r, Event $event)
    {
        // authorize() megtörtént a Request-ben
        $event->update($r->validated());
        
        return $event->fresh()->loadSum(
            ['bookings as confirmed_quantity' => fn($q)=>$q->where('status','confirmed')], 'quantity'
        );
    }
    
    public function destroy(Request $r, Event $event)
    {
        $this->authorize('delete', $event);

        // Opcionális: published esemény ne legyen törölhető, csak cancelled?
        $event->delete();

        return response()->json(['ok'=>true]);
    }
    
    public function publish(Request $r, Event $event)
    {
        $this->authorize('publish', $event);

        // csak draft → published (üzleti szabály)
        if ($event->status !== 'draft') {
            return response()->json(['message'=>'Only draft events can be published.'], 422);
        }

        $event->update(['status' => 'published']);

        return response()->json(['ok'=>true, 'status'=>$event->status]);
    }
    
    // Lemondás
    public function cancel(Request $r, Event $event)
    {
        $this->authorize('cancel', $event);
        
        if ($event->status === 'cancelled') {
            return response()->json(['message' => 'Event already cancelled'], 409);
        }
        
        // Opcionális üzleti szabály: múltbeli eseményt ne lehessen lemondani
        if ($event->starts_at && now()->greaterThanOrEqualTo($event->starts_at)) {
            return response()->json(['message' => 'Event already started'], 422);
        }
        
        $event->update(['status' => 'cancelled']);
        
        activity()
            ->causedBy($r->user())
            ->performedOn($event)
            ->event('event.cancel')
            ->withProperties(['event_id' => $event->id])
            ->log('Event cancelled');
        
        $event->load('organizer:id,name')
          ->loadSum(['bookings as confirmed_quantity' => fn($q)=>$q->where('status','confirmed')],'quantity');

        return new EventResource($event);
    }
}
