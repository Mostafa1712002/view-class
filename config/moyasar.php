<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Environment
    |--------------------------------------------------------------------------
    |
    | Which set of keys to use. Test keys charge nothing and produce payments
    | that only exist in the dashboard's test environment.
    |
    */

    'mode' => env('MOYASAR_MODE', 'test'),

    /*
    |--------------------------------------------------------------------------
    | Originating site
    |--------------------------------------------------------------------------
    |
    | One Moyasar account serves more than one product, so every payment and
    | invoice this app creates carries this tag in its metadata and description.
    | It is what tells you, in the dashboard, which site the money came from.
    |
    */

    'site' => env('MOYASAR_SITE', 'viewclass'),

    /*
    |--------------------------------------------------------------------------
    | API
    |--------------------------------------------------------------------------
    */

    'api_url' => env('MOYASAR_API_URL', 'https://api.moyasar.com/v1'),

    'currency' => env('MOYASAR_CURRENCY', 'SAR'),

    /*
    |--------------------------------------------------------------------------
    | Keys
    |--------------------------------------------------------------------------
    |
    | The publishable key is safe in a page; the secret key never leaves the
    | server and is what confirms a payment really was captured.
    |
    */

    'keys' => [

        'test' => [
            'publishable' => env('MOYASAR_TEST_PUBLISHABLE_KEY'),
            'secret' => env('MOYASAR_TEST_SECRET_KEY'),
        ],

        'live' => [
            'publishable' => env('MOYASAR_LIVE_PUBLISHABLE_KEY'),
            'secret' => env('MOYASAR_LIVE_SECRET_KEY'),
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Webhook
    |--------------------------------------------------------------------------
    |
    | Moyasar posts payment events back with this token. It is the only thing
    | separating a real notification from anyone who finds the endpoint, and
    | activating on the browser's word instead is how paid customers end up
    | locked out. Configure the same value in the dashboard.
    |
    */

    'webhook_secret' => env('MOYASAR_WEBHOOK_SECRET'),

];
