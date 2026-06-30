<?php

return [

    /*
    |--------------------------------------------------------------------------
    | SMS sandbox
    |--------------------------------------------------------------------------
    | When ON, a school whose SMS service is *active* but has no real gateway
    | credentials sends through the in-process SandboxDriver: no network call,
    | the message is marked delivered, credit is deducted per segment, and the
    | row surfaces in reports — the full flow is demonstrable without a paid
    | provider. The moment real credentials are entered, SmsService picks
    | HttpProviderDriver instead (this switch is the off-ramp, not the trigger).
    |
    | WhatsApp uses its own per-school switch (the `provider` column: 'log' =
    | sandbox, 'http' = real), so it needs no global flag here.
    */
    'sms' => [
        'sandbox' => (bool) env('SMS_SANDBOX', true),
    ],

];
