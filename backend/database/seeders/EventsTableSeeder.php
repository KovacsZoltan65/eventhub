<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

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
