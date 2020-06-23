<?php

namespace Polyel\Encryption;

class Encrypter implements Encryption
{
    private string $key;

    private string $cipher;

    public function __construct($key, $cipher)
    {
        $this->key = $key;
        $this->cipher = $cipher;
    }

    public function encrypt($data, $serialize = true)
    {

    }

    public function decrypt($payload, $unserialize = true)
    {

    }

    public function encryptString($string)
    {

    }

    public function decryptString($payload)
    {

    }

    private function hashForMac($iv, $data)
    {
        return hash_hmac('sha256', $iv.$data, $this->key);
    }
}