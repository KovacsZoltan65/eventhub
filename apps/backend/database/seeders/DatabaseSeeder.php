<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();
        
        activity()->disableLogging();
        
        $this->call([
            RolesAndUsersSeeder::class, // létrehozza: admin|organizer|user + userek
            PermissionsSeeder::class,   // jogosultságok + kiosztás

            EventsTableSeeder::class,   // Események
            BookingsTableSeeder::class,  // Foglalások
        ]);
        
        activity()->enableLogging();
        
        //User::factory()->create([
        //    'name' => 'Test User',
        //    'email' => 'test@example.com',
        //]);
    }
}
