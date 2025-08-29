<?php

namespace App\Http\Controllers;

use App\Http\Resources\EventResource;
use App\Models\Event;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EventController extends Controller
{
    /**
     * Publikus események oldalszámozott listája, amelyek cím, leírás, helyszín és kategória szerint kereshetők.
     *
     * @queryParam search string Keresési karakterlánc. Példa: "K"
     * @queryParam location string Helyszín. Példa: "Budapest"
     * @queryParam category string Esemény kategória. Példa: "konferencia"
     * @queryParam field string Rendezési mező. Példa: "starts_at"
     * @queryParam order string Rendezési sorrend. Példa: "desc"
     * @queryParam perPage int Elemek száma oldalonként. Példa: 12
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $rq): JsonResponse
    {
        $q = Event::query()
            ->where('status', 'published')
            ->withRemainingSeats();

        // keresés: cím, leírás, helyszín
        if ($s = trim($rq->get('search', ''))) {
            $q->where(function($qq) use ($s) {
                $qq->where('title','ilike',"%{$s}%")
                   ->orWhere('description','like',"%{$s}%");
            });
        }

        // keresés: helyszín
        if ($loc = trim($rq->get('location', ''))) {
            $q->where('location','ilike',"%{$loc}%");
        }

        // keresés: kategória
        if ($cat = trim($rq->get('category', ''))) {
            $q->where('category',$cat);
        }

        // rendezés (alapértelmezés: starts_at ASC)
        $field = in_array($rq->get('field'), ['starts_at','title','location','category']) ? $rq->get('field') : 'starts_at';
        $order = $rq->get('order') === 'desc' ? 'desc' : 'asc';
        $q->orderBy($field, $order);

        // oldalanként
        $perPage = (int)($rq->get('perPage', 12));
        $perPage = max(5, min($perPage, 50));

        return response()->json($q->paginate($perPage));
    }

    /**
     * Egy publikus esemény részletei (csak published).
     *
     * @urlParam int $event Az esemény ID-je.
     *
     * @return \App\Http\Resources\EventResource
     */
    public function event(Event $event): EventResource
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
}
