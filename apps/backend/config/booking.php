<?php

return [
    // Egy felhasználó eseményenként maximum ennyi jegyet foglalhat (összesítve, confirmed státuszra)
    'max_per_user_per_event' => env('BOOKING_MAX_PER_USER', 5),
];

