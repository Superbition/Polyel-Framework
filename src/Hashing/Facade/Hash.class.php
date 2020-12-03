<?php

namespace Polyel\Hashing\Facade;

use Polyel;

class Hash
{
    public static function __callStatic($method, $arguments)
    {
        return Polyel::call(Polyel\Hashing\HashManager::class)->$method(...$arguments);
    }
}