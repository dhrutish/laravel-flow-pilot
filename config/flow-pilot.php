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
