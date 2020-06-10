<?php

return [

    "default" => env("Database.Default_Connection", "mysql"),

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

                        /*
                         * minConnections: Number of minimum connections to keep alive
                         * maxConnections: Maximum number of connections in the pool allowed
                         * connectionIdleTimeout: Timeout in minutes how long a connection can be idle for
                         * waitTimeout: Maximum timeout for how long to wait for a connection in seconds
                         */
                        "minConnections" => 5,
                        "maxConnections" => 20,
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

                        /*
                         * minConnections: Number of minimum connections to keep alive
                         * maxConnections: Maximum number of connections in the pool allowed
                         * connectionIdleTimeout: Timeout in minutes how long a connection can be idle for
                         * waitTimeout: Maximum timeout for how long to wait for a connection in seconds
                         */
                        "minConnections" => 5,
                        "maxConnections" => 20,
                        "connectionIdleTimeout" => 1,
                        "waitTimeout" => 1,

                    ]

                ],

            ],

        ],

    ],

];