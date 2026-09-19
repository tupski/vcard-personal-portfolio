<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Notification recipient
    |--------------------------------------------------------------------------
    |
    | Where the "new contact message" email is sent. When unset, the profile
    | row's own email address is used, so the feature works out of the box
    | without inventing another CMS setting. Set CONTACT_NOTIFY_TO to override
    | it (e.g. to a mailbox that is not published on the site).
    |
    */

    'notify_to' => env('CONTACT_NOTIFY_TO'),

    /*
    |--------------------------------------------------------------------------
    | Rate limiting
    |--------------------------------------------------------------------------
    |
    | Attempts allowed per IP address within the decay window. Deliberately
    | generous enough for a legitimate visitor (and for a shared/NAT address)
    | while still stopping scripted abuse. Tune per deployment.
    |
    */

    'rate_limit' => [
        'max' => (int) env('CONTACT_RATE_LIMIT', 10),
        'decay' => (int) env('CONTACT_RATE_DECAY', 600),
    ],

    /*
    |--------------------------------------------------------------------------
    | Honeypot
    |--------------------------------------------------------------------------
    |
    | Name of the decoy field. It is rendered off-screen and hidden from
    | assistive technology, so a real visitor never sees or fills it. Any
    | submission that populates it is discarded.
    |
    */

    'honeypot' => 'website',
];
