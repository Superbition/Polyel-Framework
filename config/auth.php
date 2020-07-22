<?php

return [

    /*
    │------------------------------------------------------------------------------
    │ Authentication Defaults
    │------------------------------------------------------------------------------
    | The default protector and reset configuration that is used when no
    | protector is specified during authentication validation. It is recommended to
    | leave the default as your web authentication.
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
    | The list of configured protectors used to perform authentication on different
    | types of requests. Here by default we have web for browser based requests and
    | API for all of the API requests that require authentication. Each protector
    | needs a driver and source (database table) to be set.
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
    | Here we have all the configured sources which are related to tables in your
    | database. By default the users table is provided as it gives a good base for
    | where most if not all your users will be located when they need to be
    | authenticated during a request. The idea is you can configure a new source
    | for different types of users like admins for example.
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
    | Polyel provides the functionality to reset a users password out of the box,
    | here you can change the settings related to how a password reset is
    | performed. The source is the table name of where your users are located and
    | is standalone from the configured sources. The table is where all your
    | password reset tokens are located. The timeout is set in minutes and dictates
    | how long a user has to wait before requesting a new password reset email.
    | Finally, expire which is set in minutes, sets how long a password token is
    | valid for.
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
    | Set in minutes, this defines how long a verification email is valid for
    | before the user needs to request a new email verification link to validate
    | their email address.
    │
    */
    'verification_expiration' => 60,

    /*
    │------------------------------------------------------------------------------
    │ Password Confirmation Timeout
    │------------------------------------------------------------------------------
    | Set in seconds, this defines how long a  valid password confirmation lasts
    | before the user is asked to confirm their password again on password
    | protected pages/routes. The default 7200 is 2 hours.
    │
    */
    'password_confirmation_timeout' => 7200,

    /*
    │------------------------------------------------------------------------------
    │ Database API Token Table
    │------------------------------------------------------------------------------
    | This is the table name for where API tokens are stored, the default name
    | does not need to be changed but you may alter this if you like. Please make
    | sure to also refer to the documentation for what columns and data types the
    | API token table requires for API authentication to work.
    │
    */
    'api_database_token_table' => 'api_tokens',

    /*
    │------------------------------------------------------------------------------
    │ API Token Lifetime
    │------------------------------------------------------------------------------
    | When a token is created to gain access to your API, this defines how long a
    | API is valid for before it expires. It uses a long lasting default so a
    | token refresh is not needed too frequently. You can alter the token lifetime
    | after it has been created, this is only the initial expiration date.
    │
    */
    'api_token_lifetime' => '1 year',

];