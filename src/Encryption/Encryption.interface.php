<?php

namespace Polyel\Encryption;

interface Encryption
{
    public function encrypt($data, $serialize = true);

    public function decrypt($payload, $unserialize = true);

    public function encryptString($string);

    public function decryptString($payload);
}