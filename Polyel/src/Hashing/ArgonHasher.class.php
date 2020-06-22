<?php

namespace Polyel\Hashing;

use RuntimeException;

class ArgonHasher extends BaseHasher Implements Hasher
{
    protected $argonAlgo;

    protected $memory;

    protected $time;

    protected $threads;

    public function __construct(array $options)
    {
        $this->memory = $options['memory'] ?? 1024;
        $this->time = $options['time'] ?? 2;
        $this->threads = $options['threads'] ?? 2;
    }

    public function setArgonType($argonAlgo)
    {
        $this->argonAlgo = $argonAlgo;
    }

    public function create($value, array $options = [])
    {
        $memory = $options['memory'] ?? $this->memory;
        $time = $options['time'] ?? $this->time;
        $threads = $options['threads'] ?? $this->threads;

        $hash = password_hash($value, $this->argonAlgo, [
            'memory_cost' => $memory,
            'time_cost' => $time,
            'threads' => $threads,
        ]);

        if($hash === false)
        {
            throw new RuntimeException('Argon2 algorithms are not available for hashing');
        }

        return $hash;
    }

    public function check($value, $hashedValue)
    {
        if($this->info($hashedValue)['algo'] !== $this->argonAlgo)
        {
            throw new RuntimeException('The given hash does not use the Argon2 algorithms');
        }

        return Parent::check($value, $hashedValue);
    }

    public function needsRehash($hashedValue, array $options = [])
    {
        $memory = $options['memory'] ?? $this->memory;
        $time = $options['time'] ?? $this->time;
        $threads = $options['threads'] ?? $this->threads;

        return password_needs_rehash($hashedValue, $this->argonAlgo, [
            'memory_cost' => $memory,
            'time_cost' => $time,
            'threads' => $threads,
        ]);
    }
}