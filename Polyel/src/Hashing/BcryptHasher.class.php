<?php

namespace Polyel\Hashing;

use http\Exception\RuntimeException;

class BcryptHasher extends BaseHasher Implements Hasher
{
    protected $rounds;

    public function __construct(array $options)
    {
        $this->rounds = $options['rounds'] ?? 10;
    }

    public function create($value, array $options = [])
    {
        $rounds = $options['rounds'] ?? $this->rounds;

        $hash = password_hash($value, PASSWORD_BCRYPT, [
            'cost' => $rounds,
        ]);

        if($hash === false)
        {
            throw new RuntimeException('Bcrypt algorithm is not available for hashing');
        }

        return $hash;
    }

    public function check($value, $hashedValue)
    {
        if($this->info($hashedValue)['algoName'] !== 'bcrypt')
        {
            throw new RuntimeException('The given hash does not use the Bcrypt algorithm');
        }

        return Parent::check($value, $hashedValue);
    }

    public function needsRehash($hashedValue, array $options = [])
    {
        $rounds = $options['rounds'] ?? $this->rounds;

        return password_needs_rehash($hashedValue, PASSWORD_BCRYPT, [
            'cost' => $rounds
        ]);
    }
}