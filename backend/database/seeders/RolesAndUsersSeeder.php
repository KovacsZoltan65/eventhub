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
        ['name'=>'Admin', 'password'=>bcrypt('Admin123!')]);

    $org = User::firstOrCreate(
        ['email'=>'org@eventhub.local'],
        ['name'=>'Organizer', 'password'=>bcrypt('Org123!')]
    );

    $usr = User::firstOrCreate(
        ['email'=>'user@eventhub.local'],
        ['name'=>'User', 'password'=>bcrypt('User123!')]
    );

    $admin->syncRoles('admin');
    $org->syncRoles('organizer');
    $usr->syncRoles('user');
  }
}
