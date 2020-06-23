<?php

namespace Polyel\Encryption;

use RuntimeException;

class EncryptionManager implements Encryption
{
    private $initialised;

    private $encrypter;

    private string $key;

    private string $cipher;

    public function __construct()
    {
        $this->initialised = false;
    }

    public function setup()
    {
        if($this->initialised === false)
        {
            $key = config('main.encryptionKey');
            $cipher = config('main.encryptionCipher');

            $key = base64_decode($key);

            if($this->validateKeyAndCipher($key, $cipher))
            {
                $this->key = $key;
                $this->cipher = $cipher;
                $this->encrypter = new AesCbcEncrypter($this->key, $this->cipher);

                $this->initialised = true;
            }
            else
            {
                throw new RuntimeException('Encryption key and cipher not compatible, only AES-128-CBC (16 bit) & AES-256-CBC (32 bit) are supported');
            }
        }
    }

    private function validateKeyAndCipher($key, $cipher)
    {
        if(in_array('8bit', mb_list_encodings()))
        {
            $keyLength = mb_strlen($key, '8bit');

            return ($cipher === 'AES-128-CBC' && $keyLength === 16) || ($cipher === 'AES-256-CBC' && $keyLength === 32);
        }
        else
        {
            throw new RuntimeException('Cannot verify encryption key & cipher because mbstring lib is not installed');
        }
    }

    public function generateEncryptionKey()
    {
        $randomKey = random_bytes(config('main.encryptionCipher') === 'AES-128-CBC' ? 16 : 32);

        return base64_encode($randomKey);
    }

    public function getEncryptionKey()
    {
        return $this->key;
    }

    public function encrypt($data, $serialize = true)
    {
        return $this->encrypter->encrypt($data, $serialize);
    }

    public function decrypt($payload, $unserialize = true)
    {
        return $this->encrypter->decrypt($payload, $unserialize);
    }

    public function encryptString($string)
    {
        return $this->encrypt($string, false);
    }

    public function decryptString($payload)
    {
        return $this->decrypt($payload, false);
    }
}