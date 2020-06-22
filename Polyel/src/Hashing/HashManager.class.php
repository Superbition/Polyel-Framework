<?php

namespace Polyel\Hashing;

use RuntimeException;

class HashManager implements Hasher
{
    private $hasher;

    private $argonAlgos = [
        'argon2i' => PASSWORD_ARGON2I,
        'argon2id' => PASSWORD_ARGON2ID
    ];

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

            case 'argon2i':
            case 'argon2id':

                $this->hasher = new ArgonHasher(config("hashing.argon"));
                $this->hasher->setArgonType($this->argonAlgos[$algoName]);

            break;

            default:

                throw new RuntimeException("invalid hashing algorithm in config: $algoName");
        }
    }

    public function info($hashedValue)
    {
        return $this->hasher->info($hashedValue);
    }

    public function create($value, array $options = [])
    {
        return $this->hasher->create($value, $options);
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