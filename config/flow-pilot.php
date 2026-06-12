<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Registered Flows
    |--------------------------------------------------------------------------
    |
    | List flow classes here so they can be discovered by the flow:list command.
    |
    */
    'flows' => [
        //
    ],

    'queue' => [
        'connection' => env('FLOW_PILOT_QUEUE_CONNECTION'),
        'queue' => env('FLOW_PILOT_QUEUE', 'default'),
    ],

    'retries' => [
        'attempts' => 3,
        'backoff' => [60, 300, 900],
    ],

    'prune' => [
        'completed_after_days' => 30,
        'failed_after_days' => 90,
    ],

    /*
    |--------------------------------------------------------------------------
    | Payload Storage
    |--------------------------------------------------------------------------
    |
    | These keys are redacted anywhere they appear before payloads, step inputs,
    | or step outputs are persisted.
    |
    */
    'payloads' => [
        'redact' => [
            'password',
            'token',
            'secret',
            'api_key',
            'authorization',
            'card',
            'card_number',
            'cvv',
        ],
    ],
];
