<?php

namespace Polyel\Encryption\Facade;

use Polyel;

class Crypt
{
    public static function __callStatic($method, $arguments)
    {
        return Polyel::call(Polyel\Encryption\EncryptionManager::class)->$method(...$arguments);
    }
}