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
            "database" => env("Main_Database.DATABASE", "polyel1"),
            "port" => env("Main_Database.PORT", "3306"),
            "username" => env("Main_Database.USERNAME", "polyel"),
            "password" => env("Main_Database.PASSWORD", ""),
            "charset" => "utf8mb4",
            "collation" => "utf8mb4_unicode_ci",
            "prefix" => "",

            /*
             * Connection Pool Configuration
             *
             * Wait Timeout: Maximum timeout for how long to wait for a connection in seconds
             * Connection Idle Timeout: Timeout in minutes how long a connection can be idle for
             * Min Connections: Number of minimum connections to keep alive
             * Max Connections: Maximum number of connections in the pool allowed
             *
             * NOTE: A pool is created for each worker process, so a max of 10 connections for read and write
             * could lead to 10 read + 10 write = 20 * 4 workers = 80 total connections. Tune your read and write
             * pools to a suitable level and make sure to check database server settings for the maximum number
             * of connections allowed.
             */
            "pool" => [

                "waitTimeout" => 5,
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