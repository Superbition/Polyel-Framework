<?php

function encrypt($data, $serialize = true)
{
    return Polyel::call(Polyel\Encryption\EncryptionManager::class)->encrypt($data, $serialize);
}

function decrypt($payload, $unserialize = true)
{
    return Polyel::call(Polyel\Encryption\EncryptionManager::class)->decrypt($payload, $unserialize);
}