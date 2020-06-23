<?php

return [

    "appName" => "Polyel",

    /*
    │------------------------------------------------------------------------------
    │ Encryption Settings
    │------------------------------------------------------------------------------
    │ Here you must set your encryption key if you want to use the Crypt
    | service built into Polyel that will handle the encryption and decryption
    | process for you. Polyel uses openssl to perform AES encryption with a MAC.
    | You must set a securely generated key with the correct length if you want your
    | encrypted data to be safe.
    |
    | Current supported ciphers are:
    |   AES-128-CBC, AES-256-CBC,
    |   AES-128-GCM, AES-256-GCM
    │
    */
    "encryptionKey" => env('Encryption.KEY', ''),
    "encryptionCipher" => "AES-256-CBC",

];