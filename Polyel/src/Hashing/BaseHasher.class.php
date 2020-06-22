<?php

namespace Polyel\Hashing;

abstract class BaseHasher
{
    /*
     * Return information about the provided hash value.
     */
    public function info($hashedValue)
    {
        return password_get_info($hashedValue);
    }

    /*
     * Perform a hash verification on the given raw value and the hash
     */
    public function check($value, $hashedValue)
    {
        if(strlen($hashedValue) === 0)
        {
            return false;
        }

        return password_verify($value, $hashedValue);
    }
}