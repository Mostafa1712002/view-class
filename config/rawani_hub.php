<?php

// Per-instance switch for the Rawani hub module (design §2). Off by default: only rawaniedu.com turns it on.
return [
    'enabled' => (bool) env('RAWANI_HUB_ENABLED', false),

    // School that hub-created people are placed in.
    'default_school_id' => env('RAWANI_HUB_SCHOOL_ID'),
];
