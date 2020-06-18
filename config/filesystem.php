<?php

return [

    /*
    │------------------------------------------------------------------------------
    │ Default Storage Drive
    │------------------------------------------------------------------------------
    │
    │
    */
    "default" => "local",

    /*
    │------------------------------------------------------------------------------
    │ Configured Storage Drivers
    │------------------------------------------------------------------------------
    │ You can configure all your drivers for your application. Each drive needs
    | a driver so Polyel knows how to access the drive you want to interact with.
    | You may add as many drives as you need, each drive must have a unique name.
    |
    | Supported storage drivers: "local" (More coming)
    │
    */
    "drives" => [

        "local" => [
            "driver" => "local",
            "root" => ROOT_DIR . "/storage/app",
        ],

        "public" => [
            "driver" => "local",
            "root" => ROOT_DIR . "/storage/app/public",
        ]

    ]

];