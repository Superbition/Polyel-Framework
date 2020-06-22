<?php

namespace Polyel\Hashing;

interface Hasher
{
    public function info($hashedValue);

    public function create($value, array $options = []);

    public function check($value, $hashedValue);

    public function needsRehash($hashedValue, array $options = []);
}