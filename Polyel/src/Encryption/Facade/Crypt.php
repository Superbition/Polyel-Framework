<?php

namespace Polyel\Encryption\Facade;

use Polyel;

/**
 * @method static encrypt($data, $serialize = true)
 * @method static decrypt($payload, $unserialize = true)
 * @method static encryptString($string)
 * @method static decryptString($payload)
 */
class Crypt
{
    public static function __callStatic($method, $arguments)
    {
        return Polyel::call(Polyel\Encryption\EncryptionManager::class)->$method(...$arguments);
    }
}