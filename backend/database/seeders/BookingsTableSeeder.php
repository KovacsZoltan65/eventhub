<?php

namespace Database\Seeders;

use App\Models\Booking;
use App\Models\Event;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;

class BookingsTableSeeder extends Seeder
{
    /** Összes létrehozandó foglalás */
    protected int $TOTAL = 20;

    /** Userenként max ennyi jegy/event lehet confirmed */
    protected int $PER_EVENT_USER_LIMIT = 5;

    /** Cancelled arány (pl. 10%) – a kerek számot garantáljuk */
    protected int $CANCELLED_PERCENT = 10;
    
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $events = Event::query()
            ->select(['id', 'capacity', 'status'])
            ->where('status', 'published')
            ->get();

        $userIds = User::query()->pluck('id')->all();

        if ($events->isEmpty() || empty($userIds)) {
            $this->command?->warn('Nincs elég esemény vagy felhasználó a BookingsTableSeeder futtatásához.');
            return;
        }

        $desiredCancelled = (int) floor($this->TOTAL * $this->CANCELLED_PERCENT / 100);
        $cancelledCreated = 0;

        $created  = 0;
        $attempts = 0;

        while ($created < $this->TOTAL && $attempts < $this->TOTAL * 20) {
            $attempts++;

            /** @var \App\Models\Event $event */
            $event  = $events->random();
            $userId = Arr::random($userIds);

            // Az eseményen eddig megerősített mennyiség
            $confirmedForEvent = (int) Booking::query()
                ->where('event_id', $event->id)
                ->where('status', 'confirmed')
                ->sum('quantity');

            $availableForEvent = max(0, (int) $event->capacity - $confirmedForEvent);

            // Ugyanettől a usertől erre az eseményre confirmed mennyiség
            $confirmedForUserEvent = (int) Booking::query()
                ->where('event_id', $event->id)
                ->where('user_id', $userId)
                ->where('status', 'confirmed')
                ->sum('quantity');

            $userLimitLeft   = max(0, $this->PER_EVENT_USER_LIMIT - $confirmedForUserEvent);
            $maxConfirmable  = min($availableForEvent, $userLimitLeft, $this->PER_EVENT_USER_LIMIT);

            // Mennyi rekord van még hátra, ennyit kell még cancelled-ből „behozni” a garantált darabszámhoz
            $remainingSlots          = $this->TOTAL - $created;
            $remainingCancelledNeed  = max(0, $desiredCancelled - $cancelledCreated);

            // Válasszunk státuszt: garantált cancelled darabszám teljesüljön,
            // egyébként 10% eséllyel cancelled, különben confirmed/pending kapacitástól függően.
            $status = null;
            if ($remainingSlots === $remainingCancelledNeed) {
                $status = 'cancelled'; // most már KELL cancelled-et létrehozni
            } else {
                $roll = random_int(1, 100);
                if ($cancelledCreated < $desiredCancelled && $roll <= $this->CANCELLED_PERCENT) {
                    $status = 'cancelled';
                } elseif ($maxConfirmable > 0) {
                    $status = 'confirmed';
                } else {
                    $status = 'pending';
                }
            }

            // Mennyiségek
            if ($status === 'confirmed') {
                $qty = random_int(1, max(1, $maxConfirmable));
            } else {
                // pending/cancelled – tetszőleges 1..5 (nem befolyásolja a remaininget)
                $qty = random_int(1, $this->PER_EVENT_USER_LIMIT);
            }

            // Egységár: maradhat 0, vagy adhatsz kis randomot (pl. 0|1500|2500)
            $priceOptions = [0, 1500, 2500, 3500];
            $unitPrice    = Arr::random($priceOptions);

            Booking::create([
                'user_id'    => $userId,
                'event_id'   => $event->id,
                'quantity'   => $qty,
                'status'     => $status,    // pending|confirmed|cancelled
                'unit_price' => $unitPrice, // unsignedInteger
            ]);

            if ($status === 'cancelled') {
                $cancelledCreated++;
            }
            $created++;
        }

        $this->command?->info("BookingsTableSeeder: {$created} foglalás létrehozva. (cancelled: {$cancelledCreated}/{$desiredCancelled})");
    }
}
