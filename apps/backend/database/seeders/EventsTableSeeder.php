<?php

namespace Database\Seeders;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use function collect;
use function env;
use function fake;

class EventsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = fake('hu_HU');
        
        $categories = [
            'tech','music','sport','theatre','business',
            'education','health','family','arts','outdoor',
        ];
        
        // 1) Organizer felhasználók begyűjtése (Spatie role alapján)
        $organizerIds = User::role('organizer')->pluck('id')->all();
        
        // Ha valamiért üres (fallback): whereHas('roles')
        if (count($organizerIds) === 0) {
            $organizerIds = User::whereHas('roles', fn($q) => $q->where('name', 'organizer'))
                ->pluck('id')->all();
        }
        
        // Utolsó fallback: első 2 user
        if (count($organizerIds) === 0) {
            $organizerIds = User::limit(2)->pluck('id')->all();
        }

        // 2) Összes esemény száma
        $total = 50;
    
        // 3) Súlyok (%.env-ben felülírható, pl.: ORGANIZER_EVENT_SPLIT="60,40")
        // Ha nincs env, és legalább 2 organizer van: [60,40], a maradék (ha több szervező) egyenlően.
        $envSplit = env('ORGANIZER_EVENT_SPLIT'); // pl. "60,40" -> [60,40]
        $weights = [];
        
        if ($envSplit) {
            $weights = collect(explode(',', $envSplit))
                ->map(fn($v) => max(0, (int)trim($v)))
                ->filter(fn($v) => $v > 0)
                ->values()
                ->all();
        }

        if (empty($weights)) {
            if (count($organizerIds) >= 2) {
                $weights = [60, 40];
            } else {
                // 1 organizer esetén 100%
                $weights = [100];
            }
        }
        
        // Ha több organizer van, mint súly, a maradék egyenlően oszlik el
        if (count($weights) < count($organizerIds)) {
            $remaining = count($organizerIds) - count($weights);
            $even = (int) floor((100 - array_sum($weights)) / max(1, $remaining));
            for ($i = 0; $i < $remaining; $i++) {
                $weights[] = $even;
            }
        }
        
        // Normalizálás, hogy összegük 100 legyen (biztonság kedvéért)
        $sum = array_sum($weights);
        if ($sum <= 0) {
            $weights = array_fill(0, count($organizerIds), (int) floor(100 / max(1, count($organizerIds))));
            $sum = array_sum($weights);
        }
        
        // 4) Eseményszám kiosztása szervezőnként (kerekítések kezelése)
        $counts = [];
        $accumulated = 0;
        foreach ($organizerIds as $i => $uid) {
            // utolsó szervező megkapja a maradékot, hogy mindig pont $total legyen
            if ($i === count($organizerIds) - 1) {
                $counts[$uid] = $total - $accumulated;
            } else {
                $c = (int) round($total * ($weights[$i] / $sum));
                $counts[$uid] = $c;
                $accumulated += $c;
            }
        }
        
        // 5) Események generálása a kiosztás szerint
        $events = [];
        foreach ($counts as $organizerId => $cnt) {
            for ($i = 0; $i < $cnt; $i++) {
                $category   = $faker->boolean(70) ? Arr::random($categories) : null;
                $startDate  = $faker->dateTimeBetween('+1 days', '+6 months');

                $events[] = [
                    'organizer_id' => $organizerId,
                    'title'        => $faker->sentence(4),
                    'description'  => $faker->paragraph(3),
                    'starts_at'    => $startDate->format('Y-m-d H:i:s'),
                    'location'     => "{$faker->city}, {$faker->streetAddress}",
                    'capacity'     => $faker->numberBetween(20, 500),
                    'category'     => $category,
                    'status'       => $this->randomStatus(), // nálad már létező metódus
                    'created_at'   => Carbon::now(),
                    'updated_at'   => Carbon::now(),
                ];
            }
        }
        
        // 6) Mentés
        if (!empty($events)) {
            DB::table('events')->insert($events);
        }
        
        /*
        $events = [];

        for ($i = 0; $i < 50; $i++) {
            //$capacity = $faker->numberBetween(50, 500);
            
            // 70% eséllyel legyen kategória, különben null
            $category = $faker->boolean(70) ? Arr::random($categories) : null;
            
            $startDate = $faker->dateTimeBetween('+1 days', '+6 months');

            $events[] = [
                'organizer_id'    => 2,
                'title'           => $faker->sentence(4),
                'description'     => $faker->paragraph(3),
                'starts_at'       => $startDate->format('Y-m-d H:i:s'),
                'location'        => "{$faker->city}, {$faker->streetAddress}",
                'capacity'        => $faker->numberBetween(20, 500),
                //'remaining_seats' => $capacity, // induláskor nincs foglalás
                'category'        => $category,
                'status'          => $this->randomStatus(), // többnyire published
                'created_at'      => Carbon::now(),
                'updated_at'      => Carbon::now(),
            ];
        }

        DB::table('events')->insert($events);
         */
    }
    
    protected function randomStatus(): string
    {
        $statuses = ['draft', 'published', 'cancelled'];
        
        // 80% published, 15% draft, 5% cancelled
        $roll = mt_rand(1, 100);
        return match (true) {
            $roll <= 80 => 'published',
            $roll <= 95 => 'draft',
            default     => 'cancelled',
        };
    }
}
