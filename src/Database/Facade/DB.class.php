<?php

namespace Polyel\Database\Facade;

use Polyel;

class DB
{
    public static function __callStatic($method, $arguments)
    {
        return Polyel::call(Polyel\Database\Database::class)->$method(...$arguments);
    }
}