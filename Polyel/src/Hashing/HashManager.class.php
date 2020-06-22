<?php

namespace Polyel\Hashing;

class HashManager implements Hasher
{
    private $hasher;

    public function __construct()
    {

    }

    public function setup()
    {
        $algoName = config('hashing.algo');

        switch($algoName)
        {
            case 'bcrypt':

                $this->hasher = new BcryptHasher(config("hashing.$algoName"));

            break;
        }
    }

    public function info($hashedValue)
    {
        return $this->hasher->info($hashedValue);
    }

    public function make($value, array $options = [])
    {
        return $this->hasher->make($value, $options);
    }

    public function check($value, $hashedValue)
    {
        return $this->hasher->check($value, $hashedValue);
    }

    public function needsRehash($hashedValue, array $options = [])
    {
        return $this->hasher->needsRehash($hashedValue, $options);
    }
}