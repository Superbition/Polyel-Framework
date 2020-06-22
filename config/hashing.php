<?php

return [

    'algo' => 'bcrypt',

    'bcrypt' => [
        'rounds' => 10,
    ],

    'argon' => [
        'memory' => 1024,
        'threads' => 2,
        'time' => 2,
    ],

];