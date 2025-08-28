<?php

namespace Database\Seeders;

use App\Models\User;
//use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RolesAndUsersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (['admin','organizer','user'] as $r) {
            Role::findOrCreate($r);
        }

        $admin = User::firstOrCreate(
            ['email'=>'admin@eventhub.local'],
            ['name'=>'Admin', 'password'=>bcrypt('Admin123!')]
        );

        $org1 = User::firstOrCreate(
            ['email'=>'org1@eventhub.local'],
            ['name'=>'Organizer1', 'password'=>bcrypt('Org123!')]
        );
        
        $org2 = User::firstOrCreate(
            ['email'=>'org2@eventhub.local'],
            ['name'=>'Organizer2', 'password'=>bcrypt('Org123!')]
        );

        $usr = User::firstOrCreate(
            ['email'=>'user@eventhub.local'],
            ['name'=>'User', 'password'=>bcrypt('User123!')]
        );

        // Adminisztrátor
        $admin->syncRoles('admin');
        
        // Szervezők
        $org1->syncRoles('organizer');
        $org2->syncRoles('organizer');
        
        // Felhasználó
        $usr->syncRoles('user');
    }
}
