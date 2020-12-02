<?php

namespace Polyel\Encryption;

use JsonException;
use Polyel\Encryption\Exception\EncryptionException;
use Polyel\Encryption\Exception\DecryptionException;

class AesGcmEncrypter implements Encryption
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
        // The initialisation vector for the GCM cipher
        $ivector = random_bytes(openssl_cipher_iv_length($this->cipher));

        /*
         * Go ahead and try and encrypt the data with the iv, key and selected cipher
         * The data is passed through the PHP serialize function if $serialize if set to true.
         */
        $encrypted = openssl_encrypt(
            $serialize ? serialize($data) : $data,
            $this->cipher,
            $this->key,
            0,
            $ivector,
            $tag);

        // Only proceed if the encryption was successful
        if($encrypted === false)
        {
            throw new EncryptionException('Encryption failed, data could not be encrypted with openssl');
        }

        // Convert the iv and GCM tag to a storable representation for JSON
        $ivector = base64_encode($ivector);
        $tag = base64_encode($tag);

        try
        {
            // Store the encrypted data with its iv and hmac hash using JSON
            $payload = json_encode(compact('ivector', 'encrypted', 'tag'), JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
        }
        catch(JsonException $e)
        {
            // Catch and throw any JSON encoding errors...
            throw new EncryptionException('Encryption failed during payload packing: ' . $e->getMessage());
        }

        if($payload !== false)
        {
            // If the JSON encode was successful we can convert everything to bse64 for easier storage
            $payload = base64_encode($payload);
        }

        // Return the encrypted payload
        return $payload;
    }

    public function decrypt($payload, $unserialize = true)
    {
        // Because the payload is formatted using JSON which holds encryption details, decode the base64 data...
        $payload = $this->decodePayload($payload);

        // The payload could not be decoded, invalid payload format
        if($payload === false)
        {
            throw new DecryptionException('Decryption failed, invalid payload');
        }

        // Decode the iv and GCM tag to their raw values
        $ivector = base64_decode($payload['ivector']);
        $tag = base64_decode($payload['tag']);

        $decrypted = openssl_decrypt($payload['encrypted'], $this->cipher, $this->key, 0, $ivector, $tag);

        if($decrypted === false)
        {
            throw new DecryptionException('Decryption failed using openssl');
        }

        // Return the decrypted data and unserialize the data if set to true
        return $unserialize ? unserialize($decrypted, ['allowed_classes' => false]) : $decrypted;
    }

    public function encryptString($string)
    {
        return $this->encrypt($string, false);
    }

    public function decryptString($payload)
    {
        return $this->decrypt($payload, false);
    }

    private function decodePayload($payload)
    {
        try
        {
            // Decode the payload from base64 and then decode it from JSON...
            $payload = json_decode(base64_decode($payload), true, 1024, JSON_THROW_ON_ERROR);
        }
        catch(JsonException $e)
        {
            // Catch any JSON decoding errors...
            throw new DecryptionException('Decryption failed on payload decoding: ' . $e->getMessage());
        }

        // If JSON decoding was not fully successful, the payload is either false or not an array
        if($payload === false || !is_array($payload))
        {
            return false;
        }

        // Make sure we have all the required encryption details from the encoded JSON payload
        if(!exists($payload['ivector']) || !exists($payload['encrypted']) || !exists($payload['tag']))
        {
            return false;
        }

        // Validate that the iv is the same length as the required cipher iv length
        if(mb_strlen(base64_decode($payload['ivector'], true), '8bit') !== openssl_cipher_iv_length($this->cipher))
        {
            return false;
        }

        // Finally return the decoded JSON payload if no errors were found
        return $payload;
    }
}