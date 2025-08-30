<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class PermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Cache ürítés
        app()[PermissionRegistrar::class]->forgetCachedPermissions();
        
        // --- Jogosultságok (névkonvenció: domain.action.scope) ----------------
        $permissions = [
            // Events (publikus/megtekintés)
            'event.view.published',   // user: csak published
            'event.view.any',         // admin/organizer listanézet (policy szűri "own"-t)

            // Events (szerkesztés – organizer only saját eseményre)
            'event.create',
            'event.update.own',
            'event.delete.own',
            'event.publish.own',
            
            'event.cancel.own',    // organizer saját eseményt lemondhat
            'event.cancel.any',    // admin bármely eseményt lemondhat

            // Bookings
            'booking.create',      // user foglalhat
            'booking.view.mine',   // saját foglalások
            'booking.view.any',    // admin átnézheti
            'booking.cancel.mine', // user a saját foglalását lemondhatja
            'booking.cancel.any',  // admin bármely foglalást lemondhat

            // Users (admin)
            'user.list',
            'user.block',
            'user.unblock',
        ];
        
        foreach ($permissions as $name) {
            Permission::findOrCreate($name, 'web');
        }
        
        // --- Szerepkörök betöltése -------------------------------------------
        $admin     = Role::findByName('admin', 'web');
        $organizer = Role::findByName('organizer', 'web');
        $user      = Role::findByName('user', 'web');
        
        // --- Kiosztás ---------------------------------------------------------
        // Admin: mindenhez hozzáfér
        $admin->syncPermissions(Permission::all());
        
        // Organizer: saját események kezelése + listanézet
        $organizer->syncPermissions([
            'event.view.any',
            'event.create',
            'event.update.own',
            'event.delete.own',
            'event.publish.own',
            'event.cancel.own',
            'booking.view.mine',
        ]);
        
        // User: published események böngészése + foglalás + saját foglalások
        $user->syncPermissions([
            'event.view.published',
            'booking.create',
            'booking.cancel.mine',
            'booking.view.mine',
        ]);
        
        // Cache frissítés
        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
}
