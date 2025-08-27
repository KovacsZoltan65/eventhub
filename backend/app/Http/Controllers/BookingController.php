<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBookingRequest;
use App\Http\Resources\BookingResource;
use App\Models\Booking;
use App\Models\Event;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;
use function activity;
use function auth;
use function config;
use function response;
use function Symfony\Component\Clock\now;

class BookingController extends Controller
{
    public function indexMine(Request $request)
    {
        $validated = $request->validate([
            'status'   => 'sometimes|nullable|in:pending,confirmed,cancelled',
            'per_page' => 'sometimes|integer|min:1|max:100',
            'page'     => 'sometimes|integer|min:1',
            'order'    => 'sometimes|in:asc,desc',
            'field'    => 'sometimes|in:created_at,starts_at',
        ]);
        
        $user = $request->user();
        
        $q = Booking::query()
            ->where('user_id', $user->id)
            ->with(['event:id,title,starts_at,location']);
        
        if( !empty($validated['status']) ) {
            $q->where('status', $validated['status']);
        }
        
        $field = $validated['field'] ?? 'created_at';
        $order = $validated['order'] ?? 'asc';
        
        // ha starts_at-ra rendezünk, join nélkül megtehetjük a kapcsolaton keresztül is:
        if ($field === 'starts_at') {
            $q->join('events', 'events.id', '=', 'bookings.event_id')
                ->orderBy('events.starts_at', $order)
                ->select('bookings.*');
        } else {
            $q->orderBy($field, $order);
        }
        
        $perPage = (int) ($validated['per_page'] ?? 10);
        
        return BookingResource::collection(
            $q->paginate($perPage)
        );
    }
    
    // Foglalás rögzítése
    public function store(StoreBookingRequest $request)
    {
        $userId = auth()->id();

        $eventId = $request->event_id;
        
        $event = Event::find($eventId);
        
        // csak published esemény foglalható
        if ($event->status !== 'published') {
            return response()->json(['message' => 'Event is not bookable.'], 422);
        }
        
        $user = $request->user();
        $reqQty = (int)$request->integer('quantity');
        $maxPerUser = (int) config('booking.max_per_user_per_event', 5);
        
        $result = DB::transaction(function() use($user, $eventId, $reqQty, $maxPerUser) {
            // Sorzár a versenyhelyzet ellen
            $eventRow = DB::table('events')
                ->where('id', $eventId)
                ->lockForUpdate()
                ->first();
            
            // Eddig megerősített mennyiség ettől a usertől erre az eseményre
            $already = (int) Booking::where('user_id', $user->id)
                ->where('event_id', $eventId)
                ->where('status', 'confirmed')
                ->sum('quantity');
            
            if ($already + $reqQty > $maxPerUser) {
                abort(Response::HTTP_UNPROCESSABLE_ENTITY, "Limit exceeded (max {$maxPerUser} tickets per user for this event).");
            }
            
            // Maradék ülőhely a confirmed foglalások alapján
            $confirmedSum = (int) Booking::where('event_id', $eventId)
                ->where('status', 'confirmed')
                ->sum('quantity');
            
            $remaining = (int)$eventRow->capacity - $confirmedSum;
            if ($remaining < $reqQty) {
                abort(Response::HTTP_UNPROCESSABLE_ENTITY, 'Not enough seats left.');
            }
            
            $booking = Booking::create([
                'user_id'    => $user->id,
                'event_id'   => $eventId,
                'quantity'   => $reqQty,
                'status'     => 'confirmed', // jelenleg azonnal megerősítjük
                'unit_price' => 0,
            ]);
            
            activity()
                ->causedBy($user)
                ->performedOn($booking)
                ->withProperties([
                    'event_id' => $eventId,
                    'quantity' => $reqQty,
                ])
                ->event('booking.create')
                ->log('Booking created');
            
            $booking->load(['event:id,title,starts_at,location']);
            
            $result = [
                'bookingId'  => $booking->id,
                'quantity'   => (int) $booking->quantity,
                'totalPrice' => (int) $booking->quantity * (int) $booking->unit_price,
                
                'timestamp' => $booking->created_at,
                /*
                'timestamp' => $booking->created_at instanceof \DateTimeInterface
                                ? $booking->created_at->format(\DateTimeInterface::ATOM) // ISO-8601
                                : now()->toAtomString(),
                */
                'event'      => [
                    'id'        => $booking->event->id,
                    'title'     => $booking->event->title,
                    'starts_at' => $booking->event->starts_at?->toISOString(),
                    'location'  => $booking->event->location,
                ],
            ];
            
            return response()->json($result, Response::HTTP_CREATED);
        });
        
        return $result;
    }
    
    // Foglalás lemondása
    public function cancel(Request $r, Booking $booking)
    {
        $user = $r->user();
        
        // Csak a saját foglalását mondhatja le
        if ($booking->user_id !== $user->id) {
            return response()->json(['message' => 'Forbidden.'], Http::HTTP_FORBIDDEN);
        }
        
        // Betöltjük az eseményt a további ellenőrzésekhez és válaszhoz
        $booking->load(['event:id,title,starts_at,location']);
        
        // Már törölt?
        if ($booking->status === 'cancelled') {
            return response()->json(['message' => 'Booking already cancelled.'], Http::HTTP_UNPROCESSABLE_ENTITY);
        }
        
        // Csak jövőbeli eseményt lehessen lemondani
        $startsAt = $booking->event?->starts_at instanceof \Illuminate\Support\Carbon
            ? $booking->event->starts_at
            : Carbon::parse($booking->event?->starts_at);
        
        if ($startsAt && $startsAt->isPast()) {
            return response()->json(['message' => 'Event is in the past; cannot cancel.'], Http::HTTP_UNPROCESSABLE_ENTITY);
        }
        
        // Állapot frissítése
        $booking->update(['status' => 'cancelled']);
        
        activity()
            ->causedBy($user)
            ->performedOn($booking)
            ->withProperties(['event_id' => $booking->event_id, 'prev_status' => 'confirmed'])
            ->event('booking.cancel')
            ->log('Booking cancelled');
        
        // Friss objektum vissza (event mezőkkel)
        return response()->json((new BookingResource($booking))->resolve(), Response::HTTP_OK);
    }
}
