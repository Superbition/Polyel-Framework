<?php

return [

    "default" => env("Database.Default_Connection", "DefaultDatabase"),

    "connections" => [

        "mysql" => [

            "read" => [

                "DefaultDatabase",

            ],

            "write" => [

                "DefaultDatabase",

            ],

            "sticky" => true,

            "databases" => [

                "DefaultDatabase" => [

                    "active" => true,
                    "driver" => "mysql",
                    "database" => env("Main_Database.DATABASE", "polyel1"),
                    "host" => env("Main_Database.HOST", "127.0.0.1"),
                    "port" => env("Main_Database.PORT", "3306"),
                    "username" => env("Main_Database.USERNAME", "polyel"),
                    "password" => env("Main_Database.PASSWORD", ""),
                    "charset" => "utf8mb4",
                    "collation" => "utf8mb4_unicode_ci",
                    "prefix" => "",
                    "pool" => [

                        "minConnections" => 5,
                        "maxConnections" => 80,
                        "connectionIdleTimeout" => 1,
                        "waitTimeout" => 1,

                    ]

                ],

                "SecondDatabase" => [

                    "active" => false,
                    "driver" => "mysql",
                    "database" => env("Second_Database.DATABASE", "polyel2"),
                    "host" => env("Second_Database.HOST", "127.0.0.1"),
                    "port" => env("Second_Database.PORT", "3306"),
                    "username" => env("Second_Database.USERNAME", "polyel"),
                    "password" => env("Second_Database.PASSWORD", ""),
                    "charset" => "utf8mb4",
                    "collation" => "utf8mb4_unicode_ci",
                    "prefix" => "",
                    "pool" => [

                        "minConnections" => 5,
                        "maxConnections" => 80,
                        "connectionIdleTimeout" => 1,

                    ]

                ],

            ],

        ],

    ],

];