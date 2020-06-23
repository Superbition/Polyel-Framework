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
                switch($cipher)
                {
                    case 'AES-128-CBC':
                    case 'AES-256-CBC':

                    $this->key = $key;
                    $this->cipher = $cipher;
                    $this->encrypter = new AesCbcEncrypter($this->key, $this->cipher);

                    break;

                    case 'AES-128-GCM':
                    case 'AES-256-GCM':

                    $this->key = $key;
                    $this->cipher = $cipher;
                    $this->encrypter = new AesGcmEncrypter($this->key, $this->cipher);

                    break;
                }

                if(exists($this->encrypter))
                {
                    $this->initialised = true;
                }
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

            switch($cipher)
            {
                case 'AES-128-CBC':
                case 'AES-128-GCM':

                    $cipherLength = 16;

                break;

                case 'AES-256-CBC':
                case 'AES-256-GCM':

                    $cipherLength = 32;

                break;

                default:

                    $cipherLength = null;
            }

            return ($cipherLength === $keyLength);
        }
        else
        {
            throw new RuntimeException('Cannot verify encryption key & cipher because mbstring lib is not installed');
        }
    }

    public function generateEncryptionKey($cipher = null)
    {
        $cipher = $cipher ?? config('main.encryptionCipher');

        switch($cipher)
        {
            case 'AES-128-CBC':
            case 'AES-128-GCM':

                $keyLen = 16;

            break;

            case 'AES-256-CBC':
            case 'AES-256-GCM':

                $keyLen = 32;

            break;
        }

        $randomKey = random_bytes($keyLen);

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