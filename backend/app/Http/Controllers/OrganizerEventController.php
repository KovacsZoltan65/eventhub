<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEventRequest;
use App\Http\Requests\UpdateEventRequest;
use App\Http\Resources\EventResource;
use App\Models\Event;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Symfony\Component\HttpFoundation\Response;

class OrganizerEventController extends Controller
{
    use AuthorizesRequests,
        ValidatesRequests;

    /**
     * Jelenítse meg a megadott erőforrást.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Event  $event
     * @return \Illuminate\Http\Response
     */
    //
    public function show(Request $r, Event $event): EventResource
    {
        // Az esemény nézhetőségének engedélyezése:
        // - Mindenki látja a publikus eseményeket
        // - Az admin látja minden eseményt
        // - Az organizer látja a saját eseményeit
        $this->authorize('view', $event);

        // Esemény részletei: a szervező adatait, és a kapcsolódó
        // foglalások számát is feltöltjük.
        // A confirmed_quantity: a foglalások száma, amelyek státusza
        // 'confirmed' (vagyis a fizetés sikeres volt).
        $event->load('organizer:id,name')
              ->loadSum(['bookings as confirmed_quantity' => function($q){
                    $q->where('status','confirmed');
                }],'quantity');

        return new EventResource($event);
    }

    /**
     * Visszaadja a szervező eseményeinek listáját.
     *
     * Csak a saját események listája (admin mindent lát).
     * A listában a foglalások száma is feltöltődik.
     * A confirmed_quantity: a foglalások száma, amelyek státusza
     * 'confirmed' (vagyis a fizetés sikeres volt).
     * A listázás alapértelmezetten starts_at DESC szerint történik.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Pagination\LengthAwarePaginator
     *
     * @queryParam search string Keresési sztring. Példa: "K"
     * @queryParam category string Esemény kategória. Példa: "konferencia"
     * @queryParam status string Esemény státusz. Példa: "draft"
     * @queryParam per_page int Elemek száma oldalonként. Példa: 10
     */
    public function getEvents(Request $r): LengthAwarePaginator
    {
        // Csak a saját események listája (admin mindent lát)
        $q = Event::query()
            ->when(!$r->user()->hasRole('admin'), fn($q)=>$q->where('organizer_id', $r->user()->id))
            ->withSum(['bookings as confirmed_quantity' => function($q){
                $q->where('status','confirmed');
            }], 'quantity');

        // Keresés: cím, leírás
        if ($s = $r->string('search')->toString()) {
            $q->where(fn($w)=>$w->where('title','like',"%$s%")->orWhere('description','like',"%$s%"));
        }

        // Keresés: kategória
        if ($cat = $r->string('category')->toString()) { $q->where('category',$cat); }
        // Keresés: státusz
        if ($st = $r->string('status')->toString())   { $q->where('status',$st); }

        // Rendezés alapértelmezetten starts_at DESC szerint
        $q->orderBy('starts_at','desc');

        // Policy: listanézet engedélye
        $this->authorize('viewAny', Event::class);

        return $q->paginate($r->integer('per_page', 10));
    }

    /**
     * Esemény létrehozása.
     * Csak az admin-ok és a szervezők hozhatnak létre eseményt.
     * A létrehozott eseményt a saját események listájához adja hozzá.
     *
     * @param  \App\Http\Requests\StoreEventRequest  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(StoreEventRequest $request): JsonResponse
    {
        // Csak az admin-ok és a szervezők hozhatnak létre eseményt
        $this->authorize('create', Event::class);

        // A kérés validálás után a user_id-t fel kell tölteni
        $data = $request->validated();
        $data['organizer_id'] = $request->user()->id;
        // Alapértelmezetten draft státuszba kerül az esemény
        $data['status'] ??= 'draft';

        // Esemény létrehozása
        $event = Event::create($data);

        // A létrehozott eseményt a saját események listájához adjuk hozzá
        return response()->json($event->loadSum(
            ['bookings as confirmed_quantity' => fn($q)=>$q->where('status','confirmed')], 'quantity'
        ), Response::HTTP_CREATED);
    }

    /**
     * Frissítse a megadott erőforrást a tárolóban.
     *
     * @param  \App\Http\Requests\UpdateEventRequest  $request
     * @param  int  $id
     * @return \App\Models\Event
     */
    public function update(UpdateEventRequest $request, $id): Event
    {
        // Az eseményt keressük meg, hogy a Model-ben lévő fillable-okkal
        // dolgozzunk, és ne lehessen olyan mezőket módosítani, amelyek
        // nincsenek definiálva a Model-ben.
        $event = Event::findOrFail($id);

        // Az authorize() megtörtént a Request-ben, ezért itt már nem kell
        // ellenőrizni.

        // A validált adatokkal frissítjük az eseményt
        $event->update($request->validated());

        // Az eseményt frissítjük, hogy a loadSum() metódus működjön
        // A confirmed_quantity-t is feltöltjük, hogy a szervezők lássák
        // a sikeresen fizetett foglalások számát.
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
