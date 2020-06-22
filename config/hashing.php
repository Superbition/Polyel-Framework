<?php

return [

    'algo' => 'argon2id',

    'bcrypt' => [
        'rounds' => 10,
    ],

    'argon' => [
        'memory' => 1024,
        'threads' => 2,
        'time' => 2,
    ],

];