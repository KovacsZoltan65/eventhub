<?php

namespace App\Http\Controllers;

use App\Http\Resources\BookingResource;
use App\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\DB;

class AdminBookingController extends Controller
{
    /**
     * Foglalások listázása adminnak
     * Szűrési paraméterek:
     * - user_id: felhasználó ID
     * - event_id: esemény ID
     * - status: foglalás státusz (pending, confirmed, cancelled)
     * - date_from: a foglalások kezdete (dátum)
     * - date_to: a foglalások vége (dátum)
     * - per_page: egy oldalon megjelenített foglalások száma (1..100)
     * - page: az oldalszám
     * A foglalások időrendben, legújabbak elől jelennek meg.
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function getBookings(Request $request): AnonymousResourceCollection
    {
        $validated = $request->validate([
            'user_id'   => 'sometimes|integer|exists:users,id',
            'event_id'  => 'sometimes|integer|exists:events,id',
            'status'    => 'sometimes|in:pending,confirmed,cancelled',
            'date_from' => 'sometimes|date',
            'date_to'   => 'sometimes|date|after_or_equal:date_from',
            'per_page'  => 'sometimes|integer|min:1|max:100',
            'page'      => 'sometimes|integer|min:1',
        ]);

        $q = Booking::query()
            ->with(['user:id,name,email','event:id,title,starts_at,location'])
            ->latest('created_at');

        if (!empty($validated['user_id'])) {
            $q->where('user_id', (int)$validated['user_id']);
        }
        if (!empty($validated['event_id'])) {
            $q->where('event_id', (int)$validated['event_id']);
        }
        if (!empty($validated['status'])) {
            $q->where('status', $validated['status']);
        }
        if (!empty($validated['date_from'])) {
            $q->where('created_at', '>=', $validated['date_from']);
        }
        if (!empty($validated['date_to'])) {
            $q->where('created_at', '<=', $validated['date_to']);
        }

        return BookingResource::collection(
            $q->paginate((int)($validated['per_page'] ?? 20))
        );
    }
    
    public function cancel(Request $r, Booking $booking)
    {
        if ($booking->status === 'cancelled') {
            return response()->json(['message' => 'Booking already cancelled'], Response::HTTP_CONFLICT);
        }
        
        $booking->load('event:id,starts_at,title,location');
        
        if ($booking->event && $booking->event->starts_at && now()->greaterThanOrEqualTo($booking->event->starts_at)) {
            return response()->json(['message' => 'Event already started'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
        
        DB::transaction(function () use ($r, $booking) {
            $booking->update(['status' => 'cancelled']);
            
            activity()
                ->causedBy($r->user())
                ->performedOn($booking)
                ->withProperties(['event_id' => $booking->event_id, 'quantity' => $booking->quantity])
                ->event('admin.booking.cancel')
                ->log('Booking cancelled by admin');
            
            return new BookingResource($booking->refresh()->load('user:id,name,email','event:id,title,starts_at,location'));
        });
    }
}
