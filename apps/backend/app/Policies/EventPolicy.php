<?php

namespace App\Policies;

use App\Models\Event;
use App\Models\User;

class EventPolicy
{
    /**
     * Create a new policy instance.
     */
    public function __construct()
    {
        //
    }
    
    public function viewAny(User $user): bool
    {
        // organizer/admin láthat listát (controller úgyis szűr sajátokra)
        return $user->hasAnyRole(['organizer', 'admin']) || $user->can('event.view.any');
    }
    
    public function view(User $user, Event $event): bool
    {
        // published bárkinek látható (publikus végpont kezeli), nem policy téma
        // organizer csak a sajátját lássa itt
        return $this->owns($user, $event) || $user->hasRole('admin');
    }
    
    public function create(User $user): bool
    {
        return $user->can('event.create');
    }
    
    public function update(User $user, Event $event): bool
    {
        return $user->can('event.update.own') && $this->owns($user, $event);
    }
    
    public function delete(User $user, Event $event): bool
    {
        return $user->can('event.delete.own') && $this->owns($user, $event);
    }
    
    public function publish(User $user, Event $event): bool
    {
        return $user->can('event.publish.own') && $this->owns($user, $event);
    }
    
    private function owns(User $user, Event $event): bool
    {
        return (int)$event->organizer_id === (int)$user->id;
    }
    
    public function cancel(\App\Models\User $user, \App\Models\Event $event): bool
    {
        // admin mindent megtehet, ha van Gate::before, ez opcionális
        if ($user->hasRole('admin')) return true;

        return $user->can('event.cancel.own') && (int)$event->organizer_id === (int)$user->id;
    }
    
}
