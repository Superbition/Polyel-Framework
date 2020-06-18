<?php

return [

    "default" => "local",

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