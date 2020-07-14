<?php

return [

    /*
    │------------------------------------------------------------------------------
    │ Default Authentication Routes
    │------------------------------------------------------------------------------
    |
    │
    */
    'routes' => [

      'login' => '/login',
      'register' => '/register',

    ],

    /*
    │------------------------------------------------------------------------------
    │ Authentication Defaults
    │------------------------------------------------------------------------------
    |
    │
    */
    'defaults' => [

        'protector' => 'web',
        'reset' => 'users',

    ],

    /*
    │------------------------------------------------------------------------------
    │ Authentication Protectors
    │------------------------------------------------------------------------------
    |
    │
    */
    'protectors' => [

        'web' => [
            'driver' => 'session',
            'source' => 'users',
        ],

        'api' => [
            'driver' => 'token',
            'source' => 'users',
        ],
    ],

    /*
    │------------------------------------------------------------------------------
    │ Authentication Sources
    │------------------------------------------------------------------------------
    |
    │
    */
    'sources' => [

        'users' => [
            'table' => 'users',
        ],

    ],

    /*
    │------------------------------------------------------------------------------
    │ Authentication Resets
    │------------------------------------------------------------------------------
    |
    │
    */
    'resets' => [

        'passwords' => [

            'users' => [
                'source' => 'users',
                'table' => 'password_resets',
                'timeout' => 20,
                'expire' => 60,
            ],

        ],

    ],

    /*
    │------------------------------------------------------------------------------
    │ Email Verification Expiration
    │------------------------------------------------------------------------------
    |
    │
    */
    'verification_expiration' => 60,

    /*
    │------------------------------------------------------------------------------
    │ Password Confirmation Timeout
    │------------------------------------------------------------------------------
    |
    │
    */
    'password_confirmation_timeout' => 7200,

    /*
    │------------------------------------------------------------------------------
    │ API Token Lifetime
    │------------------------------------------------------------------------------
    |
    │
    */
    'api_token_lifetime' => '1 year',

];