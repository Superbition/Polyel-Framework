<?php

return [

    /*
     * The default database connection to use when one is not provided
     * and as the default connection to use for all database interaction.
     */
    "default" => env("Database.Default_Connection", "default"),

    "connections" => [

        "default" => [

            "read" => [
                "hosts" => [

                    env("Main_Database.HOST", "127.0.0.1")

                ],
            ],

            "write" => [
                "hosts" => [

                    env("Main_Database.HOST", "127.0.0.1")

                ],
            ],

            "active" => true,
            "driver" => "mysql",
            "sticky" => true,
            "database" => env("Main_Database.DATABASE", "polyel1"),
            "port" => env("Main_Database.PORT", "3306"),
            "username" => env("Main_Database.USERNAME", "polyel"),
            "password" => env("Main_Database.PASSWORD", ""),
            "charset" => "utf8mb4",
            "collation" => "utf8mb4_unicode_ci",
            "prefix" => "",

            /*
             * Wait Timeout: Maximum timeout for how long to wait for a connection in seconds
             * Connection Idle Timeout: Timeout in minutes how long a connection can be idle for
             * Min Connections: Number of minimum connections to keep alive
             * Max Connections: Maximum number of connections in the pool allowed
             */
            "pool" => [

                "waitTimeout" => 1,
                "connectionIdleTimeout" => 1,

                "read" => [

                    "minConnections" => 5,
                    "maxConnections" => 10,

                ],

                "write" => [

                    "minConnections" => 5,
                    "maxConnections" => 10,

                ],

            ],

        ],

    ],

];