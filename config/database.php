<?php

return [

    "Main_Database" => [

        "driver" => "mysql",

        "database" => env("Main_Database.DATABASE", "phase1"),

        "host" => env("Main_Database.HOST", "127.0.0.1"),

        "port" => env("Main_Database.PORT", "3306"),

        "username" => env("Main_Database.USERNAME", "phase"),

        "password" => env("Main_Database.PASSWORD", ""),
    ],

    "Second_Database" => [

        "driver" => "mysql",

        "database" => env("Second_Database.DATABASE", "phase2"),

        "host" => env("Second_Database.HOST", "127.0.0.1"),

        "port" => env("Second_Database.PORT", "3306"),

        "username" => env("Second_Database.USERNAME", "phase"),

        "password" => env("Second_Database.PASSWORD", ""),
    ],
];